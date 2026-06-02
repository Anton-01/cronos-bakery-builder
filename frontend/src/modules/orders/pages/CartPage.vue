<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import { useCartStore } from '../stores/cart'

const cart = useCartStore()

function money(amount: number, currency: string): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

onMounted(() => cart.load())
</script>

<template>
  <section class="cart">
    <h1>Tu carrito</h1>

    <p v-if="cart.loading" class="catalog__state">Cargando…</p>
    <p v-else-if="cart.itemCount === 0" class="catalog__state">
      Tu carrito está vacío. <RouterLink to="/builder">Arma tu pastel</RouterLink>.
    </p>

    <template v-else>
      <ul class="cart__items">
        <li v-for="item in cart.items" :key="item.id" class="cart__item">
          <div class="cart__item-info">
            <h3>{{ item.product_name }}</h3>
            <p class="cart__item-config">
              <span v-for="(value, key) in item.configuration.selections" :key="key">
                {{ key }}: {{ Array.isArray(value) ? value.join(', ') : value }}
              </span>
            </p>
          </div>
          <div class="cart__item-actions">
            <input type="number" min="1" :value="item.quantity"
              @change="cart.updateQuantity(item.id, Number(($event.target as HTMLInputElement).value))" />
            <span>{{ money(item.line_total.amount, item.line_total.currency) }}</span>
            <button type="button" @click="cart.remove(item.id)">Eliminar</button>
          </div>
        </li>
      </ul>

      <div class="cart__summary">
        <p>
          <strong>Subtotal</strong>
          <strong v-if="cart.subtotal">{{ money(cart.subtotal.amount, cart.subtotal.currency) }}</strong>
        </p>
        <RouterLink class="configurator__cta" to="/checkout">Proceder al pago</RouterLink>
      </div>
    </template>
  </section>
</template>
