<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useCartStore } from '@/modules/orders/stores/cart'
import { useAuthStore } from '@/stores/auth'
import OptionField from '../components/OptionField.vue'
import { resolveVisibleKeys } from '../dependencies'
import { builderService } from '../services/builderService'
import type { ConfigurableProduct, Quote, Selections } from '../types'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()
const auth = useAuthStore()
const product = ref<ConfigurableProduct | null>(null)
const selections = reactive<Selections>({})
const quote = ref<Quote | null>(null)
const loading = ref(true)
const addingToCart = ref(false)
const addedMessage = ref(false)
const errors = ref<Record<string, string[]>>({})

async function addToCart(): Promise<void> {
  if (!product.value || !quote.value) return

  addingToCart.value = true
  try {
    if (auth.isAuthenticated) {
      await cart.add(product.value.slug, { ...selections })
    } else {
      cart.addLocal({
        product_slug: product.value.slug,
        product_name: product.value.name,
        configuration: {
          selections: { ...selections },
          price: { items: quote.value.price.items.map((i) => ({ label: i.label, delta: i.delta })) },
        },
        unit_price: { amount: quote.value.price.total, currency: quote.value.price.currency },
        quantity: 1,
        line_total: { amount: quote.value.price.total, currency: quote.value.price.currency },
      })
    }
    addedMessage.value = true
    setTimeout(() => { addedMessage.value = false }, 3000)
  } finally {
    addingToCart.value = false
  }
}

function goToCart(): void {
  router.push({ name: 'cart' })
}

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

watch(selections, refreshQuote, { deep: true })
</script>

<template>
  <section class="configurator">
    <p v-if="loading" class="configurator__state">Cargando configurador...</p>
    <p v-else-if="!product" class="configurator__state">Producto no encontrado.</p>

    <template v-else>
      <header class="configurator__header">
        <h1>{{ product.name }}</h1>
        <p v-if="product.description">{{ product.description }}</p>
      </header>

      <div class="configurator__grid">
        <form class="configurator__form" @submit.prevent>
          <div v-for="option in visibleOptions" :key="option.id" class="configurator__option">
            <OptionField v-model="selections[option.key]" :option="option" />
            <ul v-if="errors[option.key]" class="configurator__errors">
              <li v-for="(msg, i) in errors[option.key]" :key="i">{{ msg }}</li>
            </ul>
          </div>
        </form>

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
          <button type="button" class="configurator__cta" :disabled="!quote || addingToCart" @click="addToCart">
            {{ addingToCart ? 'Agregando...' : 'Agregar al Carrito' }}
          </button>

          <div v-if="addedMessage" class="configurator__added">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <span>Agregado al carrito</span>
            <button type="button" class="configurator__go-cart" @click="goToCart">Ver carrito</button>
          </div>
        </aside>
      </div>
    </template>
  </section>
</template>

<style scoped>
.configurator__added {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  background: #f0faf4;
  border: 1px solid #b8e6c8;
  color: #1b7340;
  font-size: 0.85rem;
}

.configurator__go-cart {
  margin-left: auto;
  background: none;
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
  padding: 0.3rem 0.75rem;
  font-size: 0.7rem;
}

.configurator__go-cart:hover {
  background: var(--color-primary);
  color: #fff;
}
</style>
