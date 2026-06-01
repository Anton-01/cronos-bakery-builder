<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import Breadcrumbs from '../components/Breadcrumbs.vue'
import FilterSidebar from '../components/FilterSidebar.vue'
import ProductCard from '../components/ProductCard.vue'
import { catalogService } from '../services/catalogService'
import type { CatalogProduct, Facets, FilterState, Paginated } from '../types'

const facets = ref<Facets | null>(null)
const result = ref<Paginated<CatalogProduct> | null>(null)
const loading = ref(true)

const filter = reactive<FilterState>({ attributes: {}, sort: 'position', page: 1 })

const sorts = [
  { value: 'position', label: 'Destacados' },
  { value: 'price_asc', label: 'Precio: menor a mayor' },
  { value: 'price_desc', label: 'Precio: mayor a menor' },
  { value: 'name', label: 'Nombre' },
  { value: 'newest', label: 'Más recientes' },
]

async function load(): Promise<void> {
  loading.value = true
  try {
    result.value = await catalogService.browse(filter)
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
</script>

<template>
  <section class="catalog">
    <Breadcrumbs :items="[{ label: 'Catálogo', slug: '', type: 'catalog' }]" />
    <h1>Catálogo</h1>

    <div class="catalog__layout">
      <FilterSidebar :facets="facets" :filter="filter" @change="load" />

      <div class="catalog__main">
        <div class="catalog__toolbar">
          <span v-if="result">{{ result.meta.total }} productos</span>
          <select v-model="filter.sort" @change="goToPage(1)">
            <option v-for="s in sorts" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
        </div>

        <p v-if="loading" class="catalog__state">Cargando…</p>
        <p v-else-if="result && result.data.length === 0" class="catalog__state">
          No hay productos que coincidan con los filtros.
        </p>

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
  </section>
</template>
