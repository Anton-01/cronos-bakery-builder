<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { builderService } from '../services/builderService'
import type { ConfigurableProduct } from '../types'

const products = ref<ConfigurableProduct[]>([])
const loading = ref(true)

function formatMoney(amount: number, currency: string): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

onMounted(async () => {
  try {
    products.value = await builderService.products()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="product-list">
    <h1>Arma tu pastel</h1>
    <p v-if="loading">Cargando…</p>

    <div v-else class="product-list__grid">
      <RouterLink
        v-for="product in products"
        :key="product.id"
        :to="{ name: 'builder.configure', params: { slug: product.slug } }"
        class="product-list__card"
      >
        <img v-if="product.image" :src="product.image" :alt="product.name" />
        <h3>{{ product.name }}</h3>
        <p class="product-list__price">
          desde {{ formatMoney(product.base_price.amount, product.base_price.currency) }}
        </p>
      </RouterLink>
    </div>
  </section>
</template>
