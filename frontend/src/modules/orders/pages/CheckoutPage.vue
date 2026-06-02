<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { orderService, type AddressPayload } from '../services/orderService'
import { useCartStore } from '../stores/cart'
import type { Address, Branch, CheckoutPayload } from '../types'

const cart = useCartStore()
const router = useRouter()

const addresses = ref<Address[]>([])
const branches = ref<Branch[]>([])
const error = ref<string | null>(null)
const submitting = ref(false)
const showAddressForm = ref(false)

const form = reactive<CheckoutPayload>({
  fulfillment_type: 'pickup',
  notes: '',
})

const newAddress = reactive<AddressPayload>({
  label: 'home',
  recipient_name: '',
  line1: '',
  city: '',
})

async function saveAddress(): Promise<void> {
  const created = await orderService.createAddress(newAddress)
  addresses.value = await orderService.addresses()
  form.address_id = created.id
  showAddressForm.value = false
}

function money(amount: number, currency: string): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

async function submit(): Promise<void> {
  error.value = null
  submitting.value = true
  try {
    const order = await orderService.checkout(form)
    cart.reset()
    await router.push({ name: 'orders.detail', params: { id: order.id } })
  } catch (e: unknown) {
    error.value = 'No se pudo completar el pedido. Revisa los datos e intenta de nuevo.'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  await cart.load()
  ;[addresses.value, branches.value] = await Promise.all([
    orderService.addresses(),
    orderService.branches(),
  ])
  const def = addresses.value.find((a) => a.is_default)
  if (def) form.address_id = def.id
  if (branches.value.length) form.branch_id = branches.value[0].id
})
</script>

<template>
  <section class="checkout">
    <h1>Checkout</h1>

    <div class="checkout__grid">
      <form class="checkout__form" @submit.prevent="submit">
        <fieldset>
          <legend>Método de entrega</legend>
          <label><input type="radio" value="pickup" v-model="form.fulfillment_type" /> Recolección en sucursal</label>
          <label><input type="radio" value="delivery" v-model="form.fulfillment_type" /> Entrega a domicilio</label>
        </fieldset>

        <!-- Pickup -->
        <fieldset v-if="form.fulfillment_type === 'pickup'">
          <legend>Recolección</legend>
          <label>Sucursal
            <select v-model="form.branch_id" required>
              <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </label>
          <label>Fecha <input type="date" v-model="form.pickup_date" required /></label>
          <label>Hora <input type="time" v-model="form.pickup_time" required /></label>
        </fieldset>

        <!-- Delivery -->
        <fieldset v-else>
          <legend>Dirección de entrega</legend>
          <label v-for="a in addresses" :key="a.id" class="checkout__address">
            <input type="radio" :value="a.id" v-model="form.address_id" />
            <span><strong>{{ a.label_text }}</strong> — {{ a.line1 }}, {{ a.city }}</span>
          </label>

          <button type="button" class="checkout__add" @click="showAddressForm = !showAddressForm">
            {{ showAddressForm ? 'Cancelar' : '+ Agregar dirección' }}
          </button>

          <div v-if="showAddressForm" class="checkout__new-address">
            <label>Etiqueta
              <select v-model="newAddress.label">
                <option value="home">Casa</option>
                <option value="work">Trabajo</option>
                <option value="other">Otra</option>
              </select>
            </label>
            <label>Nombre <input v-model="newAddress.recipient_name" /></label>
            <label>Dirección <input v-model="newAddress.line1" /></label>
            <label>Ciudad <input v-model="newAddress.city" /></label>
            <button type="button" @click="saveAddress">Guardar dirección</button>
          </div>
        </fieldset>

        <label>Notas <textarea v-model="form.notes" rows="2"></textarea></label>

        <p v-if="error" class="auth-form__error">{{ error }}</p>
        <button type="submit" class="configurator__cta" :disabled="submitting || cart.itemCount === 0">
          {{ submitting ? 'Procesando…' : 'Confirmar pedido' }}
        </button>
      </form>

      <aside class="checkout__summary">
        <h2>Resumen</h2>
        <ul>
          <li v-for="item in cart.items" :key="item.id">
            <span>{{ item.quantity }}× {{ item.product_name }}</span>
            <span>{{ money(item.line_total.amount, item.line_total.currency) }}</span>
          </li>
        </ul>
        <p class="configurator__total" v-if="cart.subtotal">
          <strong>Total</strong>
          <strong>{{ money(cart.subtotal.amount, cart.subtotal.currency) }}</strong>
        </p>
      </aside>
    </div>
  </section>
</template>
