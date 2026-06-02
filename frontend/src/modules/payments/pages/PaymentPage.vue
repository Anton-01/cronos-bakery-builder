<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { paymentService } from '../services/paymentService'
import type { GatewayOption, GatewayType, Payment } from '../types'

const route = useRoute()
const orderId = route.params.id as string

const gateways = ref<GatewayOption[]>([])
const selected = ref<GatewayType | null>(null)
const payment = ref<Payment | null>(null)
const checkout = ref<Payment['checkout']>(null)
const loading = ref(true)
const processing = ref(false)
const error = ref<string | null>(null)

async function pay(): Promise<void> {
  if (!selected.value) return
  processing.value = true
  error.value = null
  try {
    const result = await paymentService.initiate(orderId, selected.value)
    payment.value = result.data
    checkout.value = result.checkout
  } catch {
    error.value = 'No se pudo iniciar el pago. Intenta con otro método.'
  } finally {
    processing.value = false
  }
}

onMounted(async () => {
  try {
    gateways.value = await paymentService.gateways()
    selected.value = gateways.value[0]?.gateway ?? null
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="payment">
    <h1>Pago del pedido</h1>

    <p v-if="loading" class="catalog__state">Cargando métodos de pago…</p>

    <template v-else-if="!payment">
      <p v-if="gateways.length === 0" class="catalog__state">
        No hay métodos de pago disponibles por el momento.
      </p>
      <form v-else class="payment__methods" @submit.prevent="pay">
        <label v-for="g in gateways" :key="g.gateway" class="payment__method">
          <input type="radio" :value="g.gateway" v-model="selected" />
          <span>{{ g.label }} <small>({{ g.mode }})</small></span>
        </label>

        <p v-if="error" class="auth-form__error">{{ error }}</p>
        <button type="submit" class="configurator__cta" :disabled="processing || !selected">
          {{ processing ? 'Iniciando…' : 'Pagar' }}
        </button>
      </form>
    </template>

    <!-- Payment initiated: surface the gateway's next action. -->
    <div v-else class="payment__result">
      <p>Pago <strong>{{ payment.status_label }}</strong> — referencia {{ payment.reference }}</p>

      <a v-if="checkout?.redirect_url" class="configurator__cta" :href="checkout.redirect_url">
        Continuar al proveedor de pago
      </a>
      <p v-else-if="checkout?.client_secret">
        Completa el pago de forma segura (client secret generado).
      </p>

      <p class="payment__note">
        Recibirás la confirmación automáticamente vía webhook del proveedor.
      </p>
      <RouterLink :to="{ name: 'orders.detail', params: { id: payment.order_id } }">
        Ver pedido
      </RouterLink>
    </div>
  </section>
</template>
