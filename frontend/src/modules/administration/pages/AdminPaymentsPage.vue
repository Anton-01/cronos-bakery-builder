<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'

import { useToast } from '@/composables/useToast'
import {
  adminPanelService,
  type AdminPayment,
  type PaymentGateway,
  type Paginated,
} from '../services/adminPanelService'

const { success, error } = useToast()

type Tab = 'payments' | 'gateways'

const activeTab = ref<Tab>('payments')

const paymentsResponse = ref<Paginated<AdminPayment> | null>(null)
const loadingPayments = ref(false)
const retryingId = ref<string | null>(null)

const gateways = ref<PaymentGateway[]>([])
const loadingGateways = ref(false)
const togglingId = ref<string | null>(null)

function money(amount: number, currency = 'CRC'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateStr))
}

function paymentStatusSeverity(status: string): 'success' | 'warn' | 'danger' | 'secondary' {
  if (status === 'completed' || status === 'paid') return 'success'
  if (status === 'pending') return 'warn'
  if (status === 'failed') return 'danger'
  return 'secondary'
}

async function loadPayments(): Promise<void> {
  loadingPayments.value = true
  try {
    paymentsResponse.value = await adminPanelService.payments()
  } finally {
    loadingPayments.value = false
  }
}

async function loadGateways(): Promise<void> {
  loadingGateways.value = true
  try {
    gateways.value = await adminPanelService.gateways()
  } finally {
    loadingGateways.value = false
  }
}

async function retryPayment(payment: AdminPayment): Promise<void> {
  retryingId.value = payment.id
  try {
    const updated = await adminPanelService.retryPayment(payment.id)
    if (paymentsResponse.value) {
      const idx = paymentsResponse.value.data.findIndex((p) => p.id === payment.id)
      if (idx !== -1) paymentsResponse.value.data[idx] = updated
    }
    success('Pago reintentado exitosamente')
  } catch {
    error('Error al reintentar el pago')
  } finally {
    retryingId.value = null
  }
}

async function toggleGateway(gw: PaymentGateway): Promise<void> {
  togglingId.value = gw.id
  try {
    const updated = await adminPanelService.updateGateway(gw.id, { is_active: !gw.is_active })
    const idx = gateways.value.findIndex((g) => g.id === gw.id)
    if (idx !== -1) gateways.value[idx] = updated
    success(updated.is_active ? 'Pasarela activada' : 'Pasarela desactivada')
  } catch {
    error('Error al actualizar la pasarela')
  } finally {
    togglingId.value = null
  }
}

function selectTab(tab: Tab): void {
  activeTab.value = tab
  if (tab === 'payments' && paymentsResponse.value === null) loadPayments()
  else if (tab === 'gateways' && gateways.value.length === 0) loadGateways()
}

onMounted(() => {
  loadPayments()
})
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Pagos</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Finanzas <span>/</span> Pagos</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tab-bar">
      <Button
        label="Pagos"
        :severity="activeTab === 'payments' ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="selectTab('payments')"
      />
      <Button
        label="Pasarelas"
        :severity="activeTab === 'gateways' ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="selectTab('gateways')"
      />
    </div>

    <!-- Payments tab -->
    <Card v-if="activeTab === 'payments'">
      <template #title>Listado de Pagos</template>
      <template #content>
        <div v-if="loadingPayments" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>

        <DataTable v-else-if="paymentsResponse" :value="paymentsResponse.data" class="p-datatable-sm">
          <template #empty>
            <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">No hay pagos registrados.</div>
          </template>

          <Column header="Pedido" style="width:130px;">
            <template #body="{ data }">
              <strong>{{ data.order_number ? `#${data.order_number}` : data.order_id }}</strong>
            </template>
          </Column>
          <Column header="Pasarela" field="gateway" style="width:120px." />
          <Column header="Estado" style="width:110px.">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="paymentStatusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Monto" style="width:120px.">
            <template #body="{ data }">{{ money(data.amount, data.currency) }}</template>
          </Column>
          <Column header="Fecha" style="width:180px.">
            <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
          </Column>
          <Column header="Acciones" style="width:120px.">
            <template #body="{ data }">
              <Button
                v-if="data.status === 'failed'"
                :label="retryingId === data.id ? 'Reintentando...' : 'Reintentar'"
                :loading="retryingId === data.id"
                size="small"
                severity="warn"
                outlined
                @click="retryPayment(data)"
              />
              <span v-else style="color:var(--admin-text-muted);">—</span>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Gateways tab -->
    <Card v-if="activeTab === 'gateways'">
      <template #title>Pasarelas de Pago</template>
      <template #content>
        <div v-if="loadingGateways" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>

        <DataTable v-else :value="gateways" class="p-datatable-sm">
          <template #empty>
            <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">No hay pasarelas configuradas.</div>
          </template>

          <Column header="Nombre">
            <template #body="{ data }"><strong>{{ data.name }}</strong></template>
          </Column>
          <Column header="Driver" field="driver" style="width:140px." />
          <Column header="Estado" style="width:100px.">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Activo' : 'Inactivo'" :severity="data.is_active ? 'success' : 'danger'" />
            </template>
          </Column>
          <Column header="Acciones" style="width:130px.">
            <template #body="{ data }">
              <Button
                :label="togglingId === data.id ? 'Guardando...' : (data.is_active ? 'Desactivar' : 'Activar')"
                :loading="togglingId === data.id"
                :severity="data.is_active ? 'danger' : 'success'"
                size="small"
                outlined
                @click="toggleGateway(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.tab-bar {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}
</style>
