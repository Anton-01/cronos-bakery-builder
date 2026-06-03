<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'

import { adminPanelService, type AdminCategory } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'

const { success, error } = useToast()

const categories = ref<AdminCategory[]>([])
const loading = ref(true)
const showForm = ref(false)
const editingId = ref<string | null>(null)
const saving = ref(false)

const form = reactive<{ name: string; slug: string; position: number; parent_id: string }>({
  name: '',
  slug: '',
  position: 0,
  parent_id: '',
})

function openNew(): void {
  editingId.value = null
  form.name = ''
  form.slug = ''
  form.position = 0
  form.parent_id = ''
  showForm.value = true
}

function openEdit(cat: AdminCategory): void {
  editingId.value = cat.id
  form.name = cat.name
  form.slug = cat.slug
  form.position = cat.position
  form.parent_id = cat.parent_id ?? ''
  showForm.value = true
}

function cancelForm(): void {
  showForm.value = false
  editingId.value = null
}

function parentName(parentId: string | null): string {
  if (!parentId) return '—'
  const found = categories.value.find((c) => c.id === parentId)
  return found ? found.name : '—'
}

async function load(): Promise<void> {
  loading.value = true
  try {
    categories.value = await adminPanelService.categories()
  } finally {
    loading.value = false
  }
}

async function save(): Promise<void> {
  saving.value = true
  try {
    const data: Partial<AdminCategory> = {
      name: form.name,
      slug: form.slug,
      position: form.position,
      parent_id: form.parent_id || null,
    }
    if (editingId.value) {
      await adminPanelService.updateCategory(editingId.value, data)
    } else {
      await adminPanelService.createCategory(data)
    }
    success(editingId.value ? 'Categoria actualizada' : 'Categoria creada exitosamente')
    showForm.value = false
    editingId.value = null
    await load()
  } catch {
    error('Error al guardar la categoria')
  } finally {
    saving.value = false
  }
}

async function deleteCategory(id: string): Promise<void> {
  if (!confirm('¿Eliminar esta categoria?')) return
  try {
    await adminPanelService.deleteCategory(id)
    await load()
    success('Categoria eliminada')
  } catch {
    error('Error al eliminar la categoria')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Categorias</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Catalogo <span>/</span> Categorias
        </div>
      </div>
      <div>
        <button class="admin-btn admin-btn--primary" @click="openNew">Nueva Categoria</button>
      </div>
    </div>

    <!-- New / Edit form -->
    <div v-if="showForm" class="admin-content-card admin-form-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">
          {{ editingId ? 'Editar Categoria' : 'Nueva Categoria' }}
        </h3>
      </div>
      <div class="admin-content-card__body">
        <div class="admin-form-grid">
          <div class="admin-form-group">
            <label>Nombre</label>
            <input v-model="form.name" type="text" placeholder="Nombre" />
          </div>
          <div class="admin-form-group">
            <label>Slug</label>
            <input v-model="form.slug" type="text" placeholder="slug-url" />
          </div>
          <div class="admin-form-group">
            <label>Posicion</label>
            <input v-model.number="form.position" type="number" placeholder="0" />
          </div>
          <div class="admin-form-group">
            <label>Padre</label>
            <select v-model="form.parent_id">
              <option value="">— Sin padre —</option>
              <option
                v-for="cat in categories"
                :key="cat.id"
                :value="cat.id"
                :disabled="cat.id === editingId"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <button class="admin-btn admin-btn--primary" :disabled="saving" @click="save">
            {{ saving ? 'Guardando...' : 'Guardar' }}
          </button>
          <button class="admin-btn" @click="cancelForm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- Categories table -->
    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Listado de Categorias</h3>
      </div>
      <div class="admin-content-card__body">
        <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
          Cargando categorias...
        </p>

        <template v-else>
          <p v-if="categories.length === 0" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
            No hay categorias registradas.
          </p>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Slug</th>
                <th>Posicion</th>
                <th>Productos</th>
                <th>Padre</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in categories" :key="cat.id">
                <td>
                  <span class="admin-badge">{{ cat.name }}</span>
                </td>
                <td>{{ cat.slug }}</td>
                <td>{{ cat.position }}</td>
                <td>{{ cat.products_count ?? 0 }}</td>
                <td>{{ parentName(cat.parent_id) }}</td>
                <td style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                  <button class="admin-btn admin-btn--sm" @click="openEdit(cat)">Editar</button>
                  <button class="admin-btn admin-btn--sm" style="color: var(--admin-error, #e53e3e);" @click="deleteCategory(cat.id)">
                    Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-form-card { margin-bottom: 1.5rem; }
.admin-form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.admin-form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.admin-form-group label { font-size: 0.8rem; font-weight: 500; color: var(--admin-text-muted); }
.admin-form-group input, .admin-form-group select { padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.875rem; }
</style>
