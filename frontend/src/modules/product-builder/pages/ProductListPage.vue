<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { builderService } from '../services/builderService'
import type { ConfigurableProduct } from '../types'

const products = ref<ConfigurableProduct[]>([])
const loading = ref(true)

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
    <h1>Arma tu Pastel</h1>
    <p v-if="loading" class="configurator__state">Cargando...</p>

    <div v-else class="product-list__grid">
      <RouterLink
        v-for="product in products"
        :key="product.id"
        :to="{ name: 'builder.configure', params: { slug: product.slug } }"
        class="product-list__card"
      >
        <img v-if="product.image" :src="product.image" :alt="product.name" />
        <div v-else class="product-list__card-placeholder"></div>
        <div class="product-list__card-content">
          <div class="product-list__card-label">{{ product.name }}</div>
        </div>
      </RouterLink>
    </div>
  </section>
</template>

<style scoped>
.product-list__card-placeholder {
  width: 100%;
  aspect-ratio: 4/3;
  background: var(--color-surface-warm);
}
</style>
