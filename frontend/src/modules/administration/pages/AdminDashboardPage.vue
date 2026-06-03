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
  <section class="admin-page">
    <h1>Dashboard</h1>
    <p v-if="loading">Cargando métricas…</p>

    <div v-else-if="metrics" class="admin-cards">
      <article class="admin-card">
        <h3>Ventas</h3>
        <p class="admin-card__value">{{ money(metrics.sales.revenue, metrics.sales.currency) }}</p>
        <small>{{ metrics.sales.paid_payments }} pagos · ticket prom.
          {{ money(metrics.sales.average_order_value, metrics.sales.currency) }}</small>
      </article>

      <article class="admin-card">
        <h3>Pedidos</h3>
        <p class="admin-card__value">{{ metrics.orders.total }}</p>
        <small>
          <span v-for="(count, status) in metrics.orders.by_status" :key="status">
            {{ status }}: {{ count }} ·
          </span>
        </small>
      </article>

      <article class="admin-card">
        <h3>Producción</h3>
        <p class="admin-card__value">{{ metrics.production.in_production }}</p>
        <small>{{ metrics.production.ready }} listos · {{ metrics.production.upcoming_pickups }} recolecciones próximas</small>
      </article>

      <article class="admin-card">
        <h3>Conversión</h3>
        <p class="admin-card__value">{{ percent(metrics.conversion.cart_to_order_rate) }}</p>
        <small>carrito → pedido · pago: {{ percent(metrics.conversion.order_to_paid_rate) }}</small>
      </article>

      <article class="admin-card">
        <h3>Clientes</h3>
        <p class="admin-card__value">{{ metrics.customers.total }}</p>
        <small>{{ metrics.customers.new }} nuevos · {{ metrics.customers.with_orders }} con pedidos</small>
      </article>
    </div>
  </section>
</template>
