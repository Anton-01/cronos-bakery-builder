<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import Breadcrumbs from '../components/Breadcrumbs.vue'
import { catalogService } from '../services/catalogService'
import type { Breadcrumb, CatalogProduct } from '../types'

const route = useRoute()
const product = ref<CatalogProduct | null>(null)
const breadcrumbs = ref<Breadcrumb[]>([])
const loading = ref(true)
const notFound = ref(false)

function applySeo(p: CatalogProduct): void {
  document.title = p.seo.meta_title || p.name
  if (p.seo.meta_description) {
    let tag = document.querySelector('meta[name="description"]')
    if (!tag) {
      tag = document.createElement('meta')
      tag.setAttribute('name', 'description')
      document.head.appendChild(tag)
    }
    tag.setAttribute('content', p.seo.meta_description)
  }
}

function formatMoney(p: CatalogProduct): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency: p.price.currency }).format(
    p.price.amount / 100,
  )
}

async function load(): Promise<void> {
  loading.value = true
  notFound.value = false
  try {
    const data = await catalogService.product(route.params.slug as string)
    product.value = data.product
    breadcrumbs.value = data.breadcrumbs
    applySeo(data.product)
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => route.params.slug, load)
</script>

<template>
  <section class="product-detail">
    <p v-if="loading" class="catalog__state">Cargando…</p>
    <p v-else-if="notFound" class="catalog__state">Producto no encontrado.</p>

    <template v-else-if="product">
      <Breadcrumbs :items="breadcrumbs" />
      <div class="product-detail__grid">
        <img v-if="product.image" :src="product.image" :alt="product.name" class="product-detail__image" />
        <div class="product-detail__info">
          <h1>{{ product.name }}</h1>
          <p class="product-detail__price">{{ formatMoney(product) }}</p>
          <p v-if="product.description">{{ product.description }}</p>

          <ul v-if="product.attributes?.length" class="product-detail__attrs">
            <li v-for="(attr, i) in product.attributes" :key="i">
              <strong>{{ attr.attribute_name }}:</strong> {{ attr.label }}
            </li>
          </ul>

          <RouterLink class="product-detail__cta" :to="`/builder/${product.slug}`">
            Personalizar este pastel
          </RouterLink>
        </div>
      </div>
    </template>
  </section>
</template>
