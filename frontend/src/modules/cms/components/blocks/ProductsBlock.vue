<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { catalogService } from '@/modules/catalog/services/catalogService'
import type { CatalogProduct } from '@/modules/catalog/types'

/**
 * Dynamic bakery-products block: fetches live catalog products according to
 * the block configuration (latest, featured, by category or manual picks).
 */
const props = defineProps<{ config: Record<string, any> }>()

const products = ref<CatalogProduct[]>([])
const loading = ref(true)

function formatPrice(product: CatalogProduct): string {
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: product.price.currency,
  }).format(product.price.amount / 100)
}

async function load(): Promise<void> {
  loading.value = true
  try {
    const limit = Number(props.config.limit) || 8
    const source = String(props.config.source ?? 'latest')

    const filter: Record<string, unknown> = {}
    if (source === 'category' && props.config.category_slug) {
      filter.category = props.config.category_slug
    }
    if (source === 'featured') {
      filter.collection = 'featured'
    }

    const result = await catalogService.browse(filter)
    let items = result.data

    if (source === 'manual' && Array.isArray(props.config.product_ids)) {
      const wanted = (props.config.product_ids as string[]).map(String)
      items = items.filter((product) => wanted.includes(String(product.id)))
    }

    products.value = items.slice(0, limit)
  } catch {
    products.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="block block--products">
    <h2 v-if="config.title">{{ config.title }}</h2>
    <p v-if="loading" class="block--products__state">Cargando productos…</p>
    <div v-else-if="products.length" class="block--products__grid">
      <a v-for="product in products" :key="product.id" class="product-card" :href="product.url">
        <img v-if="product.image" :src="product.image" :alt="product.name" />
        <div class="product-card__body">
          <h3>{{ product.name }}</h3>
          <span v-if="config.show_price !== false" class="product-card__price">
            {{ formatPrice(product) }}
          </span>
        </div>
      </a>
    </div>
    <p v-else class="block--products__state">No hay productos disponibles.</p>
  </section>
</template>

<style scoped>
.block--products {
  padding: 3rem 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}
.block--products h2 {
  text-align: center;
  margin-bottom: 2rem;
}
.block--products__state {
  text-align: center;
  color: #777;
}
.block--products__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.5rem;
}
.product-card {
  display: block;
  border: 1px solid #eee;
  border-radius: 12px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: box-shadow 0.15s ease;
}
.product-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
}
.product-card img {
  width: 100%;
  aspect-ratio: 4 / 3;
  object-fit: cover;
  display: block;
}
.product-card__body {
  padding: 0.875rem 1rem;
}
.product-card__body h3 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
}
.product-card__price {
  font-weight: 700;
  font-size: 0.9rem;
}
</style>
