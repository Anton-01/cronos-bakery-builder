<script setup lang="ts">
import Dialog from 'primevue/dialog'

import { blockCatalog } from '../../blockCatalog'
import type { BlockType } from '../../types'

defineProps<{ visible: boolean }>()

const emit = defineEmits<{
  close: []
  select: [type: BlockType]
}>()
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    header="Agregar bloque"
    :style="{ width: '640px', maxWidth: '95vw' }"
    @update:visible="emit('close')"
  >
    <p class="palette__hint">Selecciona el tipo de sección que quieres añadir al final de la página.</p>
    <div class="palette__grid">
      <button
        v-for="definition in blockCatalog"
        :key="definition.type"
        type="button"
        class="palette__option"
        @click="emit('select', definition.type)"
      >
        <span class="palette__label">{{ definition.label }}</span>
        <span class="palette__description">{{ definition.description }}</span>
      </button>
    </div>
  </Dialog>
</template>

<style scoped>
.palette__hint {
  margin: 0 0 1rem;
  font-size: 0.85rem;
  color: var(--admin-text-muted, #7c8fac);
}
.palette__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}
.palette__option {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  text-align: left;
  padding: 0.875rem 1rem;
  border: 1px solid var(--admin-border, #e5eaef);
  border-radius: 10px;
  background: transparent;
  cursor: pointer;
  transition: border-color 0.15s ease, background-color 0.15s ease;
  font: inherit;
}
.palette__option:hover {
  border-color: var(--admin-primary, #5d87ff);
  background: color-mix(in srgb, var(--admin-primary, #5d87ff) 6%, transparent);
}
.palette__label {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--admin-text, #2a3547);
}
.palette__description {
  font-size: 0.78rem;
  line-height: 1.35;
  color: var(--admin-text-muted, #7c8fac);
}
@media (max-width: 640px) {
  .palette__grid {
    grid-template-columns: 1fr;
  }
}
</style>
