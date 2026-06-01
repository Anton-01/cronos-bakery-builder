<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import OptionField from '../components/OptionField.vue'
import { resolveVisibleKeys } from '../dependencies'
import { builderService } from '../services/builderService'
import type { ConfigurableProduct, Quote, Selections } from '../types'

const route = useRoute()
const product = ref<ConfigurableProduct | null>(null)
const selections = reactive<Selections>({})
const quote = ref<Quote | null>(null)
const loading = ref(true)
const errors = ref<Record<string, string[]>>({})

/** Pre-select default values so the configurator opens fully priced. */
function seedDefaults(p: ConfigurableProduct): void {
  for (const option of p.options) {
    const defaults = option.values.filter((v) => v.is_default).map((v) => v.value)
    if (defaults.length === 0) continue
    selections[option.key] = option.type === 'checkbox' ? defaults : defaults[0]
  }
}

const visibleKeys = computed(() =>
  product.value ? resolveVisibleKeys(product.value, selections) : [],
)

const visibleOptions = computed(
  () => product.value?.options.filter((o) => visibleKeys.value.includes(o.key)) ?? [],
)

function formatMoney(amount: number, currency: string): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

let timer: ReturnType<typeof setTimeout> | undefined

/** Debounced authoritative price request. */
function refreshQuote(): void {
  if (!product.value) return
  clearTimeout(timer)
  timer = setTimeout(async () => {
    try {
      quote.value = await builderService.quote(product.value!.slug, { ...selections })
      errors.value = {}
    } catch (e: unknown) {
      const response = (e as { response?: { status?: number; data?: { errors?: Record<string, string[]> } } })
        .response
      if (response?.status === 422) {
        errors.value = response.data?.errors ?? {}
      }
    }
  }, 250)
}

onMounted(async () => {
  try {
    const p = await builderService.product(route.params.slug as string)
    product.value = p
    seedDefaults(p)
    refreshQuote()
  } finally {
    loading.value = false
  }
})

// Re-price whenever any selection changes.
watch(selections, refreshQuote, { deep: true })
</script>

<template>
  <section class="configurator">
    <p v-if="loading" class="configurator__state">Cargando configurador…</p>
    <p v-else-if="!product" class="configurator__state">Producto no encontrado.</p>

    <template v-else>
      <header class="configurator__header">
        <h1>{{ product.name }}</h1>
        <p v-if="product.description">{{ product.description }}</p>
      </header>

      <div class="configurator__grid">
        <!-- Auto-generated form: one field per visible option, by type. -->
        <form class="configurator__form" @submit.prevent>
          <div v-for="option in visibleOptions" :key="option.id" class="configurator__option">
            <OptionField v-model="selections[option.key]" :option="option" />
            <ul v-if="errors[option.key]" class="configurator__errors">
              <li v-for="(msg, i) in errors[option.key]" :key="i">{{ msg }}</li>
            </ul>
          </div>
        </form>

        <!-- Live pricing summary. -->
        <aside class="configurator__summary">
          <h2>Resumen</h2>
          <ul v-if="quote" class="configurator__lines">
            <li v-for="(line, i) in quote.price.items" :key="i">
              <span>{{ line.label }}</span>
              <span>{{ formatMoney(line.delta, quote.price.currency) }}</span>
            </li>
          </ul>
          <p class="configurator__total" v-if="quote">
            <strong>Total</strong>
            <strong>{{ formatMoney(quote.price.total, quote.price.currency) }}</strong>
          </p>
          <button type="button" class="configurator__cta" :disabled="!quote">Agregar al carrito</button>
        </aside>
      </div>
    </template>
  </section>
</template>
