<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

import { adminPanelService, type AdminOrder, type Paginated } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'

const { success, error } = useToast()

const ordersResponse = ref<Paginated<AdminOrder> | null>(null)
const loading = ref(true)
const viewMode = ref<'kanban' | 'table'>('kanban')
const selectedOrder = ref<AdminOrder | null>(null)

const COLUMNS = [
  { status: 'pending', label: 'Pendiente', color: 'warning' },
  { status: 'confirmed', label: 'Confirmado', color: 'info' },
  { status: 'in_production', label: 'En Produccion', color: 'info' },
  { status: 'ready', label: 'Listo', color: 'success' },
  { status: 'completed', label: 'Completado', color: 'success' },
  { status: 'cancelled', label: 'Cancelado', color: 'error' },
] as const

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

function statusBadgeClass(status: string): Record<string, boolean> {
  return {
    'admin-badge--warning': status === 'pending',
    'admin-badge--info': status === 'confirmed' || status === 'in_production',
    'admin-badge--success': status === 'ready' || status === 'completed',
    'admin-badge--error': status === 'cancelled',
  }
}

function columnColorClass(color: string): string {
  return `kanban-column--${color}`
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

function openDetail(order: AdminOrder): void {
  selectedOrder.value = order
}

function closeDetail(): void {
  selectedOrder.value = null
}

onMounted(load)
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Pedidos</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Pedidos
        </div>
      </div>
      <div style="display: flex; gap: 0.5rem;">
        <button
          class="admin-btn admin-btn--sm"
          :class="{ 'admin-btn--primary': viewMode === 'kanban' }"
          @click="viewMode = 'kanban'"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="18" rx="1"/><rect x="14" y="3" width="7" height="12" rx="1"/></svg>
          Kanban
        </button>
        <button
          class="admin-btn admin-btn--sm"
          :class="{ 'admin-btn--primary': viewMode === 'table' }"
          @click="viewMode = 'table'"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          Tabla
        </button>
      </div>
    </div>

    <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
      Cargando pedidos...
    </p>

    <template v-else-if="ordersResponse">
      <!-- ===== KANBAN VIEW ===== -->
      <div v-if="viewMode === 'kanban'" class="kanban-board">
        <div
          v-for="col in COLUMNS"
          :key="col.status"
          class="kanban-column"
          :class="[
            columnColorClass(col.color),
            { 'kanban-column--drag-over': dragOverColumn === col.status },
          ]"
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
              @click="openDetail(order)"
            >
              <div class="kanban-card__header">
                <strong class="kanban-card__number">#{{ order.number }}</strong>
                <span class="kanban-card__date">{{ shortDate(order.placed_at) }}</span>
              </div>
              <div v-if="order.user_name" class="kanban-card__customer">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
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
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
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

      <!-- ===== TABLE VIEW ===== -->
      <div v-else class="admin-content-card">
        <div class="admin-content-card__header">
          <h3 class="admin-content-card__title">Listado de Pedidos</h3>
        </div>
        <div class="admin-content-card__body">
          <p v-if="ordersResponse.data.length === 0" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
            No hay pedidos registrados.
          </p>
          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Numero</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Total</th>
                <th>Fecha</th>
                <th>Items</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in ordersResponse.data" :key="order.id" style="cursor: pointer;" @click="openDetail(order)">
                <td><strong>#{{ order.number }}</strong></td>
                <td>{{ order.user_name ?? '—' }}</td>
                <td>
                  <span class="admin-badge" :class="statusBadgeClass(order.status)">
                    {{ order.status_label }}
                  </span>
                </td>
                <td>{{ money(order.totals.total, order.totals.currency) }}</td>
                <td>{{ formatDate(order.placed_at) }}</td>
                <td>{{ order.items.length }}</td>
                <td @click.stop>
                  <select
                    class="admin-btn admin-btn--sm"
                    :value="order.status"
                    @change="updateStatus(order, ($event.target as HTMLSelectElement).value)"
                  >
                    <option value="pending">Pendiente</option>
                    <option value="confirmed">Confirmado</option>
                    <option value="in_production">En produccion</option>
                    <option value="ready">Listo</option>
                    <option value="completed">Completado</option>
                    <option value="cancelled">Cancelado</option>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- ===== ORDER DETAIL MODAL ===== -->
    <Teleport to="body">
      <div v-if="selectedOrder" class="kanban-modal-overlay" @click.self="closeDetail">
        <div class="kanban-modal">
          <div class="kanban-modal__header">
            <div>
              <h2 class="kanban-modal__title">Pedido #{{ selectedOrder.number }}</h2>
              <span class="admin-badge" :class="statusBadgeClass(selectedOrder.status)">
                {{ selectedOrder.status_label }}
              </span>
            </div>
            <button class="kanban-modal__close" @click="closeDetail">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>

          <div class="kanban-modal__body">
            <!-- Customer info -->
            <div class="kanban-modal__section">
              <h4>Cliente</h4>
              <div class="kanban-modal__info-grid">
                <div>
                  <span class="kanban-modal__label">Nombre</span>
                  <span>{{ selectedOrder.user_name ?? '—' }}</span>
                </div>
                <div>
                  <span class="kanban-modal__label">Email</span>
                  <span>{{ selectedOrder.user_email ?? '—' }}</span>
                </div>
              </div>
            </div>

            <!-- Fulfillment -->
            <div class="kanban-modal__section">
              <h4>Entrega</h4>
              <div class="kanban-modal__info-grid">
                <div>
                  <span class="kanban-modal__label">Tipo</span>
                  <span>{{ selectedOrder.fulfillment?.type ?? '—' }}</span>
                </div>
                <div v-if="selectedOrder.fulfillment?.pickup_date">
                  <span class="kanban-modal__label">Fecha</span>
                  <span>{{ selectedOrder.fulfillment.pickup_date }}</span>
                </div>
                <div v-if="selectedOrder.fulfillment?.pickup_time">
                  <span class="kanban-modal__label">Hora</span>
                  <span>{{ selectedOrder.fulfillment.pickup_time }}</span>
                </div>
              </div>
            </div>

            <!-- Items -->
            <div class="kanban-modal__section">
              <h4>Productos</h4>
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, i) in selectedOrder.items" :key="i">
                    <td>{{ item.product_name }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ money(item.line_total, selectedOrder.totals.currency) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Totals -->
            <div class="kanban-modal__section">
              <div class="kanban-modal__totals">
                <div class="kanban-modal__total-row">
                  <span>Subtotal</span>
                  <span>{{ money(selectedOrder.totals.subtotal, selectedOrder.totals.currency) }}</span>
                </div>
                <div class="kanban-modal__total-row kanban-modal__total-row--grand">
                  <span>Total</span>
                  <span>{{ money(selectedOrder.totals.total, selectedOrder.totals.currency) }}</span>
                </div>
              </div>
            </div>

            <!-- Status change -->
            <div class="kanban-modal__section">
              <h4>Cambiar Estado</h4>
              <div class="kanban-modal__status-actions">
                <button
                  v-for="col in COLUMNS"
                  :key="col.status"
                  class="admin-btn admin-btn--sm"
                  :class="{
                    'admin-btn--primary': selectedOrder.status === col.status,
                    'admin-btn--outline': selectedOrder.status !== col.status,
                  }"
                  :disabled="selectedOrder.status === col.status"
                  @click="updateStatus(selectedOrder!, col.status)"
                >
                  {{ col.label }}
                </button>
              </div>
            </div>

            <!-- Meta -->
            <div class="kanban-modal__meta">
              <span>Creado: {{ formatDate(selectedOrder.placed_at) }}</span>
              <span v-if="selectedOrder.updated_at">Actualizado: {{ formatDate(selectedOrder.updated_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
/* ===== Kanban Board ===== */
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
  transition: box-shadow 0.2s ease, background 0.2s ease;
  border: 2px solid transparent;
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

.kanban-column--warning .kanban-column__title { color: #b37a00; }
.kanban-column--info .kanban-column__title { color: #3672c4; }
.kanban-column--success .kanban-column__title { color: #0a8c6a; }
.kanban-column--error .kanban-column__title { color: #d4543a; }

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
}

.kanban-column--warning .kanban-column__count { background: var(--admin-warning); }
.kanban-column--info .kanban-column__count { background: var(--admin-info); }
.kanban-column--success .kanban-column__count { background: var(--admin-success); }
.kanban-column--error .kanban-column__count { background: var(--admin-error); }

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

/* ===== Kanban Cards ===== */
.kanban-card {
  background: var(--admin-surface);
  border-radius: var(--admin-radius-sm);
  padding: 0.85rem;
  box-shadow: var(--admin-shadow);
  cursor: grab;
  transition: box-shadow 0.2s ease, transform 0.15s ease, opacity 0.2s ease;
  border: 1px solid var(--admin-border);
}

.kanban-card:hover {
  box-shadow: var(--admin-shadow-lg);
  transform: translateY(-1px);
}

.kanban-card:active {
  cursor: grabbing;
}

.kanban-card--dragging {
  opacity: 0.4;
  transform: rotate(2deg);
}

.kanban-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.kanban-card__number {
  font-size: 0.9rem;
  color: var(--admin-text);
}

.kanban-card__date {
  font-size: 0.7rem;
  color: var(--admin-text-muted);
  background: var(--admin-bg);
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.kanban-card__customer {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: var(--admin-text-secondary);
  margin-bottom: 0.5rem;
}

.kanban-card__items {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  margin-bottom: 0.65rem;
}

.kanban-card__item-tag {
  font-size: 0.7rem;
  background: var(--admin-primary-light);
  color: var(--admin-primary);
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  font-weight: 500;
  white-space: nowrap;
}

.kanban-card__item-tag--more {
  background: var(--admin-bg);
  color: var(--admin-text-muted);
}

.kanban-card__footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.kanban-card__total {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--admin-text);
}

.kanban-card__pickup {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.7rem;
  color: var(--admin-text-muted);
}

/* ===== Order Detail Modal ===== */
.kanban-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  animation: kanban-fade-in 0.2s ease;
}

.kanban-modal {
  background: var(--admin-surface);
  border-radius: var(--admin-radius);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  width: min(600px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  font-family: var(--admin-font);
  animation: kanban-slide-up 0.25s ease;
}

.kanban-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid var(--admin-border);
}

.kanban-modal__header > div {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.kanban-modal__title {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
  color: var(--admin-text);
}

.kanban-modal__close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.35rem;
  border-radius: 6px;
  color: var(--admin-text-muted);
  transition: all 0.15s;
}

.kanban-modal__close:hover {
  background: var(--admin-error-light);
  color: var(--admin-error);
}

.kanban-modal__body {
  padding: 1.5rem;
}

.kanban-modal__section {
  margin-bottom: 1.5rem;
}

.kanban-modal__section h4 {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 0.75rem;
}

.kanban-modal__info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.75rem;
}

.kanban-modal__info-grid > div {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.kanban-modal__label {
  font-size: 0.75rem;
  color: var(--admin-text-muted);
  font-weight: 500;
}

.kanban-modal__totals {
  background: var(--admin-bg);
  border-radius: var(--admin-radius-sm);
  padding: 1rem;
}

.kanban-modal__total-row {
  display: flex;
  justify-content: space-between;
  padding: 0.35rem 0;
  font-size: 0.9rem;
}

.kanban-modal__total-row--grand {
  border-top: 1px solid var(--admin-border);
  margin-top: 0.35rem;
  padding-top: 0.65rem;
  font-weight: 700;
  font-size: 1.05rem;
  color: var(--admin-primary);
}

.kanban-modal__status-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.kanban-modal__meta {
  display: flex;
  gap: 1.5rem;
  font-size: 0.75rem;
  color: var(--admin-text-muted);
  padding-top: 0.75rem;
  border-top: 1px solid var(--admin-border);
}

@keyframes kanban-fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes kanban-slide-up {
  from { opacity: 0; transform: translateY(20px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 768px) {
  .kanban-board {
    flex-direction: column;
  }
  .kanban-column {
    flex: none;
    min-width: 0;
  }
  .kanban-modal {
    width: 100%;
  }
}
</style>
