<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

import { adminPanelService, type AdminOrder, type Paginated } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'

const { success, error } = useToast()

const ordersResponse = ref<Paginated<AdminOrder> | null>(null)
const loading = ref(true)
const viewMode = ref<'kanban' | 'table'>('kanban')
const selectedOrder = ref<AdminOrder | null>(null)

const COLUMNS = [
  { status: 'pending', label: 'Pendiente', severity: 'warn' },
  { status: 'confirmed', label: 'Confirmado', severity: 'info' },
  { status: 'in_production', label: 'En Produccion', severity: 'info' },
  { status: 'ready', label: 'Listo', severity: 'success' },
  { status: 'completed', label: 'Completado', severity: 'success' },
  { status: 'cancelled', label: 'Cancelado', severity: 'danger' },
] as const

const statusOptions = COLUMNS.map((c) => ({ label: c.label, value: c.status }))

const ordersByStatus = computed(() => {
  const map: Record<string, AdminOrder[]> = {}
  for (const col of COLUMNS) map[col.status] = []
  if (ordersResponse.value) {
    for (const order of ordersResponse.value.data) {
      if (map[order.status]) map[order.status].push(order)
      else map[order.status] = [order]
    }
  }
  return map
})

function money(amount: number, currency = 'CRC'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateStr))
}

function shortDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { day: 'numeric', month: 'short' }).format(new Date(dateStr))
}

function statusSeverity(status: string): 'warn' | 'info' | 'success' | 'danger' | 'secondary' {
  const col = COLUMNS.find((c) => c.status === status)
  return (col?.severity as 'warn' | 'info' | 'success' | 'danger') ?? 'secondary'
}

async function load(): Promise<void> {
  loading.value = true
  try {
    ordersResponse.value = await adminPanelService.adminOrders()
  } finally {
    loading.value = false
  }
}

async function updateStatus(order: AdminOrder, newStatus: string): Promise<void> {
  if (order.status === newStatus) return
  const oldStatus = order.status
  order.status = newStatus
  try {
    const updated = await adminPanelService.updateOrderStatus(order.id, newStatus)
    if (ordersResponse.value) {
      const idx = ordersResponse.value.data.findIndex((o) => o.id === order.id)
      if (idx !== -1) ordersResponse.value.data[idx] = updated
    }
    if (selectedOrder.value?.id === order.id) selectedOrder.value = updated
    success('Pedido #' + order.number + ' actualizado a ' + newStatus)
  } catch {
    order.status = oldStatus
    error('Error al actualizar el estado del pedido')
  }
}

// --- Drag & Drop ---
const draggedOrderId = ref<string | null>(null)
const dragOverColumn = ref<string | null>(null)

function onDragStart(e: DragEvent, order: AdminOrder): void {
  draggedOrderId.value = order.id
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move'
    e.dataTransfer.setData('text/plain', order.id)
  }
}

function onDragEnd(): void {
  draggedOrderId.value = null
  dragOverColumn.value = null
}

function onDragOver(e: DragEvent, status: string): void {
  e.preventDefault()
  if (e.dataTransfer) e.dataTransfer.dropEffect = 'move'
  dragOverColumn.value = status
}

function onDragLeave(e: DragEvent, status: string): void {
  const rel = e.relatedTarget as HTMLElement | null
  const col = (e.currentTarget as HTMLElement)
  if (!rel || !col.contains(rel)) {
    if (dragOverColumn.value === status) dragOverColumn.value = null
  }
}

function onDrop(e: DragEvent, newStatus: string): void {
  e.preventDefault()
  dragOverColumn.value = null
  if (!draggedOrderId.value || !ordersResponse.value) return
  const order = ordersResponse.value.data.find((o) => o.id === draggedOrderId.value)
  if (order && order.status !== newStatus) {
    void updateStatus(order, newStatus)
  }
  draggedOrderId.value = null
}

onMounted(load)
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Pedidos</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Pedidos</div>
      </div>
      <div style="display:flex; gap:0.5rem;">
        <Button
          label="Kanban"
          icon="pi pi-table"
          size="small"
          :severity="viewMode === 'kanban' ? 'primary' : 'secondary'"
          @click="viewMode = 'kanban'"
        />
        <Button
          label="Tabla"
          icon="pi pi-list"
          size="small"
          :severity="viewMode === 'table' ? 'primary' : 'secondary'"
          @click="viewMode = 'table'"
        />
      </div>
    </div>

    <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <template v-else-if="ordersResponse">
      <!-- KANBAN VIEW -->
      <div v-if="viewMode === 'kanban'" class="kanban-board">
        <div
          v-for="col in COLUMNS"
          :key="col.status"
          class="kanban-column"
          :class="[`kanban-column--${col.severity}`, { 'kanban-column--drag-over': dragOverColumn === col.status }]"
          @dragover="onDragOver($event, col.status)"
          @dragleave="onDragLeave($event, col.status)"
          @drop="onDrop($event, col.status)"
        >
          <div class="kanban-column__header">
            <span class="kanban-column__title">{{ col.label }}</span>
            <span class="kanban-column__count">{{ ordersByStatus[col.status]?.length ?? 0 }}</span>
          </div>
          <div class="kanban-column__body">
            <div
              v-for="order in ordersByStatus[col.status]"
              :key="order.id"
              class="kanban-card"
              :class="{ 'kanban-card--dragging': draggedOrderId === order.id }"
              draggable="true"
              @dragstart="onDragStart($event, order)"
              @dragend="onDragEnd"
              @click="selectedOrder = order"
            >
              <div class="kanban-card__header">
                <strong class="kanban-card__number">#{{ order.number }}</strong>
                <span class="kanban-card__date">{{ shortDate(order.placed_at) }}</span>
              </div>
              <div v-if="order.user_name" class="kanban-card__customer">
                <i class="pi pi-user" style="font-size:0.8rem;"></i>
                {{ order.user_name }}
              </div>
              <div class="kanban-card__items">
                <span v-for="(item, i) in order.items.slice(0, 3)" :key="i" class="kanban-card__item-tag">
                  {{ item.quantity }}x {{ item.product_name }}
                </span>
                <span v-if="order.items.length > 3" class="kanban-card__item-tag kanban-card__item-tag--more">
                  +{{ order.items.length - 3 }} mas
                </span>
              </div>
              <div class="kanban-card__footer">
                <span class="kanban-card__total">{{ money(order.totals.total, order.totals.currency) }}</span>
                <span v-if="order.fulfillment?.pickup_date" class="kanban-card__pickup">
                  <i class="pi pi-calendar" style="font-size:0.7rem;"></i>
                  {{ order.fulfillment.pickup_date }}
                </span>
              </div>
            </div>
            <div v-if="(ordersByStatus[col.status]?.length ?? 0) === 0" class="kanban-column__empty">
              Sin pedidos
            </div>
          </div>
        </div>
      </div>

      <!-- TABLE VIEW -->
      <Card v-else>
        <template #title>Listado de Pedidos</template>
        <template #content>
          <DataTable :value="ordersResponse.data" class="p-datatable-sm" :rowHover="true" @row-click="selectedOrder = $event.data">
            <template #empty>
              <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">No hay pedidos registrados.</div>
            </template>
            <Column header="#" field="number">
              <template #body="{ data }"><strong>#{{ data.number }}</strong></template>
            </Column>
            <Column header="Cliente" field="user_name">
              <template #body="{ data }">{{ data.user_name ?? '—' }}</template>
            </Column>
            <Column header="Estado">
              <template #body="{ data }">
                <Tag :value="data.status_label" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Total">
              <template #body="{ data }">{{ money(data.totals.total, data.totals.currency) }}</template>
            </Column>
            <Column header="Fecha">
              <template #body="{ data }">{{ formatDate(data.placed_at) }}</template>
            </Column>
            <Column header="Items">
              <template #body="{ data }">{{ data.items.length }}</template>
            </Column>
            <Column header="Cambiar estado" style="width:180px;" @click.stop>
              <template #body="{ data }">
                <Select
                  :modelValue="data.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  @update:modelValue="updateStatus(data, $event)"
                  @click.stop
                />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </template>

    <!-- Order detail dialog -->
    <Dialog v-model:visible="selectedOrder" modal :header="`Pedido #${selectedOrder?.number}`" :style="{ width: '600px' }" @hide="selectedOrder = null">
      <template v-if="selectedOrder">
        <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem;">
          <Tag :value="selectedOrder.status_label" :severity="statusSeverity(selectedOrder.status)" />
        </div>

        <!-- Customer -->
        <div class="order-section">
          <h4 class="order-section__title">Cliente</h4>
          <div class="order-info-grid">
            <div><span class="order-label">Nombre</span><span>{{ selectedOrder.user_name ?? '—' }}</span></div>
            <div><span class="order-label">Email</span><span>{{ selectedOrder.user_email ?? '—' }}</span></div>
          </div>
        </div>

        <!-- Fulfillment -->
        <div class="order-section">
          <h4 class="order-section__title">Entrega</h4>
          <div class="order-info-grid">
            <div><span class="order-label">Tipo</span><span>{{ selectedOrder.fulfillment?.type ?? '—' }}</span></div>
            <div v-if="selectedOrder.fulfillment?.pickup_date"><span class="order-label">Fecha</span><span>{{ selectedOrder.fulfillment.pickup_date }}</span></div>
            <div v-if="selectedOrder.fulfillment?.pickup_time"><span class="order-label">Hora</span><span>{{ selectedOrder.fulfillment.pickup_time }}</span></div>
          </div>
        </div>

        <!-- Items -->
        <div class="order-section">
          <h4 class="order-section__title">Productos</h4>
          <DataTable :value="selectedOrder.items" class="p-datatable-sm">
            <Column field="product_name" header="Producto" />
            <Column field="quantity" header="Cant." style="width:70px;" />
            <Column header="Subtotal">
              <template #body="{ data }">{{ money(data.line_total, selectedOrder!.totals.currency) }}</template>
            </Column>
          </DataTable>
        </div>

        <!-- Totals -->
        <div class="order-section">
          <div class="order-totals">
            <div class="order-total-row">
              <span>Subtotal</span>
              <span>{{ money(selectedOrder.totals.subtotal, selectedOrder.totals.currency) }}</span>
            </div>
            <div class="order-total-row order-total-row--grand">
              <span>Total</span>
              <span>{{ money(selectedOrder.totals.total, selectedOrder.totals.currency) }}</span>
            </div>
          </div>
        </div>

        <!-- Status change -->
        <div class="order-section">
          <h4 class="order-section__title">Cambiar Estado</h4>
          <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
            <Button
              v-for="col in COLUMNS"
              :key="col.status"
              :label="col.label"
              size="small"
              :severity="selectedOrder.status === col.status ? col.severity : 'secondary'"
              :outlined="selectedOrder.status !== col.status"
              :disabled="selectedOrder.status === col.status"
              @click="updateStatus(selectedOrder!, col.status)"
            />
          </div>
        </div>

        <!-- Meta -->
        <div style="display:flex; gap:1.5rem; font-size:0.75rem; color:var(--admin-text-muted); padding-top:0.75rem; border-top:1px solid var(--admin-border);">
          <span>Creado: {{ formatDate(selectedOrder.placed_at) }}</span>
          <span v-if="selectedOrder.updated_at">Actualizado: {{ formatDate(selectedOrder.updated_at) }}</span>
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
/* Kanban Board */
.kanban-board {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding-bottom: 1rem;
  min-height: calc(100vh - 220px);
}
.kanban-column {
  flex: 0 0 280px;
  min-width: 260px;
  background: var(--admin-bg);
  border-radius: var(--admin-radius);
  display: flex;
  flex-direction: column;
  border: 2px solid transparent;
  transition: border-color 0.2s, background 0.2s;
}
.kanban-column--drag-over {
  border-color: var(--admin-primary);
  background: var(--admin-primary-light);
}
.kanban-column__header {
  padding: 1rem 1rem 0.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.kanban-column__title {
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.kanban-column--warn .kanban-column__title { color: #b37a00; }
.kanban-column--info .kanban-column__title { color: #3672c4; }
.kanban-column--success .kanban-column__title { color: #0a8c6a; }
.kanban-column--danger .kanban-column__title { color: #d4543a; }
.kanban-column__count {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: white;
  background: var(--admin-primary);
}
.kanban-column__body {
  flex: 1;
  padding: 0 0.75rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  min-height: 100px;
}
.kanban-column__empty {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
  color: var(--admin-text-muted);
  font-size: 0.8rem;
  padding: 2rem 0;
  border: 2px dashed var(--admin-border);
  border-radius: var(--admin-radius-sm);
}
.kanban-card {
  background: var(--admin-surface);
  border-radius: var(--admin-radius-sm);
  padding: 0.85rem;
  box-shadow: var(--admin-shadow);
  cursor: grab;
  transition: box-shadow 0.2s, transform 0.15s, opacity 0.2s;
  border: 1px solid var(--admin-border);
}
.kanban-card:hover { box-shadow: var(--admin-shadow-lg); transform: translateY(-1px); }
.kanban-card:active { cursor: grabbing; }
.kanban-card--dragging { opacity: 0.4; transform: rotate(2deg); }
.kanban-card__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.kanban-card__number { font-size: 0.9rem; color: var(--admin-text); }
.kanban-card__date { font-size: 0.7rem; color: var(--admin-text-muted); background: var(--admin-bg); padding: 0.15rem 0.5rem; border-radius: 4px; }
.kanban-card__customer { display: flex; align-items: center; gap: 0.4rem; font-size: 0.8rem; color: var(--admin-text-secondary); margin-bottom: 0.5rem; }
.kanban-card__items { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-bottom: 0.65rem; }
.kanban-card__item-tag { font-size: 0.7rem; background: var(--admin-primary-light); color: var(--admin-primary); padding: 0.15rem 0.45rem; border-radius: 4px; font-weight: 500; white-space: nowrap; }
.kanban-card__item-tag--more { background: var(--admin-bg); color: var(--admin-text-muted); }
.kanban-card__footer { display: flex; justify-content: space-between; align-items: center; }
.kanban-card__total { font-size: 0.85rem; font-weight: 700; color: var(--admin-text); }
.kanban-card__pickup { display: flex; align-items: center; gap: 0.3rem; font-size: 0.7rem; color: var(--admin-text-muted); }

/* Order detail */
.order-section { margin-bottom: 1.5rem; }
.order-section__title { font-size: 0.85rem; font-weight: 600; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 0.75rem; }
.order-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.75rem; }
.order-info-grid > div { display: flex; flex-direction: column; gap: 0.15rem; }
.order-label { font-size: 0.75rem; color: var(--admin-text-muted); font-weight: 500; }
.order-totals { background: var(--admin-bg); border-radius: var(--admin-radius-sm); padding: 1rem; }
.order-total-row { display: flex; justify-content: space-between; padding: 0.35rem 0; font-size: 0.9rem; }
.order-total-row--grand { border-top: 1px solid var(--admin-border); margin-top: 0.35rem; padding-top: 0.65rem; font-weight: 700; font-size: 1.05rem; color: var(--admin-primary); }

@media (max-width: 768px) {
  .kanban-board { flex-direction: column; }
  .kanban-column { flex: none; min-width: 0; }
}
</style>
