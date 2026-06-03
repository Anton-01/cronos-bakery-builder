<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { orderService } from '../services/orderService'
import type { Order } from '../types'

const orders = ref<Order[]>([])
const loading = ref(true)

function money(amount: number, currency: string): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

onMounted(async () => {
  try {
    orders.value = await orderService.orders()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="orders">
    <h1>Mis pedidos</h1>

    <p v-if="loading" class="catalog__state">Cargando…</p>
    <p v-else-if="orders.length === 0" class="catalog__state">Aún no tienes pedidos.</p>

    <ul v-else class="orders__list">
      <li v-for="order in orders" :key="order.id" class="orders__item">
        <RouterLink :to="{ name: 'orders.detail', params: { id: order.id } }">
          <div>
            <strong>{{ order.number }}</strong>
            <span class="orders__status">{{ order.status_label }}</span>
          </div>
          <div>
            <span>{{ order.fulfillment.type_label }}</span>
            <span>{{ money(order.totals.total, order.totals.currency) }}</span>
          </div>
        </RouterLink>
      </li>
    </ul>
  </section>
</template>
