<script setup lang="ts">
import { RouterLink } from 'vue-router'

import type { CatalogProduct } from '../types'

const props = defineProps<{ product: CatalogProduct }>()

function formatMoney(): string {
  return new Intl.NumberFormat('es-CR', {
    style: 'currency',
    currency: props.product.price.currency,
  }).format(props.product.price.amount / 100)
}
</script>

<template>
  <RouterLink :to="`/pastel/${product.slug}`" class="product-card">
    <img v-if="product.image" :src="product.image" :alt="product.name" />
    <div v-else class="product-card__placeholder"></div>
    <h3>{{ product.name }}</h3>
    <p class="product-card__price">{{ formatMoney() }}</p>
  </RouterLink>
</template>
