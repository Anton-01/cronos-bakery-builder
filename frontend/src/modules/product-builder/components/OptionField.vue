<script setup lang="ts">
import { computed } from 'vue'

import type { ConfigOption } from '../types'

const props = defineProps<{
  option: ConfigOption
  modelValue: string | string[] | undefined
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | string[]]
}>()

const single = computed({
  get: () => (Array.isArray(props.modelValue) ? props.modelValue[0] : (props.modelValue ?? '')),
  set: (v: string) => emit('update:modelValue', v),
})

/** Checkbox toggles a value within the array model. */
function toggle(value: string, checked: boolean): void {
  const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
  const next = checked ? [...current, value] : current.filter((v) => v !== value)
  emit('update:modelValue', next)
}

function isChecked(value: string): boolean {
  return Array.isArray(props.modelValue) && props.modelValue.includes(value)
}

const maxLength = computed(() => Number(props.option.config?.max_length ?? 0) || undefined)
</script>

<template>
  <div class="option-field" :data-type="option.type">
    <label class="option-field__label">
      {{ option.label }}
      <span v-if="option.is_required" class="option-field__required">*</span>
    </label>
    <p v-if="option.help_text" class="option-field__help">{{ option.help_text }}</p>

    <!-- select -->
    <select v-if="option.type === 'select'" v-model="single">
      <option value="" disabled>Selecciona…</option>
      <option v-for="v in option.values" :key="v.id" :value="v.value">{{ v.label }}</option>
    </select>

    <!-- radio -->
    <div v-else-if="option.type === 'radio'" class="option-field__choices">
      <label v-for="v in option.values" :key="v.id" class="option-field__choice">
        <input type="radio" :name="option.key" :value="v.value" :checked="single === v.value"
          @change="single = v.value" />
        {{ v.label }}
      </label>
    </div>

    <!-- checkbox -->
    <div v-else-if="option.type === 'checkbox'" class="option-field__choices">
      <label v-for="v in option.values" :key="v.id" class="option-field__choice">
        <input type="checkbox" :value="v.value" :checked="isChecked(v.value)"
          @change="toggle(v.value, ($event.target as HTMLInputElement).checked)" />
        {{ v.label }}
      </label>
    </div>

    <!-- color swatches -->
    <div v-else-if="option.type === 'color'" class="option-field__swatches">
      <button v-for="v in option.values" :key="v.id" type="button" class="swatch"
        :class="{ 'swatch--active': single === v.value }"
        :style="{ background: (v.metadata?.hex as string) ?? '#ccc' }" :title="v.label"
        @click="single = v.value"></button>
    </div>

    <!-- image choices -->
    <div v-else-if="option.type === 'image'" class="option-field__images">
      <button v-for="v in option.values" :key="v.id" type="button" class="image-choice"
        :class="{ 'image-choice--active': single === v.value }" @click="single = v.value">
        <img :src="(v.metadata?.image as string) ?? ''" :alt="v.label" />
        <span>{{ v.label }}</span>
      </button>
    </div>

    <!-- free text -->
    <input v-else-if="option.type === 'text'" v-model="single" type="text" :maxlength="maxLength" />

    <!-- textarea -->
    <textarea v-else-if="option.type === 'textarea'" v-model="single" :maxlength="maxLength" rows="3" />
  </div>
</template>
