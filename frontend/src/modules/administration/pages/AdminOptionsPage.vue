<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminPanelService, type AdminAttribute } from '../services/adminPanelService'

const attributes = ref<AdminAttribute[]>([])
const loading = ref(true)
const expandedIds = ref(new Set<string>())

function toggleExpand(id: string): void {
  if (expandedIds.value.has(id)) {
    expandedIds.value.delete(id)
  } else {
    expandedIds.value.add(id)
  }
}

function isExpanded(id: string): boolean {
  return expandedIds.value.has(id)
}

function metaString(metadata: Record<string, unknown> | null): string | null {
  if (!metadata || Object.keys(metadata).length === 0) return null
  return JSON.stringify(metadata)
}

onMounted(async () => {
  try {
    attributes.value = await adminPanelService.attributes()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Opciones de Producto</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Catalogo <span>/</span> Opciones
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
      Cargando opciones...
    </p>

    <!-- Empty state -->
    <div
      v-else-if="attributes.length === 0"
      style="text-align: center; padding: 3rem; color: var(--admin-text-muted);"
    >
      No hay opciones de producto registradas.
    </div>

    <!-- Attribute cards -->
    <template v-else>
      <div
        v-for="attribute in attributes"
        :key="attribute.id"
        class="admin-content-card admin-attribute-card"
      >
        <!-- Card header (clickable to expand/collapse) -->
        <div
          class="admin-content-card__header admin-attribute-header"
          @click="toggleExpand(attribute.id)"
        >
          <h3 class="admin-content-card__title">{{ attribute.name }}</h3>

          <div class="admin-attribute-meta">
            <span class="admin-badge admin-badge--default">{{ attribute.code }}</span>
            <span class="admin-badge admin-badge--info">{{ attribute.type }}</span>
            <span style="font-size: 0.85rem; color: var(--admin-text-muted); margin-left: 0.25rem;">
              {{ isExpanded(attribute.id) ? '▲' : '▼' }}
            </span>
          </div>
        </div>

        <!-- Card body (only when expanded) -->
        <div v-if="isExpanded(attribute.id)" class="admin-content-card__body">
          <table v-if="attribute.values.length > 0" class="admin-table">
            <thead>
              <tr>
                <th>Label</th>
                <th>Valor</th>
                <th>Posicion</th>
                <th>Metadata</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="val in attribute.values" :key="val.id">
                <td>{{ val.label }}</td>
                <td>{{ val.value }}</td>
                <td>{{ val.position }}</td>
                <td>
                  <span v-if="metaString(val.metadata)" style="font-family: monospace; font-size: 0.8rem;">
                    {{ metaString(val.metadata) }}
                  </span>
                  <span v-else style="color: var(--admin-text-muted);">—</span>
                </td>
              </tr>
            </tbody>
          </table>

          <p v-else style="color: var(--admin-text-muted); margin: 0;">
            Este atributo no tiene valores registrados.
          </p>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.admin-attribute-card { margin-bottom: 1rem; }
.admin-attribute-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
.admin-attribute-meta { display: flex; gap: 0.75rem; }
.admin-attribute-meta .admin-badge { font-size: 0.75rem; }
</style>
