<script setup lang="ts">
import InputText from 'primevue/inputtext'
import Editor from 'primevue/editor'
import Card from 'primevue/card'

import type { ProductFormState } from '../composables/useProductForm'
const form = defineModel<ProductFormState>({ required: true })
const emit = defineEmits<{
  'name-input': []
}>()
</script>

<template>
  <Card style="margin-bottom: 1.5rem;">
    <template #title>General</template>
    <template #content>
      <div style="margin-bottom: 1rem;">
        <label class="field-label" for="pf-name">Nombre del producto</label>
        <InputText
          id="pf-name"
          v-model="form.name"
          fluid
          required
          placeholder="Ej: Pastel de Chocolate"
          @input="emit('name-input')"
        />
        <small v-if="form.slug" class="product-slug-display">/{{ form.slug }}</small>
      </div>
      <div>
        <label class="field-label">Descripción</label>
        <Editor v-model="form.description" editorStyle="height: 200px" />
      </div>
    </template>
  </Card>
</template>

<style scoped>
.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
  margin-bottom: 0.4rem;
}
.product-slug-display {
  display: block;
  margin-top: 0.3rem;
  font-size: 0.75rem;
  color: var(--admin-primary);
  font-family: monospace;
  opacity: 0.7;
}
</style>
