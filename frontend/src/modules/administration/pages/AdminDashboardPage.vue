<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import ProgressSpinner from 'primevue/progressspinner'

import { adminPanelService, type DashboardMetrics } from '../services/adminPanelService'

const metrics = ref<DashboardMetrics | null>(null)
const loading = ref(true)

function money(amount: number, currency = 'USD'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function percent(rate: number): string {
  return `${(rate * 100).toFixed(1)}%`
}

function statusSeverity(status: string): 'success' | 'warn' | 'info' | 'danger' | 'secondary' {
  const map: Record<string, 'success' | 'warn' | 'info' | 'danger' | 'secondary'> = {
    pending: 'warn',
    confirmed: 'info',
    in_production: 'info',
    ready: 'success',
    completed: 'success',
    cancelled: 'danger',
  }
  return map[status] ?? 'secondary'
}

const orderRows = ref<{ status: string; count: number }[]>([])
const activityItems = ref<{ color: string; title: string; subtitle: string }[]>([])

onMounted(async () => {
  try {
    metrics.value = await adminPanelService.dashboard()
    if (metrics.value) {
      orderRows.value = Object.entries(metrics.value.orders.by_status).map(([status, count]) => ({ status, count }))
      activityItems.value = [
        { color: 'var(--admin-success)', title: `${metrics.value.production.ready} pedidos listos para entrega`, subtitle: 'Producción completada' },
        { color: 'var(--admin-warning)', title: `${metrics.value.production.in_production} en producción`, subtitle: 'En proceso actualmente' },
        { color: 'var(--admin-info)', title: `${metrics.value.production.upcoming_pickups} entregas próximas`, subtitle: 'Próximos 7 días' },
        { color: 'var(--admin-primary)', title: `${metrics.value.customers.new} clientes nuevos`, subtitle: 'En el período actual' },
      ]
    }
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Dashboard</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Dashboard</div>
      </div>
    </div>

    <div v-if="loading" style="display:flex; justify-content:center; padding: 4rem;">
      <ProgressSpinner />
    </div>

    <template v-else-if="metrics">
      <!-- Metric cards -->
      <div class="admin-metrics">
        <Card class="metric-card">
          <template #content>
            <div class="metric-card__inner">
              <div class="metric-card__icon metric-card__icon--blue"><i class="pi pi-dollar" style="font-size:1.5rem;"></i></div>
              <div>
                <div class="metric-card__label">Ventas</div>
                <div class="metric-card__value">{{ money(metrics.sales.revenue, metrics.sales.currency) }}</div>
                <div class="metric-card__sub">{{ metrics.sales.paid_payments }} pagos realizados</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="metric-card">
          <template #content>
            <div class="metric-card__inner">
              <div class="metric-card__icon metric-card__icon--orange"><i class="pi pi-shopping-bag" style="font-size:1.5rem;"></i></div>
              <div>
                <div class="metric-card__label">Pedidos</div>
                <div class="metric-card__value">{{ metrics.orders.total }}</div>
                <div class="metric-card__sub">
                  <span v-for="(count, status) in metrics.orders.by_status" :key="String(status)" style="margin-right:0.5rem;">
                    {{ status }}: {{ count }}
                  </span>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="metric-card">
          <template #content>
            <div class="metric-card__inner">
              <div class="metric-card__icon metric-card__icon--green"><i class="pi pi-box" style="font-size:1.5rem;"></i></div>
              <div>
                <div class="metric-card__label">Producción</div>
                <div class="metric-card__value">{{ metrics.production.in_production }}</div>
                <div class="metric-card__sub">{{ metrics.production.ready }} listos · {{ metrics.production.upcoming_pickups }} entregas próx.</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="metric-card">
          <template #content>
            <div class="metric-card__inner">
              <div class="metric-card__icon metric-card__icon--red"><i class="pi pi-chart-line" style="font-size:1.5rem;"></i></div>
              <div>
                <div class="metric-card__label">Conversión</div>
                <div class="metric-card__value">{{ percent(metrics.conversion.cart_to_order_rate) }}</div>
                <div class="metric-card__sub">carrito → pedido · pago: {{ percent(metrics.conversion.order_to_paid_rate) }}</div>
              </div>
            </div>
          </template>
        </Card>

        <Card class="metric-card">
          <template #content>
            <div class="metric-card__inner">
              <div class="metric-card__icon metric-card__icon--info"><i class="pi pi-users" style="font-size:1.5rem;"></i></div>
              <div>
                <div class="metric-card__label">Clientes</div>
                <div class="metric-card__value">{{ metrics.customers.total }}</div>
                <div class="metric-card__sub">{{ metrics.customers.new }} nuevos · {{ metrics.customers.with_orders }} con pedidos</div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Dashboard grid -->
      <div class="admin-dashboard-grid">
        <Card>
          <template #title>Resumen de Pedidos</template>
          <template #content>
            <DataTable :value="orderRows" class="p-datatable-sm">
              <Column field="status" header="Estado">
                <template #body="{ data }">
                  <Tag :value="data.status" :severity="statusSeverity(data.status)" />
                </template>
              </Column>
              <Column field="count" header="Cantidad">
                <template #body="{ data }"><strong>{{ data.count }}</strong></template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <div style="display:flex; flex-direction:column; gap:1.5rem;">
          <Card>
            <template #title>Ticket Promedio</template>
            <template #content>
              <div style="text-align:center;">
                <div style="font-size:2rem; font-weight:700; color:var(--admin-primary); margin-bottom:0.25rem;">
                  {{ money(metrics.sales.average_order_value, metrics.sales.currency) }}
                </div>
                <div style="font-size:0.85rem; color:var(--admin-text-muted);">por pedido pagado</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #title>Actividad Reciente</template>
            <template #content>
              <div style="display:flex; flex-direction:column; gap:0.75rem;">
                <div v-for="item in activityItems" :key="item.title" style="display:flex; align-items:center; gap:0.75rem;">
                  <div :style="{ width: '8px', height: '8px', borderRadius: '50%', background: item.color, flexShrink: 0 }"></div>
                  <div>
                    <div style="font-size:0.85rem; font-weight:500;">{{ item.title }}</div>
                    <div style="font-size:0.75rem; color:var(--admin-text-muted);">{{ item.subtitle }}</div>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.metric-card .metric-card__inner {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}
.metric-card__icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.metric-card__icon--blue { background: var(--admin-primary-light); color: var(--admin-primary); }
.metric-card__icon--green { background: var(--admin-success-light); color: var(--admin-success); }
.metric-card__icon--orange { background: var(--admin-warning-light); color: var(--admin-warning); }
.metric-card__icon--red { background: var(--admin-error-light); color: var(--admin-error); }
.metric-card__icon--info { background: var(--admin-info-light); color: var(--admin-info); }
.metric-card__label { font-size: 0.8rem; font-weight: 500; color: var(--admin-text-secondary); margin-bottom: 0.25rem; }
.metric-card__value { font-size: 1.5rem; font-weight: 700; color: var(--admin-text); line-height: 1.2; margin-bottom: 0.25rem; }
.metric-card__sub { font-size: 0.75rem; color: var(--admin-text-muted); }
</style>
