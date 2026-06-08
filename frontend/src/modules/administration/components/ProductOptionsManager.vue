<script setup lang="ts">
import type { OptionTemplate } from '../services/adminPanelService'
import type { MappedOptionLink } from '../composables/useProductOptions'
defineProps<{
  isEdit: boolean
  optionLinks: MappedOptionLink[]
  showAddOption: boolean
  addOptionTemplateId: string
  availableTemplates: OptionTemplate[]
  expandedLinks: Set<string>
  getOptionTypeLabel: (type: string) => string
  isValueEnabled: (link: MappedOptionLink, valueId: string) => boolean
}>()
defineEmits<{
  'update:showAddOption': [val: boolean]
  'update:addOptionTemplateId': [val: string]
  'toggle-expand': [id: string]
  'open-legend': [link: MappedOptionLink]
  'remove-link': [link: MappedOptionLink]
  'toggle-value': [link: MappedOptionLink, valueId: string]
  'add-link': []
}>()
</script>

<template>
  <div class="admin-content-card" style="margin-bottom: 1.5rem;">
    <div class="admin-content-card__header">
      <h3 class="admin-content-card__title">Opciones del Producto</h3>
      <button
          v-if="isEdit && availableTemplates.length"
          type="button"
          class="admin-btn admin-btn--sm admin-btn--outline"
          @click="$emit('update:showAddOption', !showAddOption)"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
        Vincular
      </button>
    </div>

    <div class="admin-content-card__body">
      <p v-if="!isEdit" style="font-size: 0.85rem; color: var(--admin-text-secondary);">
        Guarda el producto primero para poder asignar opciones.
      </p>
      <template v-else>
        <!-- Add option selector -->
        <div v-if="showAddOption" class="option-link-add">
          <select :value="addOptionTemplateId" class="admin-product-form__select" @change="$emit('update:addOptionTemplateId', ($event.target as HTMLSelectElement).value)">
            <option value="">Selecciona una opción...</option>
            <option v-for="tpl in availableTemplates" :key="tpl.id" :value="tpl.id">
              {{ tpl.label }} ({{ getOptionTypeLabel(tpl.type) }})
            </option>
          </select>
          <div style="display: flex; gap: 0.35rem; margin-top: 0.5rem;">
            <button type="button" class="admin-btn admin-btn--sm admin-btn--primary" :disabled="!addOptionTemplateId" @click="$emit('add-link')">
              Agregar
            </button>
            <button type="button" class="admin-btn admin-btn--sm admin-btn--outline" @click="$emit('update:showAddOption', false); $emit('update:addOptionTemplateId', '')">
              Cancelar
            </button>
          </div>
        </div>

        <p v-if="!optionLinks.length && !showAddOption" style="font-size: 0.85rem; color: var(--admin-text-secondary); margin-bottom: 0;">
          Este producto aún no tiene opciones asignadas.
        </p>

        <!-- Option links list -->
        <div v-if="optionLinks.length" class="option-links-list">
          <div v-for="link in optionLinks" :key="link.id" class="option-link-item">
            <!-- Header row -->
            <div class="option-link-item__header">
              <button type="button" class="option-link-item__toggle" @click="$emit('toggle-expand', link.id)">
                <svg
                    width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    :style="{ transform: expandedLinks.has(link.id) ? 'rotate(90deg)' : 'rotate(0deg)', transition: 'transform 0.15s ease' }"
                ><polyline points="9 18 15 12 9 6" /></svg>
              </button>
              <span class="option-link-item__name">{{ link.template?.label ?? '...' }}</span>
              <span class="admin-badge admin-badge--info" style="font-size: 0.6rem;">{{ getOptionTypeLabel(link.template?.type ?? '') }}</span>
              <div class="option-link-item__actions">
                <button type="button" class="option-link-action-btn" title="Leyenda" @click="$emit('open-legend', link)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" />
                  </svg>
                </button>
                <button type="button" class="option-link-action-btn option-link-action-btn--delete" title="Desvincular" @click="$emit('remove-link', link)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Expanded body -->
            <div v-if="expandedLinks.has(link.id) && link.template" class="option-link-item__body">
              <div v-if="link.template.help_text" class="option-link-item__help">
                {{ link.template.help_text }}
              </div>
              <div v-if="link.legend" class="option-link-item__legend">
                <span class="option-link-item__legend-label">Leyenda:</span>
                <div class="option-link-item__legend-content" v-html="link.legend"></div>
              </div>

              <!-- Values tree -->
              <div v-if="link.template.values.length" class="option-link-values-tree">
                <div v-for="val in link.template.values" :key="val.id" class="option-link-value-row" :class="{ 'option-link-value-row--disabled': !isValueEnabled(link, val.id) }">
                  <label class="option-link-value-check">
                    <input type="checkbox" :checked="isValueEnabled(link, val.id)" @change="$emit('toggle-value', link, val.id)" />
                    <span class="option-link-value-check__mark"></span>
                  </label>
                  <template v-if="link.template!.type === 'color' && val.metadata?.hex">
                    <span class="option-link-value-swatch" :style="{ background: (val.metadata.hex as string) }"></span>
                  </template>
                  <span class="option-link-value-label">{{ val.label }}</span>
                  <span v-if="val.price_modifier_type !== 'none'" class="option-link-value-price">
                    {{ val.price_modifier_type === 'add' ? '+' : val.price_modifier_type === 'subtract' ? '-' : '=' }}{{ ((val.price_modifier_amount ?? 0) / 100).toFixed(2) }}
                  </span>
                </div>
              </div>
              <p v-else style="font-size: 0.75rem; color: var(--admin-text-muted); margin: 0;">
                Sin valores configurados
              </p>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>