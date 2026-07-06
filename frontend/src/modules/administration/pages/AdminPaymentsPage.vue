<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import ToggleSwitch from 'primevue/toggleswitch'
import ProgressSpinner from 'primevue/progressspinner'

import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { usePaymentGatewayStore } from '../stores/paymentGateways'
import {
  adminPanelService,
  type GatewayDriver,
  type PaymentGateway,
  type Transaction,
  type TransactionStatus,
} from '../services/adminPanelService'

const { success, error } = useToast()
const { confirm } = useConfirm()
const store = usePaymentGatewayStore()

// ============================ Pasarelas ============================

/** Borradores de edición por pasarela: solo lo que el usuario re-escribe viaja al API. */
interface GatewayDraft {
  name: string
  credentials: Record<string, string>
}
const drafts = reactive<Record<number, GatewayDraft>>({})

function draftFor(gateway: PaymentGateway): GatewayDraft {
  if (!drafts[gateway.id]) {
    drafts[gateway.id] = { name: gateway.name, credentials: {} }
  }
  return drafts[gateway.id]
}

function fieldsFor(gateway: PaymentGateway) {
  return store.driverFor(gateway.driver_name)?.fields ?? []
}

function draftHasChanges(gateway: PaymentGateway): boolean {
  const draft = drafts[gateway.id]
  if (!draft) return false
  if (draft.name.trim() !== gateway.name) return true
  return Object.values(draft.credentials).some((v) => v !== '')
}

async function saveGateway(gateway: PaymentGateway): Promise<void> {
  const draft = draftFor(gateway)
  const credentials: Record<string, string> = {}
  for (const [key, value] of Object.entries(draft.credentials)) {
    if (value !== '') credentials[key] = value
  }
  try {
    await store.updateGateway(gateway.id, {
      name: draft.name.trim() || gateway.name,
      ...(Object.keys(credentials).length ? { credentials } : {}),
    })
    delete drafts[gateway.id]
    success('Pasarela actualizada')
  } catch {
    error('Error al guardar la pasarela')
  }
}

async function toggleActive(gateway: PaymentGateway): Promise<void> {
  try {
    const updated = await store.toggleActive(gateway)
    success(updated.is_active ? 'Pasarela activada' : 'Pasarela desactivada')
  } catch {
    error('Error al actualizar la pasarela')
  }
}

async function toggleEnvironment(gateway: PaymentGateway): Promise<void> {
  if (gateway.environment === 'sandbox') {
    const ok = await confirm({
      title: 'Cambiar a Producción',
      message: `"${gateway.name}" empezará a operar con dinero real usando las credenciales configuradas. ¿Continuar?`,
      action: 'confirm',
      confirmText: 'Cambiar a Producción',
    })
    if (!ok) return
  }
  try {
    const updated = await store.toggleEnvironment(gateway)
    success(updated.environment === 'production' ? 'Pasarela en Producción' : 'Pasarela en Sandbox')
  } catch {
    error('Error al cambiar el entorno')
  }
}

async function removeGateway(gateway: PaymentGateway): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar pasarela',
    message: `¿Eliminar la configuración de "${gateway.name}"? El historial de transacciones se conserva.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await store.removeGateway(gateway.id)
    success('Pasarela eliminada')
  } catch {
    error('Error al eliminar la pasarela')
  }
}

// --- Alta de pasarela ---
const newDialog = ref(false)
const newForm = reactive({
  driver_name: '',
  name: '',
  environment: 'sandbox' as 'sandbox' | 'production',
  is_active: false,
  credentials: {} as Record<string, string>,
})

const newDriver = computed<GatewayDriver | undefined>(() =>
  store.drivers.find((d) => d.driver_name === newForm.driver_name),
)

function openNewDialog(): void {
  newForm.driver_name = ''
  newForm.name = ''
  newForm.environment = 'sandbox'
  newForm.is_active = false
  newForm.credentials = {}
  newDialog.value = true
}

function onNewDriverChange(): void {
  newForm.credentials = {}
  newForm.name = newDriver.value?.label ?? ''
}

async function createGateway(): Promise<void> {
  if (!newForm.driver_name || !newForm.name.trim()) return
  const credentials: Record<string, string> = {}
  for (const [key, value] of Object.entries(newForm.credentials)) {
    if (value !== '') credentials[key] = value
  }
  try {
    await store.createGateway({
      driver_name: newForm.driver_name,
      name: newForm.name.trim(),
      environment: newForm.environment,
      is_active: newForm.is_active,
      ...(Object.keys(credentials).length ? { credentials } : {}),
    })
    newDialog.value = false
    success('Pasarela configurada')
  } catch {
    error('Error al crear la pasarela')
  }
}

// ============================ Transacciones ============================

const statusOptions: { label: string; value: TransactionStatus | '' }[] = [
  { label: 'Todos los estados', value: '' },
  { label: 'Pendiente', value: 'pending' },
  { label: 'Procesando', value: 'processing' },
  { label: 'Pagado', value: 'paid' },
  { label: 'Fallido', value: 'failed' },
  { label: 'Reembolsado', value: 'refunded' },
  { label: 'Cancelado', value: 'cancelled' },
]

const dateRange = ref<Date[] | null>(null)
const detailVisible = ref(false)
const detail = ref<Transaction | null>(null)
const detailLoading = ref(false)
const refundingId = ref<number | null>(null)
const retryingId = ref<number | null>(null)

const gatewayFilterOptions = computed(() => [
  { label: 'Todas las pasarelas', value: null as number | null },
  ...store.gateways.map((g) => ({ label: g.name, value: g.id as number | null })),
])

function toYmd(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

async function applyFilters(page = 1): Promise<void> {
  store.filters.date_from = dateRange.value?.[0] ? toYmd(dateRange.value[0]) : ''
  store.filters.date_to = dateRange.value?.[1] ? toYmd(dateRange.value[1]) : ''
  try {
    await store.loadTransactions(page)
  } catch {
    error('Error al cargar las transacciones')
  }
}

function clearFilters(): void {
  store.filters.status = ''
  store.filters.gateway_id = null
  dateRange.value = null
  applyFilters()
}

function onPage(event: { page: number }): void {
  applyFilters(event.page + 1)
}

function money(amount: number, currency = 'USD'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateStr))
}

function statusSeverity(status: TransactionStatus): 'success' | 'warn' | 'danger' | 'info' | 'secondary' {
  switch (status) {
    case 'paid': return 'success'
    case 'pending': return 'warn'
    case 'processing': return 'info'
    case 'failed': return 'danger'
    case 'refunded': return 'info'
    default: return 'secondary'
  }
}

async function openDetail(transaction: Transaction): Promise<void> {
  detailVisible.value = true
  detailLoading.value = true
  try {
    detail.value = await adminPanelService.transaction(transaction.id)
  } catch {
    error('Error al cargar el detalle')
    detailVisible.value = false
  } finally {
    detailLoading.value = false
  }
}

async function refund(transaction: Transaction): Promise<void> {
  const ok = await confirm({
    title: 'Reembolsar transacción',
    message: `Se solicitará a ${transaction.gateway_name ?? transaction.driver_name} el reembolso de ${money(transaction.amount, transaction.currency)}. Esta acción no se puede deshacer.`,
    action: 'delete',
    confirmText: 'Reembolsar',
  })
  if (!ok) return
  refundingId.value = transaction.id
  try {
    await store.refundTransaction(transaction.id)
    success('Reembolso procesado')
  } catch {
    error('Error al procesar el reembolso')
  } finally {
    refundingId.value = null
  }
}

async function retry(transaction: Transaction): Promise<void> {
  retryingId.value = transaction.id
  try {
    await store.retryTransaction(transaction.id)
    success('Reintento de conciliación encolado')
  } catch {
    error('Error al encolar el reintento')
  } finally {
    retryingId.value = null
  }
}

onMounted(async () => {
  try {
    await Promise.all([store.loadDrivers(), store.loadGateways(), store.loadTransactions()])
  } catch {
    error('Error al cargar el módulo de pagos')
  }
})
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Pagos</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Finanzas <span>/</span> Pagos</div>
      </div>
      <Button
        v-if="store.availableDrivers.length"
        label="Nueva Pasarela"
        icon="pi pi-plus"
        @click="openNewDialog"
      />
    </div>

    <Tabs value="transactions">
      <TabList>
        <Tab value="transactions"><i class="pi pi-list" style="margin-right:0.4rem;" />Transacciones</Tab>
        <Tab value="gateways"><i class="pi pi-credit-card" style="margin-right:0.4rem;" />Pasarelas</Tab>
      </TabList>
      <TabPanels>
        <!-- ==================== Centro de Transacciones ==================== -->
        <TabPanel value="transactions">
          <Card>
            <template #content>
              <!-- Filtros -->
              <div class="tx-filters">
                <Select
                  v-model="store.filters.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Estado"
                  style="min-width:11rem;"
                  @change="applyFilters()"
                />
                <Select
                  v-model="store.filters.gateway_id"
                  :options="gatewayFilterOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Pasarela"
                  style="min-width:12rem;"
                  @change="applyFilters()"
                />
                <DatePicker
                  v-model="dateRange"
                  selectionMode="range"
                  :manualInput="false"
                  showIcon
                  placeholder="Rango de fechas"
                  dateFormat="dd/mm/yy"
                  style="min-width:14rem;"
                  @update:modelValue="applyFilters()"
                />
                <Button
                  v-tooltip.top="'Limpiar filtros'"
                  icon="pi pi-filter-slash"
                  size="small"
                  severity="secondary"
                  text
                  rounded
                  aria-label="Limpiar filtros"
                  @click="clearFilters"
                />
              </div>

              <div v-if="store.loadingTransactions" style="display:flex; justify-content:center; padding:3rem;">
                <ProgressSpinner />
              </div>

              <DataTable
                v-else
                :value="store.transactions?.data ?? []"
                lazy
                paginator
                :rows="20"
                :totalRecords="store.transactions?.meta?.total ?? 0"
                :first="((store.transactions?.meta?.current_page ?? 1) - 1) * 20"
                dataKey="id"
                @page="onPage"
              >
                <template #empty>
                  <p style="text-align:center; color:var(--admin-text-muted); margin:1.5rem 0;">
                    No hay transacciones para los filtros seleccionados.
                  </p>
                </template>

                <Column field="id" header="#" style="width:4rem;" />
                <Column header="Orden">
                  <template #body="{ data }">
                    <span style="font-weight:600;">{{ data.order_number ?? data.order_id.slice(0, 8) }}</span>
                  </template>
                </Column>
                <Column header="Pasarela">
                  <template #body="{ data }">
                    {{ data.gateway_name ?? data.driver_name }}
                    <Tag
                      v-if="data.environment === 'sandbox'"
                      value="Sandbox"
                      severity="secondary"
                      style="margin-left:0.35rem; font-size:0.6rem;"
                    />
                  </template>
                </Column>
                <Column header="Referencia">
                  <template #body="{ data }">
                    <code class="tx-ref">{{ data.provider_transaction_id ?? '—' }}</code>
                  </template>
                </Column>
                <Column header="Monto">
                  <template #body="{ data }">{{ money(data.amount, data.currency) }}</template>
                </Column>
                <Column header="Estado">
                  <template #body="{ data }">
                    <Tag :value="data.status_label" :severity="statusSeverity(data.status)" />
                  </template>
                </Column>
                <Column header="Fecha">
                  <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                </Column>
                <Column header="Acciones" style="width:9rem;">
                  <template #body="{ data }">
                    <div style="display:flex; gap:0.2rem;">
                      <Button
                        v-tooltip.top="'Ver detalle y auditoría'"
                        icon="pi pi-eye"
                        size="small"
                        severity="info"
                        text
                        rounded
                        aria-label="Ver detalle"
                        @click="openDetail(data)"
                      />
                      <Button
                        v-if="data.status === 'pending' || data.status === 'processing'"
                        v-tooltip.top="'Reintentar conciliación'"
                        icon="pi pi-refresh"
                        size="small"
                        severity="warn"
                        text
                        rounded
                        :loading="retryingId === data.id"
                        aria-label="Reintentar conciliación"
                        @click="retry(data)"
                      />
                      <Button
                        v-if="data.status === 'paid'"
                        v-tooltip.top="'Reembolsar'"
                        icon="pi pi-undo"
                        size="small"
                        severity="danger"
                        text
                        rounded
                        :loading="refundingId === data.id"
                        aria-label="Reembolsar"
                        @click="refund(data)"
                      />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <!-- ==================== Configuración de Pasarelas ==================== -->
        <TabPanel value="gateways">
          <div v-if="store.loadingGateways" style="display:flex; justify-content:center; padding:3rem;">
            <ProgressSpinner />
          </div>

          <p
            v-else-if="!store.gateways.length"
            style="text-align:center; padding:3rem; color:var(--admin-text-muted);"
          >
            No hay pasarelas configuradas. Crea una con el botón "Nueva Pasarela".
          </p>

          <Accordion v-else :value="[]" multiple>
            <AccordionPanel v-for="gateway in store.gateways" :key="gateway.id" :value="gateway.id">
              <AccordionHeader>
                <div class="gw-header">
                  <span class="gw-header__name">{{ gateway.name }}</span>
                  <Tag :value="gateway.driver_label" severity="secondary" />
                  <Tag
                    :value="gateway.environment === 'production' ? 'Producción' : 'Sandbox'"
                    :severity="gateway.environment === 'production' ? 'danger' : 'info'"
                  />
                  <Tag
                    :value="gateway.is_active ? 'Activa' : 'Inactiva'"
                    :severity="gateway.is_active ? 'success' : 'secondary'"
                  />
                </div>
              </AccordionHeader>
              <AccordionContent>
                <!-- Switches de estado -->
                <div class="gw-switches">
                  <label class="gw-switch">
                    <ToggleSwitch
                      v-tooltip.top="'Activa o desactiva esta pasarela para los clientes'"
                      :modelValue="gateway.is_active"
                      :disabled="store.savingGatewayId === gateway.id"
                      :aria-label="`Activar ${gateway.name}`"
                      @update:modelValue="toggleActive(gateway)"
                    />
                    <span>Pasarela activa</span>
                  </label>
                  <label class="gw-switch">
                    <ToggleSwitch
                      v-tooltip.top="'Sandbox: pruebas sin dinero real · Producción: cobros reales'"
                      :modelValue="gateway.environment === 'production'"
                      :disabled="store.savingGatewayId === gateway.id"
                      :aria-label="`Entorno de ${gateway.name}`"
                      @update:modelValue="toggleEnvironment(gateway)"
                    />
                    <span>Entorno: <strong>{{ gateway.environment === 'production' ? 'Producción' : 'Sandbox' }}</strong></span>
                  </label>
                  <div style="margin-left:auto;">
                    <Button
                      v-tooltip.top="'Eliminar pasarela'"
                      icon="pi pi-trash"
                      size="small"
                      severity="danger"
                      text
                      rounded
                      aria-label="Eliminar pasarela"
                      @click="removeGateway(gateway)"
                    />
                  </div>
                </div>

                <!-- Formulario -->
                <div class="gw-form">
                  <div class="gw-field">
                    <label>Nombre</label>
                    <InputText v-model="draftFor(gateway).name" fluid />
                  </div>

                  <div v-for="field in fieldsFor(gateway)" :key="field.key" class="gw-field">
                    <label>
                      {{ field.label }}
                      <i
                        v-if="gateway.credentials[field.key]"
                        v-tooltip.top="`Configurado: ${gateway.credentials[field.key]}`"
                        class="pi pi-check-circle gw-field__configured"
                      />
                    </label>
                    <Password
                      v-if="field.secret"
                      v-model="draftFor(gateway).credentials[field.key]"
                      :feedback="false"
                      toggleMask
                      fluid
                      :placeholder="gateway.credentials[field.key] ?? 'Sin configurar'"
                    />
                    <InputText
                      v-else
                      v-model="draftFor(gateway).credentials[field.key]"
                      fluid
                      :placeholder="gateway.credentials[field.key] ?? 'Sin configurar'"
                    />
                  </div>

                  <small class="gw-hint">
                    Los secretos se almacenan encriptados y nunca se muestran completos. Deja un campo vacío
                    para conservar el valor actual; escribe uno nuevo para reemplazarlo.
                  </small>

                  <div>
                    <Button
                      label="Guardar cambios"
                      icon="pi pi-save"
                      size="small"
                      :loading="store.savingGatewayId === gateway.id"
                      :disabled="!draftHasChanges(gateway)"
                      @click="saveGateway(gateway)"
                    />
                  </div>
                </div>
              </AccordionContent>
            </AccordionPanel>
          </Accordion>
        </TabPanel>
      </TabPanels>
    </Tabs>

    <!-- Alta de pasarela -->
    <Dialog v-model:visible="newDialog" modal header="Configurar nueva pasarela" :style="{ width: '520px' }">
      <div class="gw-field">
        <label>Proveedor</label>
        <Select
          v-model="newForm.driver_name"
          :options="store.availableDrivers"
          optionLabel="label"
          optionValue="driver_name"
          placeholder="Selecciona un proveedor..."
          fluid
          @change="onNewDriverChange"
        />
      </div>
      <template v-if="newDriver">
        <div class="gw-field">
          <label>Nombre</label>
          <InputText v-model="newForm.name" fluid />
        </div>
        <div class="gw-field">
          <label>Entorno</label>
          <label class="gw-switch">
            <ToggleSwitch
              :modelValue="newForm.environment === 'production'"
              aria-label="Entorno"
              @update:modelValue="newForm.environment = $event ? 'production' : 'sandbox'"
            />
            <span>{{ newForm.environment === 'production' ? 'Producción' : 'Sandbox' }}</span>
          </label>
        </div>
        <div v-for="field in newDriver.fields" :key="field.key" class="gw-field">
          <label>{{ field.label }}</label>
          <Password
            v-if="field.secret"
            v-model="newForm.credentials[field.key]"
            :feedback="false"
            toggleMask
            fluid
          />
          <InputText v-else v-model="newForm.credentials[field.key]" fluid />
        </div>
        <div class="gw-field">
          <label class="gw-switch">
            <ToggleSwitch v-model="newForm.is_active" aria-label="Activar al crear" />
            <span>Activar inmediatamente</span>
          </label>
        </div>
      </template>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined @click="newDialog = false" />
        <Button
          label="Crear"
          :loading="store.savingGatewayId === 'new'"
          :disabled="!newForm.driver_name || !newForm.name.trim()"
          @click="createGateway"
        />
      </template>
    </Dialog>

    <!-- Detalle de transacción -->
    <Dialog v-model:visible="detailVisible" modal header="Detalle de transacción" :style="{ width: '640px' }">
      <div v-if="detailLoading" style="display:flex; justify-content:center; padding:2.5rem;">
        <ProgressSpinner />
      </div>
      <template v-else-if="detail">
        <div class="tx-detail-grid">
          <div><span class="tx-detail-label">Orden</span>{{ detail.order_number ?? detail.order_id }}</div>
          <div><span class="tx-detail-label">Pasarela</span>{{ detail.gateway_name ?? detail.driver_name }}</div>
          <div><span class="tx-detail-label">Referencia</span><code class="tx-ref">{{ detail.provider_transaction_id ?? '—' }}</code></div>
          <div><span class="tx-detail-label">Monto</span>{{ money(detail.amount, detail.currency) }}</div>
          <div>
            <span class="tx-detail-label">Estado</span>
            <Tag :value="detail.status_label" :severity="statusSeverity(detail.status)" />
          </div>
          <div><span class="tx-detail-label">Pagado</span>{{ formatDate(detail.paid_at) }}</div>
          <div><span class="tx-detail-label">Intentos</span>{{ detail.attempts }}</div>
          <div><span class="tx-detail-label">Creada</span>{{ formatDate(detail.created_at) }}</div>
        </div>

        <h4 style="margin:1.25rem 0 0.5rem; font-size:0.85rem;">Historial de eventos</h4>
        <ul class="tx-events">
          <li v-for="event in detail.events ?? []" :key="event.id">
            <span class="tx-events__type">{{ event.type }}</span>
            <Tag v-if="event.status" :value="event.status" severity="secondary" style="font-size:0.6rem;" />
            <i
              v-if="event.signature_valid === true"
              v-tooltip.top="'Firma criptográfica verificada'"
              class="pi pi-verified"
              style="color:var(--admin-success);"
            />
            <span class="tx-events__at">{{ formatDate(event.at) }}</span>
          </li>
          <li v-if="!(detail.events ?? []).length" style="color:var(--admin-text-muted);">Sin eventos registrados.</li>
        </ul>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.tx-filters {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}
.tx-ref {
  font-size: 0.75rem;
  background: var(--admin-bg);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
}
.gw-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.gw-header__name {
  font-weight: 600;
}
.gw-switches {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--admin-border);
  margin-bottom: 1rem;
}
.gw-switch {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--admin-text-secondary);
  cursor: pointer;
}
.gw-form {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
  max-width: 480px;
}
.gw-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.gw-field > label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
.gw-field__configured {
  font-size: 0.75rem;
  color: var(--admin-success);
  margin-left: 0.3rem;
}
.gw-hint {
  font-size: 0.72rem;
  color: var(--admin-text-muted);
}
.tx-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem 1.5rem;
  font-size: 0.85rem;
}
.tx-detail-label {
  display: block;
  font-size: 0.68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--admin-text-muted);
  margin-bottom: 0.15rem;
}
.tx-events {
  list-style: none;
  padding: 0;
  margin: 0;
  font-size: 0.82rem;
}
.tx-events li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0;
  border-bottom: 1px solid var(--admin-border);
}
.tx-events li:last-child {
  border-bottom: none;
}
.tx-events__type {
  font-weight: 600;
  min-width: 7.5rem;
}
.tx-events__at {
  margin-left: auto;
  color: var(--admin-text-muted);
  font-size: 0.75rem;
}
</style>
