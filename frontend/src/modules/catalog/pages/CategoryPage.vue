<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import Breadcrumbs from '../components/Breadcrumbs.vue'
import FilterSidebar from '../components/FilterSidebar.vue'
import ProductCard from '../components/ProductCard.vue'
import { catalogService } from '../services/catalogService'
import type { Breadcrumb, CatalogProduct, Category, Facets, FilterState, Paginated } from '../types'

const route = useRoute()

const facets = ref<Facets | null>(null)
const category = ref<Category | null>(null)
const breadcrumbs = ref<Breadcrumb[]>([])
const result = ref<Paginated<CatalogProduct> | null>(null)
const loading = ref(true)
const notFound = ref(false)

const filter = reactive<FilterState>({ attributes: {}, sort: 'position', page: 1 })

function applySeo(c: Category): void {
  document.title = c.seo.meta_title || c.name
  if (c.seo.meta_description) {
    let tag = document.querySelector('meta[name="description"]')
    if (!tag) {
      tag = document.createElement('meta')
      tag.setAttribute('name', 'description')
      document.head.appendChild(tag)
    }
    tag.setAttribute('content', c.seo.meta_description)
  }
}

async function load(): Promise<void> {
  loading.value = true
  notFound.value = false
  try {
    const data = await catalogService.category(route.params.slug as string, filter)
    category.value = data.category
    breadcrumbs.value = data.breadcrumbs
    result.value = data.products
    applySeo(data.category)
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

function goToPage(page: number): void {
  filter.page = page
  load()
}

onMounted(async () => {
  facets.value = await catalogService.facets()
  await load()
})

watch(() => route.params.slug, () => {
  filter.page = 1
  load()
})
</script>

<template>
  <section class="catalog">
    <p v-if="notFound" class="catalog__state">Categoría no encontrada.</p>

    <template v-else-if="category">
      <Breadcrumbs :items="breadcrumbs" />
      <h1>{{ category.name }}</h1>
      <p v-if="category.description">{{ category.description }}</p>

      <div class="catalog__layout">
        <FilterSidebar :facets="facets" :filter="filter" hide-categories @change="load" />

        <div class="catalog__main">
          <div class="catalog__toolbar">
            <span v-if="result">{{ result.meta.total }} productos</span>
          </div>

          <p v-if="loading" class="catalog__state">Cargando…</p>
          <div v-else-if="result" class="catalog__grid">
            <ProductCard v-for="product in result.data" :key="product.id" :product="product" />
          </div>

          <div v-if="result && result.meta.last_page > 1" class="catalog__pagination">
            <button :disabled="result.meta.current_page <= 1" @click="goToPage(result.meta.current_page - 1)">
              Anterior
            </button>
            <span>{{ result.meta.current_page }} / {{ result.meta.last_page }}</span>
            <button :disabled="result.meta.current_page >= result.meta.last_page"
              @click="goToPage(result.meta.current_page + 1)">
              Siguiente
            </button>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>
