<script setup lang="ts">
import type { Facets, FilterState } from '../types'

const props = defineProps<{
  facets: Facets | null
  filter: FilterState
  /** Hide the category facet on category landing pages. */
  hideCategories?: boolean
}>()

const emit = defineEmits<{ change: [] }>()

function changed(): void {
  props.filter.page = 1
  emit('change')
}

function toggleAttribute(code: string, value: string, checked: boolean): void {
  const current = props.filter.attributes[code] ?? []
  props.filter.attributes[code] = checked
    ? [...current, value]
    : current.filter((v) => v !== value)
  changed()
}

function isAttrChecked(code: string, value: string): boolean {
  return (props.filter.attributes[code] ?? []).includes(value)
}

function setCategory(slug: string): void {
  props.filter.category = props.filter.category === slug ? undefined : slug
  changed()
}

function setCollection(slug: string): void {
  props.filter.collection = props.filter.collection === slug ? undefined : slug
  changed()
}
</script>

<template>
  <aside class="filters" v-if="facets">
    <!-- Categories -->
    <div v-if="!hideCategories && facets.categories.length" class="filters__group">
      <h4>Categoría</h4>
      <ul>
        <li v-for="cat in facets.categories" :key="cat.id">
          <button type="button" class="filters__chip" :class="{ 'filters__chip--active': filter.category === cat.slug }"
            @click="setCategory(cat.slug)">{{ cat.name }}</button>
        </li>
      </ul>
    </div>

    <!-- Collections -->
    <div v-if="facets.collections.length" class="filters__group">
      <h4>Colección</h4>
      <ul>
        <li v-for="col in facets.collections" :key="col.id">
          <button type="button" class="filters__chip" :class="{ 'filters__chip--active': filter.collection === col.slug }"
            @click="setCollection(col.slug)">{{ col.name }}</button>
        </li>
      </ul>
    </div>

    <!-- Price range -->
    <div class="filters__group">
      <h4>Precio</h4>
      <div class="filters__price">
        <input type="number" v-model.number="filter.price_min" placeholder="Mín" @change="changed" />
        <input type="number" v-model.number="filter.price_max" placeholder="Máx" @change="changed" />
      </div>
    </div>

    <!-- Dynamic, admin-defined attribute filters -->
    <div v-for="attr in facets.attributes" :key="attr.id" class="filters__group">
      <h4>{{ attr.name }}</h4>
      <div v-if="attr.type === 'color'" class="filters__swatches">
        <button v-for="val in attr.values" :key="val.id" type="button" class="swatch"
          :class="{ 'swatch--active': isAttrChecked(attr.code, val.value) }"
          :style="{ background: (val.metadata?.hex as string) ?? '#ccc' }" :title="val.label"
          @click="toggleAttribute(attr.code, val.value, !isAttrChecked(attr.code, val.value))"></button>
      </div>
      <ul v-else>
        <li v-for="val in attr.values" :key="val.id">
          <label>
            <input type="checkbox" :checked="isAttrChecked(attr.code, val.value)"
              @change="toggleAttribute(attr.code, val.value, ($event.target as HTMLInputElement).checked)" />
            {{ val.label }}
          </label>
        </li>
      </ul>
    </div>
  </aside>
</template>
