<script setup lang="ts">
import { computed } from 'vue'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Card from 'primevue/card'
import ToggleSwitch from 'primevue/toggleswitch'

import type { OptionTemplate } from '../services/adminPanelService'
import type { MappedOptionLink } from '../composables/useProductOptions'

const props = defineProps<{
  isEdit: boolean
  optionLinks: MappedOptionLink[]
  showAddOption: boolean
  addOptionTemplateId: number | null
  availableTemplates: OptionTemplate[]
  expandedLinks: Set<number>
  getOptionTypeLabel: (type: string) => string
  isValueEnabled: (link: MappedOptionLink, valueId: number) => boolean
}>()

defineEmits<{
  'update:showAddOption': [val: boolean]
  'update:addOptionTemplateId': [val: number | null]
  'toggle-expand': [id: number]
  'open-legend': [link: MappedOptionLink]
  'remove-link': [link: MappedOptionLink]
  'toggle-value': [link: MappedOptionLink, valueId: number]
  'add-link': []
}>()

const templateOptions = computed(() =>
  props.availableTemplates.map((tpl) => ({
    label: `${tpl.label} (${props.getOptionTypeLabel(tpl.type)})`,
    value: tpl.id,
  })),
)
</script>

<template>
  <Card style="margin-bottom:1.5rem;">
    <template #title>
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <span>Opciones del Producto</span>
        <Button
          v-if="isEdit && availableTemplates.length"
          label="Vincular"
          icon="pi pi-plus"
          size="small"
          severity="secondary"
          outlined
          @click="$emit('update:showAddOption', !showAddOption)"
        />
      </div>
    </template>
    <template #content>
      <p v-if="!isEdit" style="font-size:0.85rem; color:var(--admin-text-secondary);">
        Guarda el producto primero para poder asignar opciones.
      </p>
      <template v-else>
        <!-- Add option selector -->
        <div v-if="showAddOption" class="option-link-add">
          <Select
            :modelValue="addOptionTemplateId"
            :options="[{ label: 'Selecciona una opción...', value: null }, ...templateOptions]"
            optionLabel="label"
            optionValue="value"
            fluid
            @update:modelValue="$emit('update:addOptionTemplateId', $event)"
          />
          <div style="display:flex; gap:0.35rem; margin-top:0.5rem;">
            <Button
              label="Agregar"
              size="small"
              :disabled="!addOptionTemplateId"
              @click="$emit('add-link')"
            />
            <Button
              label="Cancelar"
              size="small"
              severity="secondary"
              outlined
              @click="$emit('update:showAddOption', false); $emit('update:addOptionTemplateId', null)"
            />
          </div>
        </div>

        <p v-if="!optionLinks.length && !showAddOption" style="font-size:0.85rem; color:var(--admin-text-secondary); margin-bottom:0;">
          Este producto aún no tiene opciones asignadas.
        </p>

        <!-- Option links list -->
        <div v-if="optionLinks.length" class="option-links-list">
          <div v-for="link in optionLinks" :key="link.id" class="option-link-item">
            <!-- Header row -->
            <div class="option-link-item__header">
              <button type="button" class="option-link-item__toggle" @click="$emit('toggle-expand', link.id)">
                <i
                  class="pi pi-chevron-right"
                  :style="{ transform: expandedLinks.has(link.id) ? 'rotate(90deg)' : 'rotate(0deg)', transition: 'transform 0.15s ease', fontSize: '0.75rem' }"
                />
              </button>
              <span class="option-link-item__name">{{ link.template?.label ?? '...' }}</span>
              <Tag :value="getOptionTypeLabel(link.template?.type ?? '')" severity="info" style="font-size:0.6rem;" />
              <div class="option-link-item__actions">
                <Button
                  v-tooltip.top="'Editar leyenda'"
                  icon="pi pi-info-circle"
                  size="small"
                  severity="info"
                  text
                  rounded
                  aria-label="Editar leyenda"
                  @click="$emit('open-legend', link)"
                />
                <Button
                  v-tooltip.top="'Desvincular opción'"
                  icon="pi pi-trash"
                  size="small"
                  severity="danger"
                  text
                  rounded
                  aria-label="Desvincular opción"
                  @click="$emit('remove-link', link)"
                />
              </div>
            </div>

            <!-- Expanded body -->
            <div v-if="expandedLinks.has(link.id) && link.template" class="option-link-item__body">
              <div v-if="link.template.help_text" class="option-link-item__help">
                {{ link.template.help_text }}
              </div>
              <div v-if="link.legend" class="option-link-item__legend">
                <span class="option-link-item__legend-label">Leyenda:</span>
                <div class="option-link-item__legend-content" v-html="link.legend" />
              </div>

              <!-- Values tree -->
              <div v-if="link.template.values.length" class="option-link-values-tree">
                <div
                  v-for="val in link.template.values"
                  :key="val.id"
                  class="option-link-value-row"
                  :class="{ 'option-link-value-row--disabled': !isValueEnabled(link, val.id) }"
                >
                  <ToggleSwitch
                    v-tooltip.left="isValueEnabled(link, val.id) ? 'Excluir este valor para el producto' : 'Incluir este valor para el producto'"
                    :modelValue="isValueEnabled(link, val.id)"
                    class="option-link-value-switch"
                    :aria-label="`${isValueEnabled(link, val.id) ? 'Excluir' : 'Incluir'} ${val.label}`"
                    @update:modelValue="$emit('toggle-value', link, val.id)"
                  />
                  <template v-if="link.template!.type === 'color' && val.metadata?.hex">
                    <span class="option-link-value-swatch" :style="{ background: (val.metadata.hex as string) }" />
                  </template>
                  <span class="option-link-value-label">{{ val.label }}</span>
                  <Tag
                    v-if="!isValueEnabled(link, val.id)"
                    value="Excluido"
                    severity="warn"
                    style="font-size:0.55rem;"
                  />
                  <span v-if="val.price_modifier_type !== 'none'" class="option-link-value-price">
                    {{ val.price_modifier_type === 'add' ? '+' : val.price_modifier_type === 'subtract' ? '-' : '=' }}{{ ((val.price_modifier_amount ?? 0) / 100).toFixed(2) }}
                  </span>
                </div>
              </div>
              <p v-else style="font-size:0.75rem; color:var(--admin-text-muted); margin:0;">
                Sin valores configurados
              </p>
            </div>
          </div>
        </div>
      </template>
    </template>
  </Card>
</template>
