<script setup lang="ts">
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Card from 'primevue/card'

import type { ProductFormState } from '../composables/useProductForm'

const form = defineModel<ProductFormState>({ required: true })

const currencyOptions = [
  { label: 'MXN', value: 'MXN' },
  { label: 'USD', value: 'USD' },
]

const taxClassOptions = [
  { label: 'Estándar', value: 'standard' },
  { label: 'Reducida', value: 'reduced' },
  { label: 'Exento', value: 'zero' },
]

const discountTypeOptions = [
  { label: 'Sin descuento', value: 'none' },
  { label: 'Porcentaje', value: 'percentage' },
  { label: 'Monto fijo', value: 'fixed' },
]
</script>

<template>
  <Card style="margin-bottom:1.5rem;">
    <template #title>Precios</template>
    <template #content>
      <div class="pricing-grid">
        <div class="pf">
          <label for="pf-price">Precio Base</label>
          <InputNumber id="pf-price" v-model="form.base_price_amount" :min="0" fluid required />
        </div>
        <div class="pf">
          <label for="pf-currency">Moneda</label>
          <Select id="pf-currency" v-model="form.base_price_currency" :options="currencyOptions" optionLabel="label" optionValue="value" fluid />
        </div>
        <div class="pf">
          <label for="pf-vat">IVA (%)</label>
          <InputNumber id="pf-vat" v-model="form.vat" :min="0" :max="100" fluid />
        </div>
        <div class="pf">
          <label for="pf-tax-class">Clase de impuesto</label>
          <Select id="pf-tax-class" v-model="form.tax_class" :options="taxClassOptions" optionLabel="label" optionValue="value" fluid />
        </div>
      </div>

      <div class="pricing-grid" style="margin-top:1rem;">
        <div class="pf">
          <label for="pf-discount-type">Tipo de descuento</label>
          <Select id="pf-discount-type" v-model="form.discount_type" :options="discountTypeOptions" optionLabel="label" optionValue="value" fluid />
        </div>
        <div v-if="form.discount_type !== 'none'" class="pf">
          <label for="pf-discount">
            {{ form.discount_type === 'percentage' ? 'Descuento (%)' : 'Descuento' }}
          </label>
          <InputNumber id="pf-discount" v-model="form.discount_value" :min="0" fluid />
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
.pricing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.pf {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}
.pf label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
</style>
