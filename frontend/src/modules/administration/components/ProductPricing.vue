<script setup lang="ts">
import type { ProductFormState } from '../composables/useProductForm'
const form = defineModel<ProductFormState>({ required: true })
</script>

<template>
  <div class="admin-content-card" style="margin-bottom: 1.5rem;">
    <div class="admin-content-card__header">
      <h3 class="admin-content-card__title">Precios</h3>
    </div>
    <div class="admin-content-card__body">
      <div class="admin-pricing-grid">
        <div class="admin-product-form__field">
          <label class="admin-product-form__label" for="pf-price">Precio Base</label>
          <input id="pf-price" v-model.number="form.base_price_amount" type="number" min="0" step="1" class="admin-product-form__input" required />
        </div>
        <div class="admin-product-form__field">
          <label class="admin-product-form__label" for="pf-currency">Moneda</label>
          <select id="pf-currency" v-model="form.base_price_currency" class="admin-product-form__select">
            <option value="MXN">MXN</option>
            <option value="USD">USD</option>
          </select>
        </div>
        <div class="admin-product-form__field">
          <label class="admin-product-form__label" for="pf-vat">IVA (%)</label>
          <input id="pf-vat" v-model.number="form.vat" type="number" min="0" max="100" class="admin-product-form__input" />
        </div>
        <div class="admin-product-form__field">
          <label class="admin-product-form__label" for="pf-tax-class">Clase de impuesto</label>
          <select id="pf-tax-class" v-model="form.tax_class" class="admin-product-form__select">
            <option value="standard">Estándar</option>
            <option value="reduced">Reducida</option>
            <option value="zero">Exento</option>
          </select>
        </div>
      </div>

      <div class="admin-pricing-grid" style="margin-top: 1rem;">
        <div class="admin-product-form__field">
          <label class="admin-product-form__label" for="pf-discount-type">Tipo de descuento</label>
          <select id="pf-discount-type" v-model="form.discount_type" class="admin-product-form__select">
            <option value="none">Sin descuento</option>
            <option value="percentage">Porcentaje</option>
            <option value="fixed">Monto fijo</option>
          </select>
        </div>
        <div v-if="form.discount_type !== 'none'" class="admin-product-form__field">
          <label class="admin-product-form__label" for="pf-discount">
            {{ form.discount_type === 'percentage' ? 'Descuento (%)' : 'Descuento' }}
          </label>
          <input id="pf-discount" v-model.number="form.discount_value" type="number" min="0" class="admin-product-form__input" />
        </div>
      </div>
    </div>
  </div>
</template>