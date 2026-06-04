<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminPanelService, type DashboardMetrics } from '../services/adminPanelService'

const metrics = ref<DashboardMetrics | null>(null)
const loading = ref(true)

function money(amount: number, currency = 'USD'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function percent(rate: number): string {
  return `${(rate * 100).toFixed(1)}%`
}

onMounted(async () => {
  try {
    metrics.value = await adminPanelService.dashboard()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Dashboard</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Dashboard
        </div>
      </div>
    </div>

    <p v-if="loading" style="text-align:center; padding: 2rem; color: var(--admin-text-muted);">Cargando metricas...</p>

    <template v-else-if="metrics">
      <!-- Top metric cards -->
      <div class="admin-metrics">
        <article class="admin-metric-card">
          <div class="admin-metric-card__icon admin-metric-card__icon--blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <div class="admin-metric-card__body">
            <div class="admin-metric-card__label">Ventas</div>
            <div class="admin-metric-card__value">{{ money(metrics.sales.revenue, metrics.sales.currency) }}</div>
            <div class="admin-metric-card__sub">{{ metrics.sales.paid_payments }} pagos realizados</div>
          </div>
        </article>

        <article class="admin-metric-card">
          <div class="admin-metric-card__icon admin-metric-card__icon--orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
          </div>
          <div class="admin-metric-card__body">
            <div class="admin-metric-card__label">Pedidos</div>
            <div class="admin-metric-card__value">{{ metrics.orders.total }}</div>
            <div class="admin-metric-card__sub">
              <span v-for="(count, status) in metrics.orders.by_status" :key="status" style="margin-right: 0.5rem;">
                {{ status }}: {{ count }}
              </span>
            </div>
          </div>
        </article>

        <article class="admin-metric-card">
          <div class="admin-metric-card__icon admin-metric-card__icon--green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          </div>
          <div class="admin-metric-card__body">
            <div class="admin-metric-card__label">Produccion</div>
            <div class="admin-metric-card__value">{{ metrics.production.in_production }}</div>
            <div class="admin-metric-card__sub">{{ metrics.production.ready }} listos · {{ metrics.production.upcoming_pickups }} entregas prox.</div>
          </div>
        </article>

        <article class="admin-metric-card">
          <div class="admin-metric-card__icon admin-metric-card__icon--red">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
          <div class="admin-metric-card__body">
            <div class="admin-metric-card__label">Conversion</div>
            <div class="admin-metric-card__value">{{ percent(metrics.conversion.cart_to_order_rate) }}</div>
            <div class="admin-metric-card__sub">carrito a pedido · pago: {{ percent(metrics.conversion.order_to_paid_rate) }}</div>
          </div>
        </article>

        <article class="admin-metric-card">
          <div class="admin-metric-card__icon admin-metric-card__icon--info">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          </div>
          <div class="admin-metric-card__body">
            <div class="admin-metric-card__label">Clientes</div>
            <div class="admin-metric-card__value">{{ metrics.customers.total }}</div>
            <div class="admin-metric-card__sub">{{ metrics.customers.new }} nuevos · {{ metrics.customers.with_orders }} con pedidos</div>
          </div>
        </article>
      </div>

      <!-- Dashboard grid: detailed panels -->
      <div class="admin-dashboard-grid">
        <div class="admin-content-card">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Resumen de Pedidos</h3>
          </div>
          <div class="admin-content-card__body">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Estado</th>
                  <th>Cantidad</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(count, status) in metrics.orders.by_status" :key="status">
                  <td>
                    <span class="admin-badge" :class="{
                      'admin-badge--warning': status === 'pending',
                      'admin-badge--info': status === 'confirmed' || status === 'in_production',
                      'admin-badge--success': status === 'ready' || status === 'completed',
                      'admin-badge--error': status === 'cancelled',
                      'admin-badge--default': !['pending','confirmed','in_production','ready','completed','cancelled'].includes(status as string),
                    }">
                      {{ status }}
                    </span>
                  </td>
                  <td><strong>{{ count }}</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <div class="admin-content-card" style="margin-bottom: 1.5rem;">
            <div class="admin-content-card__header">
              <h3 class="admin-content-card__title">Ticket Promedio</h3>
            </div>
            <div class="admin-content-card__body" style="text-align: center;">
              <div style="font-size: 2rem; font-weight: 700; color: var(--admin-primary); margin-bottom: 0.25rem;">
                {{ money(metrics.sales.average_order_value, metrics.sales.currency) }}
              </div>
              <div style="font-size: 0.85rem; color: var(--admin-text-muted);">por pedido pagado</div>
            </div>
          </div>

          <div class="admin-content-card">
            <div class="admin-content-card__header">
              <h3 class="admin-content-card__title">Actividad Reciente</h3>
            </div>
            <div class="admin-content-card__body">
              <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--admin-success); flex-shrink: 0;"></div>
                  <div>
                    <div style="font-size: 0.85rem; font-weight: 500;">{{ metrics.production.ready }} pedidos listos para entrega</div>
                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">Produccion completada</div>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--admin-warning); flex-shrink: 0;"></div>
                  <div>
                    <div style="font-size: 0.85rem; font-weight: 500;">{{ metrics.production.in_production }} en produccion</div>
                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">En proceso actualmente</div>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--admin-info); flex-shrink: 0;"></div>
                  <div>
                    <div style="font-size: 0.85rem; font-weight: 500;">{{ metrics.production.upcoming_pickups }} entregas proximas</div>
                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">Proximos 7 dias</div>
                  </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--admin-primary); flex-shrink: 0;"></div>
                  <div>
                    <div style="font-size: 0.85rem; font-weight: 500;">{{ metrics.customers.new }} clientes nuevos</div>
                    <div style="font-size: 0.75rem; color: var(--admin-text-muted);">En el periodo actual</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
