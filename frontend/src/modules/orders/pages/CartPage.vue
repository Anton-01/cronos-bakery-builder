<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '../stores/cart'

const cart = useCartStore()
const auth = useAuthStore()
const removingId = ref<string | null>(null)

const isAuthenticated = computed(() => auth.isAuthenticated)

function money(amount: number, currency: string): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

function itemId(item: Record<string, unknown>): string {
  return (item.id as string) ?? (item.localId as string)
}

function itemName(item: Record<string, unknown>): string {
  return (item.product_name as string) ?? ''
}

function itemSelections(item: Record<string, unknown>): Record<string, string | string[]> {
  const config = item.configuration as { selections: Record<string, string | string[]> } | undefined
  return config?.selections ?? {}
}

function itemPriceLines(item: Record<string, unknown>): { label: string; delta: number }[] {
  const config = item.configuration as { price?: { items?: { label: string; delta: number }[] } } | undefined
  return config?.price?.items ?? []
}

function itemUnitPrice(item: Record<string, unknown>): { amount: number; currency: string } {
  return (item.unit_price as { amount: number; currency: string }) ?? { amount: 0, currency: 'CRC' }
}

function itemLineTotal(item: Record<string, unknown>): { amount: number; currency: string } {
  return (item.line_total as { amount: number; currency: string }) ?? { amount: 0, currency: 'CRC' }
}

function itemQuantity(item: Record<string, unknown>): number {
  return (item.quantity as number) ?? 1
}

function itemSlug(item: Record<string, unknown>): string {
  return (item.product_slug as string) ?? ''
}

function formatSelectionValue(value: string | string[]): string {
  return Array.isArray(value) ? value.join(', ') : value
}

function formatKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

async function handleQuantityChange(id: string, event: Event): Promise<void> {
  const val = Number((event.target as HTMLInputElement).value)
  if (val >= 1) await cart.updateQuantity(id, val)
}

async function handleRemove(id: string): Promise<void> {
  removingId.value = id
  try {
    await cart.remove(id)
  } finally {
    removingId.value = null
  }
}

onMounted(() => {
  if (auth.isAuthenticated) {
    cart.load()
  }
})
</script>

<template>
  <section class="cart-page">
    <h1>Tu Carrito</h1>

    <p v-if="cart.loading" class="cart-page__state">Cargando...</p>

    <div v-else-if="cart.itemCount === 0" class="cart-page__empty">
      <svg class="cart-page__empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      <h2>Tu carrito esta vacio</h2>
      <p>Aun no has agregado ningun pastel. Explora nuestro catalogo o arma tu pastel personalizado.</p>
      <div class="cart-page__empty-actions">
        <RouterLink to="/builder" class="cart-page__btn cart-page__btn--primary">Arma tu Pastel</RouterLink>
        <RouterLink to="/catalog" class="cart-page__btn cart-page__btn--secondary">Ver Catalogo</RouterLink>
      </div>
    </div>

    <template v-else>
      <div class="cart-page__layout">
        <!-- Items list -->
        <div class="cart-page__items">
          <div v-for="item in cart.items" :key="itemId(item as Record<string, unknown>)" class="cart-item">
            <div class="cart-item__header">
              <div class="cart-item__title">
                <h3>
                  <RouterLink :to="`/builder/${itemSlug(item as Record<string, unknown>)}`">
                    {{ itemName(item as Record<string, unknown>) }}
                  </RouterLink>
                </h3>
                <span class="cart-item__unit-price">
                  {{ money(itemUnitPrice(item as Record<string, unknown>).amount, itemUnitPrice(item as Record<string, unknown>).currency) }} c/u
                </span>
              </div>
              <div class="cart-item__line-total">
                {{ money(itemLineTotal(item as Record<string, unknown>).amount, itemLineTotal(item as Record<string, unknown>).currency) }}
              </div>
            </div>

            <!-- Configuration details -->
            <div class="cart-item__details">
              <div
                v-for="(value, key) in itemSelections(item as Record<string, unknown>)"
                :key="key"
                class="cart-item__detail"
              >
                <span class="cart-item__detail-key">{{ formatKey(key as string) }}</span>
                <span class="cart-item__detail-value">{{ formatSelectionValue(value) }}</span>
              </div>
            </div>

            <!-- Price breakdown -->
            <details v-if="itemPriceLines(item as Record<string, unknown>).length" class="cart-item__breakdown">
              <summary>Ver desglose de precio</summary>
              <ul>
                <li v-for="(line, i) in itemPriceLines(item as Record<string, unknown>)" :key="i">
                  <span>{{ line.label }}</span>
                  <span>{{ money(line.delta, itemUnitPrice(item as Record<string, unknown>).currency) }}</span>
                </li>
              </ul>
            </details>

            <!-- Actions row -->
            <div class="cart-item__actions">
              <div class="cart-item__quantity">
                <label>Cantidad</label>
                <div class="cart-item__qty-control">
                  <button
                    type="button"
                    :disabled="itemQuantity(item as Record<string, unknown>) <= 1"
                    @click="cart.updateQuantity(itemId(item as Record<string, unknown>), itemQuantity(item as Record<string, unknown>) - 1)"
                  >-</button>
                  <input
                    type="number"
                    min="1"
                    :value="itemQuantity(item as Record<string, unknown>)"
                    @change="handleQuantityChange(itemId(item as Record<string, unknown>), $event)"
                  />
                  <button
                    type="button"
                    @click="cart.updateQuantity(itemId(item as Record<string, unknown>), itemQuantity(item as Record<string, unknown>) + 1)"
                  >+</button>
                </div>
              </div>

              <div class="cart-item__action-btns">
                <RouterLink
                  :to="`/builder/${itemSlug(item as Record<string, unknown>)}`"
                  class="cart-item__edit"
                  title="Modificar configuracion"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Modificar
                </RouterLink>
                <button
                  type="button"
                  class="cart-item__remove"
                  :disabled="removingId === itemId(item as Record<string, unknown>)"
                  @click="handleRemove(itemId(item as Record<string, unknown>))"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  {{ removingId === itemId(item as Record<string, unknown>) ? 'Eliminando...' : 'Eliminar' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar summary -->
        <aside class="cart-page__sidebar">
          <div class="cart-page__summary">
            <h2>Resumen del Pedido</h2>

            <div class="cart-page__summary-lines">
              <div class="cart-page__summary-line">
                <span>Subtotal ({{ cart.itemCount }} {{ cart.itemCount === 1 ? 'item' : 'items' }})</span>
                <span v-if="cart.subtotal">{{ money(cart.subtotal.amount, cart.subtotal.currency) }}</span>
              </div>
              <div class="cart-page__summary-line cart-page__summary-line--note">
                <span>Envio</span>
                <span>Se calcula al pagar</span>
              </div>
            </div>

            <div class="cart-page__summary-total">
              <strong>Total estimado</strong>
              <strong v-if="cart.total">{{ money(cart.total.amount, cart.total.currency) }}</strong>
            </div>

            <!-- Delivery scheduling section -->
            <div class="cart-page__schedule">
              <div class="cart-page__schedule-header">
                <div class="cart-page__schedule-icon" :class="{ 'cart-page__schedule-icon--locked': !isAuthenticated }">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                  </svg>
                  <svg v-if="!isAuthenticated" class="cart-page__lock-overlay" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3-9H9V6c0-1.66 1.34-3 3-3s3 1.34 3 3v2z"/></svg>
                </div>
                <div>
                  <h4>Fecha y Hora de Entrega</h4>
                  <p v-if="isAuthenticated">Selecciona cuando quieres recibir tu pedido</p>
                  <p v-else>Inicia sesion para seleccionar fecha y hora de entrega</p>
                </div>
              </div>

              <div v-if="!isAuthenticated" class="cart-page__schedule-locked">
                <p>Para completar tu pedido necesitas:</p>
                <ul>
                  <li>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Seleccionar fecha y hora de entrega
                  </li>
                  <li>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Elegir metodo de entrega (envio o recoger en tienda)
                  </li>
                  <li>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Registrar tu informacion de contacto
                  </li>
                </ul>
                <div class="cart-page__auth-actions">
                  <RouterLink :to="{ name: 'auth.login', query: { redirect: '/carrito' } }" class="cart-page__btn cart-page__btn--primary">
                    Iniciar Sesion
                  </RouterLink>
                  <RouterLink :to="{ name: 'auth.register', query: { redirect: '/carrito' } }" class="cart-page__btn cart-page__btn--secondary">
                    Crear Cuenta
                  </RouterLink>
                </div>
              </div>

              <RouterLink v-else to="/checkout" class="cart-page__btn cart-page__btn--primary cart-page__btn--full">
                Proceder al Pago
              </RouterLink>
            </div>
          </div>

          <!-- Info section -->
          <div class="cart-page__info">
            <div class="cart-page__info-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              <div>
                <strong>Pago seguro</strong>
                <p>Tu informacion esta protegida</p>
              </div>
            </div>
            <div class="cart-page__info-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
              <div>
                <strong>Entrega cuidadosa</strong>
                <p>Tu pastel llega en perfectas condiciones</p>
              </div>
            </div>
            <div class="cart-page__info-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <div>
                <strong>Soporte personalizado</strong>
                <p>Te ayudamos con cualquier duda</p>
              </div>
            </div>
          </div>
        </aside>
      </div>

      <!-- Continue shopping -->
      <div class="cart-page__continue">
        <RouterLink to="/builder">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Seguir armando pasteles
        </RouterLink>
      </div>
    </template>
  </section>
</template>

<style scoped>
.cart-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 3rem 4rem;
}

.cart-page > h1 {
  font-family: var(--font-heading);
  font-size: 2.5rem;
  font-weight: 300;
  text-align: center;
  margin-bottom: 2rem;
}

.cart-page__state {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-light);
}

/* Empty state */
.cart-page__empty {
  text-align: center;
  padding: 4rem 2rem;
}

.cart-page__empty-icon {
  color: var(--color-border);
  margin-bottom: 1.5rem;
}

.cart-page__empty h2 {
  font-family: var(--font-heading);
  font-size: 1.8rem;
  font-weight: 400;
  margin-bottom: 0.5rem;
}

.cart-page__empty p {
  color: var(--color-text-light);
  max-width: 400px;
  margin: 0 auto 2rem;
  line-height: 1.7;
}

.cart-page__empty-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

/* Layout */
.cart-page__layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 3rem;
  align-items: start;
}

@media (max-width: 900px) {
  .cart-page__layout { grid-template-columns: 1fr; }
}

/* Cart items */
.cart-item {
  padding: 1.5rem 0;
  border-bottom: 1px solid var(--color-border-light);
}

.cart-item:first-child {
  padding-top: 0;
}

.cart-item__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.cart-item__title h3 {
  font-family: var(--font-heading);
  font-size: 1.35rem;
  font-weight: 400;
  margin: 0 0 0.15rem;
}

.cart-item__title h3 a {
  color: var(--color-heading);
}

.cart-item__title h3 a:hover {
  color: var(--color-primary);
}

.cart-item__unit-price {
  font-size: 0.8rem;
  color: var(--color-text-light);
}

.cart-item__line-total {
  font-family: var(--font-heading);
  font-size: 1.3rem;
  color: var(--color-heading);
  white-space: nowrap;
}

/* Config details */
.cart-item__details {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1.5rem;
  margin-bottom: 0.75rem;
}

.cart-item__detail {
  display: flex;
  gap: 0.35rem;
  font-size: 0.85rem;
}

.cart-item__detail-key {
  color: var(--color-text-light);
}

.cart-item__detail-key::after {
  content: ':';
}

.cart-item__detail-value {
  color: var(--color-text);
}

/* Price breakdown */
.cart-item__breakdown {
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
}

.cart-item__breakdown summary {
  cursor: pointer;
  color: var(--color-text-light);
  font-size: 0.8rem;
  letter-spacing: 0.05em;
}

.cart-item__breakdown summary:hover {
  color: var(--color-primary);
}

.cart-item__breakdown ul {
  list-style: none;
  padding: 0.5rem 0 0;
  margin: 0;
}

.cart-item__breakdown li {
  display: flex;
  justify-content: space-between;
  padding: 0.2rem 0;
  color: var(--color-text-light);
}

/* Actions */
.cart-item__actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.75rem;
}

.cart-item__quantity label {
  font-size: 0.7rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--color-text-light);
  display: block;
  margin-bottom: 0.35rem;
}

.cart-item__qty-control {
  display: flex;
  align-items: center;
  border: 1px solid var(--color-border);
}

.cart-item__qty-control button {
  width: 32px;
  height: 32px;
  background: var(--color-surface);
  color: var(--color-text);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  padding: 0;
  letter-spacing: 0;
  text-transform: none;
}

.cart-item__qty-control button:hover:not(:disabled) {
  background: var(--color-surface-warm);
}

.cart-item__qty-control input {
  width: 40px;
  height: 32px;
  text-align: center;
  border: none;
  border-left: 1px solid var(--color-border);
  border-right: 1px solid var(--color-border);
  font-family: var(--font-body);
  font-size: 0.9rem;
  padding: 0;
  margin: 0;
  -moz-appearance: textfield;
}

.cart-item__qty-control input::-webkit-inner-spin-button,
.cart-item__qty-control input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.cart-item__action-btns {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.cart-item__edit,
.cart-item__remove {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  color: var(--color-text-light);
  cursor: pointer;
  background: none;
  border: none;
  padding: 0.3rem 0;
  letter-spacing: 0.03em;
  text-transform: none;
  text-decoration: none;
}

.cart-item__edit:hover {
  color: var(--color-primary);
}

.cart-item__remove:hover {
  color: #b3261e;
}

/* Sidebar */
.cart-page__sidebar {
  position: sticky;
  top: 5rem;
}

.cart-page__summary {
  background: var(--color-surface-warm);
  padding: 2rem;
  margin-bottom: 1.5rem;
}

.cart-page__summary h2 {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 400;
  margin: 0 0 1.25rem;
}

.cart-page__summary-lines {
  margin-bottom: 1rem;
}

.cart-page__summary-line {
  display: flex;
  justify-content: space-between;
  padding: 0.4rem 0;
  font-size: 0.9rem;
}

.cart-page__summary-line--note span:last-child {
  color: var(--color-text-light);
  font-size: 0.8rem;
  font-style: italic;
}

.cart-page__summary-total {
  display: flex;
  justify-content: space-between;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border);
  font-size: 1.15rem;
}

/* Schedule section */
.cart-page__schedule {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border);
}

.cart-page__schedule-header {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.cart-page__schedule-icon {
  position: relative;
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-surface);
  border-radius: 50%;
  color: var(--color-primary);
}

.cart-page__schedule-icon--locked {
  color: var(--color-text-light);
  opacity: 0.7;
}

.cart-page__lock-overlay {
  position: absolute;
  bottom: -2px;
  right: -2px;
  color: var(--color-primary);
  background: var(--color-surface-warm);
  border-radius: 50%;
  padding: 1px;
}

.cart-page__schedule-header h4 {
  font-family: var(--font-body);
  font-size: 0.8rem;
  font-weight: 500;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin: 0 0 0.2rem;
  color: var(--color-heading);
}

.cart-page__schedule-header p {
  font-size: 0.8rem;
  color: var(--color-text-light);
  margin: 0;
  line-height: 1.5;
}

.cart-page__schedule-locked {
  padding: 1rem;
  background: var(--color-surface);
  border: 1px dashed var(--color-border);
}

.cart-page__schedule-locked > p {
  font-size: 0.85rem;
  color: var(--color-text);
  margin: 0 0 0.75rem;
  font-weight: 500;
}

.cart-page__schedule-locked ul {
  list-style: none;
  padding: 0;
  margin: 0 0 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.cart-page__schedule-locked li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--color-text-light);
}

.cart-page__schedule-locked li svg {
  flex-shrink: 0;
  color: var(--color-border);
}

.cart-page__auth-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Info section */
.cart-page__info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cart-page__info-item {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.cart-page__info-item svg {
  flex-shrink: 0;
  color: var(--color-primary);
  margin-top: 0.1rem;
}

.cart-page__info-item strong {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--color-heading);
  margin-bottom: 0.1rem;
}

.cart-page__info-item p {
  font-size: 0.78rem;
  color: var(--color-text-light);
  margin: 0;
  line-height: 1.4;
}

/* Continue shopping */
.cart-page__continue {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border-light);
}

.cart-page__continue a {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: var(--color-text-light);
  font-size: 0.85rem;
}

.cart-page__continue a:hover {
  color: var(--color-primary);
}

/* Buttons */
.cart-page__btn {
  display: block;
  text-align: center;
  padding: 0.8rem 1.5rem;
  font-family: var(--font-body);
  font-size: 0.75rem;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.cart-page__btn--primary {
  background: var(--color-primary);
  color: #fff;
  border: 1px solid var(--color-primary);
}

.cart-page__btn--primary:hover {
  background: #a84432;
  border-color: #a84432;
}

.cart-page__btn--secondary {
  background: transparent;
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

.cart-page__btn--secondary:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.cart-page__btn--full {
  width: 100%;
}
</style>
