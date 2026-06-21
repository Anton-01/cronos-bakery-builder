<script setup lang="ts">
import { onMounted, ref, reactive, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

import { adminPanelService, type AdminCategory } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { success, error } = useToast()
const { confirm } = useConfirm()

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

const parentOptions = computed(() =>
  [{ label: '— Sin padre —', value: '' }, ...categories.value
    .filter((c) => c.id !== editingId.value)
    .map((c) => ({ label: c.name, value: c.id }))]
)

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
  const ok = await confirm({
    title: 'Eliminar categoria',
    message: '¿Eliminar esta categoria?',
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
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
    <div class="admin-page-header">
      <div>
        <h1>Categorias</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Catalogo <span>/</span> Categorias</div>
      </div>
      <Button label="Nueva Categoria" icon="pi pi-plus" @click="openNew" />
    </div>

    <Card>
      <template #title>Listado de Categorias</template>
      <template #content>
        <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>

        <DataTable v-else :value="categories" class="p-datatable-sm">
          <template #empty>
            <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">No hay categorias registradas.</div>
          </template>

          <Column header="Nombre" field="name">
            <template #body="{ data }">
              <span style="font-weight:500;">{{ data.name }}</span>
            </template>
          </Column>

          <Column header="Slug" field="slug">
            <template #body="{ data }">
              <small style="font-family:monospace; color:var(--admin-primary); opacity:0.75;">{{ data.slug }}</small>
            </template>
          </Column>

          <Column header="Posicion" field="position" style="width:100px;" />

          <Column header="Productos" style="width:100px;">
            <template #body="{ data }">{{ data.products_count ?? 0 }}</template>
          </Column>

          <Column header="Padre" style="width:140px;">
            <template #body="{ data }">{{ parentName(data.parent_id) }}</template>
          </Column>

          <Column header="Acciones" style="width:120px;">
            <template #body="{ data }">
              <div style="display:flex; gap:0.25rem;">
                <Button icon="pi pi-pencil" size="small" severity="info" text rounded title="Editar" @click="openEdit(data)" />
                <Button icon="pi pi-trash" size="small" severity="danger" text rounded title="Eliminar" @click="deleteCategory(data.id)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Form dialog -->
    <Dialog
      v-model:visible="showForm"
      modal
      :header="editingId ? 'Editar Categoria' : 'Nueva Categoria'"
      :style="{ width: '480px' }"
      @hide="editingId = null"
    >
      <form @submit.prevent="save">
        <div class="cat-field">
          <label>Nombre</label>
          <InputText v-model="form.name" fluid required placeholder="Nombre" />
        </div>
        <div class="cat-field">
          <label>Slug</label>
          <InputText v-model="form.slug" fluid required placeholder="slug-url" style="font-family:monospace;" />
        </div>
        <div class="cat-field">
          <label>Posicion</label>
          <InputNumber v-model="form.position" fluid :min="0" />
        </div>
        <div class="cat-field">
          <label>Categoria padre</label>
          <Select v-model="form.parent_id" :options="parentOptions" optionLabel="label" optionValue="value" fluid />
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined @click="showForm = false" />
        <Button :label="saving ? 'Guardando...' : 'Guardar'" :loading="saving" @click="save" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.cat-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.cat-field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
</style>
