<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

import { orderService } from '../services/orderService'
import type { Branch, Order } from '../types'

const route = useRoute()
const order = ref<Order | null>(null)
const loading = ref(true)

function money(amount: number, currency: string): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function branchName(order: Order): string | null {
  const b = order.fulfillment.branch as { data?: Branch } | Branch | null
  if (!b) return null
  return 'data' in b ? (b.data?.name ?? null) : (b as Branch).name
}

onMounted(async () => {
  try {
    order.value = await orderService.order(route.params.id as string)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="order-detail">
    <p v-if="loading" class="catalog__state">Cargando…</p>

    <template v-else-if="order">
      <h1>Pedido {{ order.number }}</h1>
      <p><strong>Estado:</strong> {{ order.status_label }}</p>
      <p><strong>Entrega:</strong> {{ order.fulfillment.type_label }}</p>

      <p v-if="order.fulfillment.type === 'pickup'">
        Sucursal: {{ branchName(order) }} — {{ order.fulfillment.pickup_date }} {{ order.fulfillment.pickup_time }}
      </p>
      <p v-else-if="order.fulfillment.shipping_address">
        Envío a: {{ (order.fulfillment.shipping_address as Record<string, string>).line1 }},
        {{ (order.fulfillment.shipping_address as Record<string, string>).city }}
      </p>

      <h2>Artículos</h2>
      <ul class="order-detail__items">
        <li v-for="item in order.items" :key="item.id">
          <span>{{ item.quantity }}× {{ item.product_name }}</span>
          <span>{{ money(item.line_total, order.totals.currency) }}</span>
        </li>
      </ul>

      <p class="configurator__total">
        <strong>Total</strong>
        <strong>{{ money(order.totals.total, order.totals.currency) }}</strong>
      </p>
    </template>
  </section>
</template>
