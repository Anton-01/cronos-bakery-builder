<script setup lang="ts">
import { computed, ref } from 'vue'
import Button from 'primevue/button'
import Editor from 'primevue/editor'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'

import MediaLibraryModal from '../MediaLibraryModal.vue'
import type { FieldDef } from '../../blockCatalog'
import type { MediaAsset } from '../../types'

/**
 * Schema-driven editor for a block payload: renders one input per field
 * definition from the block catalog and mutates `data` in place (the store
 * object is reactive), so the builder preview updates as the user types.
 */
const props = defineProps<{
  fields: FieldDef[]
  data: Record<string, unknown>
}>()

const visibleFields = computed(() =>
  props.fields.filter((field) => {
    if (!field.visibleWhen) return true
    return props.data[field.visibleWhen.key] === field.visibleWhen.equals
  }),
)

function stringValue(target: Record<string, unknown>, key: string): string {
  const value = target[key]
  return typeof value === 'string' ? value : ''
}

function numberValue(target: Record<string, unknown>, key: string): number | null {
  const value = target[key]
  return typeof value === 'number' ? value : null
}

function booleanValue(target: Record<string, unknown>, key: string): boolean {
  return props.data[key] === true || target[key] === true
}

function rows(field: FieldDef): Record<string, unknown>[] {
  const value = props.data[field.key]
  return Array.isArray(value) ? (value as Record<string, unknown>[]) : []
}

function addRow(field: FieldDef): void {
  const row: Record<string, unknown> = {}
  for (const sub of field.itemFields ?? []) {
    row[sub.key] = sub.kind === 'toggle' ? false : ''
  }
  const current = rows(field)
  props.data[field.key] = [...current, row]
}

function removeRow(field: FieldDef, index: number): void {
  props.data[field.key] = rows(field).filter((_, i) => i !== index)
}

function moveRow(field: FieldDef, index: number, direction: -1 | 1): void {
  const list = [...rows(field)]
  const target = index + direction
  if (target < 0 || target >= list.length) return
  const [moved] = list.splice(index, 1)
  list.splice(target, 0, moved)
  props.data[field.key] = list
}

// --- Media library integration ----------------------------------------------

const mediaOpen = ref(false)
const mediaTarget = ref<{ object: Record<string, unknown>; key: string } | null>(null)

function pickImage(object: Record<string, unknown>, key: string): void {
  mediaTarget.value = { object, key }
  mediaOpen.value = true
}

function onMediaSelect(asset: MediaAsset): void {
  if (mediaTarget.value) {
    mediaTarget.value.object[mediaTarget.value.key] = asset.url ?? asset.path
  }
  mediaOpen.value = false
  mediaTarget.value = null
}
</script>

<template>
  <div class="config-form">
    <template v-for="field in visibleFields" :key="field.key">
      <!-- Repeatable rows (gallery images, FAQ items, cards…) -->
      <div v-if="field.kind === 'items'" class="config-form__field">
        <div class="config-form__items-header">
          <label>{{ field.label }}<span v-if="field.required" class="config-form__required">*</span></label>
          <Button label="Agregar" size="small" severity="secondary" outlined @click="addRow(field)" />
        </div>

        <p v-if="rows(field).length === 0" class="config-form__empty">Sin elementos todavía.</p>

        <div v-for="(row, index) in rows(field)" :key="index" class="config-form__item">
          <div class="config-form__item-toolbar">
            <span class="config-form__item-index">{{ index + 1 }}</span>
            <div class="config-form__item-actions">
              <Button label="Subir" size="small" text :disabled="index === 0" @click="moveRow(field, index, -1)" />
              <Button label="Bajar" size="small" text :disabled="index === rows(field).length - 1" @click="moveRow(field, index, 1)" />
              <Button label="Quitar" size="small" text severity="danger" @click="removeRow(field, index)" />
            </div>
          </div>

          <div v-for="sub in field.itemFields" :key="sub.key" class="config-form__field">
            <label>{{ sub.label }}<span v-if="sub.required" class="config-form__required">*</span></label>
            <Textarea
              v-if="sub.kind === 'textarea'"
              :model-value="stringValue(row, sub.key)"
              rows="3"
              auto-resize
              fluid
              @update:model-value="row[sub.key] = $event"
            />
            <div v-else-if="sub.kind === 'image'" class="config-form__image-input">
              <InputText
                :model-value="stringValue(row, sub.key)"
                placeholder="URL de la imagen"
                fluid
                @update:model-value="row[sub.key] = $event"
              />
              <Button label="Biblioteca" size="small" severity="secondary" outlined @click="pickImage(row, sub.key)" />
            </div>
            <InputText
              v-else
              :model-value="stringValue(row, sub.key)"
              fluid
              @update:model-value="row[sub.key] = $event"
            />
          </div>
        </div>
      </div>

      <!-- Scalar fields -->
      <div v-else class="config-form__field">
        <label>{{ field.label }}<span v-if="field.required" class="config-form__required">*</span></label>

        <Textarea
          v-if="field.kind === 'textarea'"
          :model-value="stringValue(data, field.key)"
          rows="3"
          auto-resize
          fluid
          @update:model-value="data[field.key] = $event"
        />

        <Editor
          v-else-if="field.kind === 'richtext'"
          :model-value="stringValue(data, field.key)"
          editor-style="height: 220px"
          @update:model-value="data[field.key] = $event"
        />

        <InputNumber
          v-else-if="field.kind === 'number'"
          :model-value="numberValue(data, field.key)"
          :min="field.min"
          :max="field.max"
          show-buttons
          fluid
          @update:model-value="data[field.key] = $event"
        />

        <div v-else-if="field.kind === 'toggle'" class="config-form__toggle">
          <ToggleSwitch
            :model-value="booleanValue(data, field.key)"
            @update:model-value="data[field.key] = $event"
          />
        </div>

        <Select
          v-else-if="field.kind === 'select'"
          :model-value="stringValue(data, field.key)"
          :options="field.options"
          option-label="label"
          option-value="value"
          fluid
          @update:model-value="data[field.key] = $event"
        />

        <div v-else-if="field.kind === 'image'" class="config-form__image-input">
          <InputText
            :model-value="stringValue(data, field.key)"
            placeholder="URL de la imagen"
            fluid
            @update:model-value="data[field.key] = $event"
          />
          <Button label="Biblioteca" size="small" severity="secondary" outlined @click="pickImage(data, field.key)" />
        </div>

        <InputText
          v-else
          :model-value="stringValue(data, field.key)"
          fluid
          @update:model-value="data[field.key] = $event"
        />

        <small v-if="field.help" class="config-form__help">{{ field.help }}</small>
      </div>
    </template>

    <MediaLibraryModal :visible="mediaOpen" @close="mediaOpen = false" @select="onMediaSelect" />
  </div>
</template>

<style scoped>
.config-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.config-form__field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary, #5a6a85);
}
.config-form__required {
  color: var(--admin-error, #fa896b);
  margin-left: 2px;
}
.config-form__help {
  color: var(--admin-text-muted, #7c8fac);
  font-size: 0.75rem;
}
.config-form__items-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}
.config-form__empty {
  font-size: 0.82rem;
  color: var(--admin-text-muted, #7c8fac);
  border: 1px dashed var(--admin-border, #e5eaef);
  border-radius: 8px;
  padding: 0.75rem;
  text-align: center;
}
.config-form__item {
  border: 1px solid var(--admin-border, #e5eaef);
  border-radius: 8px;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
}
.config-form__item-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}
.config-form__item-index {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--admin-text-muted, #7c8fac);
}
.config-form__item-actions {
  display: flex;
  gap: 0.125rem;
}
.config-form__image-input {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
.config-form__toggle {
  display: flex;
}
</style>
