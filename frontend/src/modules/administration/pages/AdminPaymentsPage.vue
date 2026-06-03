<script setup lang="ts">
import { onMounted, ref } from 'vue'

import {
  adminPanelService,
  type AdminPayment,
  type PaymentGateway,
  type Paginated,
} from '../services/adminPanelService'

type Tab = 'payments' | 'gateways'

const activeTab = ref<Tab>('payments')

// --- Payments tab ---
const paymentsResponse = ref<Paginated<AdminPayment> | null>(null)
const loadingPayments = ref(false)
const retryingId = ref<string | null>(null)

// --- Gateways tab ---
const gateways = ref<PaymentGateway[]>([])
const loadingGateways = ref(false)
const togglingId = ref<string | null>(null)

function money(amount: number, currency = 'CRC'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(
    new Date(dateStr),
  )
}

function paymentStatusClass(status: string): Record<string, boolean> {
  return {
    'admin-badge--success': status === 'completed' || status === 'paid',
    'admin-badge--warning': status === 'pending',
    'admin-badge--error': status === 'failed',
  }
}

function gatewayStatusClass(isActive: boolean): Record<string, boolean> {
  return {
    'admin-badge--success': isActive,
    'admin-badge--error': !isActive,
  }
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
      if (idx !== -1) {
        paymentsResponse.value.data[idx] = updated
      }
    }
  } finally {
    retryingId.value = null
  }
}

async function toggleGateway(gw: PaymentGateway): Promise<void> {
  togglingId.value = gw.id
  try {
    const updated = await adminPanelService.updateGateway(gw.id, { is_active: !gw.is_active })
    const idx = gateways.value.findIndex((g) => g.id === gw.id)
    if (idx !== -1) {
      gateways.value[idx] = updated
    }
  } finally {
    togglingId.value = null
  }
}

function selectTab(tab: Tab): void {
  activeTab.value = tab
  if (tab === 'payments' && paymentsResponse.value === null) {
    loadPayments()
  } else if (tab === 'gateways' && gateways.value.length === 0) {
    loadGateways()
  }
}

onMounted(() => {
  loadPayments()
})
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Pagos</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Finanzas <span>/</span> Pagos
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs">
      <button
        class="admin-tab"
        :class="{ 'admin-tab--active': activeTab === 'payments' }"
        @click="selectTab('payments')"
      >
        Pagos
      </button>
      <button
        class="admin-tab"
        :class="{ 'admin-tab--active': activeTab === 'gateways' }"
        @click="selectTab('gateways')"
      >
        Pasarelas
      </button>
    </div>

    <!-- Payments tab -->
    <div v-if="activeTab === 'payments'" class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Listado de Pagos</h3>
      </div>
      <div class="admin-content-card__body">
        <p
          v-if="loadingPayments"
          style="text-align: center; padding: 2rem; color: var(--admin-text-muted)"
        >
          Cargando pagos...
        </p>

        <template v-else-if="paymentsResponse">
          <p
            v-if="paymentsResponse.data.length === 0"
            style="text-align: center; padding: 2rem; color: var(--admin-text-muted)"
          >
            No hay pagos registrados.
          </p>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Pedido</th>
                <th>Pasarela</th>
                <th>Estado</th>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in paymentsResponse.data" :key="payment.id">
                <td>
                  <strong>{{ payment.order_number ? `#${payment.order_number}` : payment.order_id }}</strong>
                </td>
                <td>{{ payment.gateway }}</td>
                <td>
                  <span class="admin-badge" :class="paymentStatusClass(payment.status)">
                    {{ payment.status }}
                  </span>
                </td>
                <td>{{ money(payment.amount, payment.currency) }}</td>
                <td>{{ formatDate(payment.created_at) }}</td>
                <td>
                  <button
                    v-if="payment.status === 'failed'"
                    class="admin-btn admin-btn--sm"
                    :disabled="retryingId === payment.id"
                    @click="retryPayment(payment)"
                  >
                    {{ retryingId === payment.id ? 'Reintentando...' : 'Reintentar' }}
                  </button>
                  <span v-else style="color: var(--admin-text-muted)">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
      </div>
    </div>

    <!-- Gateways tab -->
    <div v-if="activeTab === 'gateways'" class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Pasarelas de Pago</h3>
      </div>
      <div class="admin-content-card__body">
        <p
          v-if="loadingGateways"
          style="text-align: center; padding: 2rem; color: var(--admin-text-muted)"
        >
          Cargando pasarelas...
        </p>

        <template v-else>
          <p
            v-if="gateways.length === 0"
            style="text-align: center; padding: 2rem; color: var(--admin-text-muted)"
          >
            No hay pasarelas configuradas.
          </p>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Driver</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="gw in gateways" :key="gw.id">
                <td><strong>{{ gw.name }}</strong></td>
                <td>{{ gw.driver }}</td>
                <td>
                  <span class="admin-badge" :class="gatewayStatusClass(gw.is_active)">
                    {{ gw.is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td>
                  <button
                    class="admin-btn admin-btn--sm"
                    :disabled="togglingId === gw.id"
                    @click="toggleGateway(gw)"
                  >
                    {{ togglingId === gw.id
                      ? 'Guardando...'
                      : gw.is_active
                        ? 'Desactivar'
                        : 'Activar' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.admin-tab {
  padding: 0.5rem 1rem;
  border: 1px solid var(--admin-border);
  border-radius: 6px;
  background: white;
  cursor: pointer;
  font-size: 0.875rem;
}

.admin-tab--active {
  background: var(--admin-primary);
  color: white;
  border-color: var(--admin-primary);
}
</style>
