<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminPanelService, type AdminOrder, type Paginated } from '../services/adminPanelService'

const ordersResponse = ref<Paginated<AdminOrder> | null>(null)
const loading = ref(true)

function money(amount: number, currency = 'CRC'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateStr))
}

function statusBadgeClass(status: string): Record<string, boolean> {
  return {
    'admin-badge--warning': status === 'pending',
    'admin-badge--info': status === 'confirmed' || status === 'in_production',
    'admin-badge--success': status === 'ready' || status === 'completed',
    'admin-badge--error': status === 'cancelled',
  }
}

async function load(): Promise<void> {
  loading.value = true
  try {
    ordersResponse.value = await adminPanelService.adminOrders()
  } finally {
    loading.value = false
  }
}

async function updateStatus(order: AdminOrder, status: string): Promise<void> {
  const updated = await adminPanelService.updateOrderStatus(order.id, status)
  if (ordersResponse.value) {
    const idx = ordersResponse.value.data.findIndex((o) => o.id === order.id)
    if (idx !== -1) {
      ordersResponse.value.data[idx] = updated
    }
  }
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
    </div>

    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Listado de Pedidos</h3>
      </div>
      <div class="admin-content-card__body">
        <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
          Cargando pedidos...
        </p>

        <template v-else-if="ordersResponse">
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
              <tr v-for="order in ordersResponse.data" :key="order.id">
                <td><strong>#{{ order.number }}</strong></td>
                <td>{{ order.user_name ?? '—' }}</td>
                <td>
                  <span class="admin-badge" :class="statusBadgeClass(order.status)">
                    {{ order.status_label }}
                  </span>
                </td>
                <td>{{ money(order.totals.total, order.totals.currency) }}</td>
                <td>{{ formatDate(order.placed_at) }}</td>
                <td>{{ order.items.length }} {{ order.items.length === 1 ? 'item' : 'items' }}</td>
                <td>
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
        </template>
      </div>
    </div>
  </div>
</template>
