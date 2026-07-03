<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

import { adminPanelService, type CmsMenu } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { success, error } = useToast()
const { confirm } = useConfirm()

const locationOptions = [
  { label: 'Header', value: 'header' },
  { label: 'Footer', value: 'footer' },
  { label: 'Sidebar', value: 'sidebar' },
]

const menus = ref<CmsMenu[]>([])
const loading = ref(true)

const showNewForm = ref(false)
const newForm = reactive({ name: '', location: 'header' as 'header' | 'footer' | 'sidebar' })
const saving = ref(false)

interface EditState { name: string; location: string }
const editingId = ref<number | null>(null)
const editForm = reactive<EditState>({ name: '', location: '' })

onMounted(async () => {
  try {
    menus.value = await adminPanelService.menus()
  } finally {
    loading.value = false
  }
})

async function createMenu(): Promise<void> {
  if (!newForm.name.trim()) return
  saving.value = true
  try {
    const created = await adminPanelService.createMenu({ name: newForm.name.trim(), location: newForm.location })
    menus.value.push(created)
    newForm.name = ''
    newForm.location = 'header'
    showNewForm.value = false
    success('Menu creado exitosamente')
  } catch {
    error('Error al crear el menu')
  } finally {
    saving.value = false
  }
}

function startEdit(menu: CmsMenu): void {
  editingId.value = menu.id
  editForm.name = menu.name
  editForm.location = menu.location
}

function cancelEdit(): void {
  editingId.value = null
  editForm.name = ''
  editForm.location = ''
}

async function saveEdit(menu: CmsMenu): Promise<void> {
  if (!editForm.name.trim()) return
  saving.value = true
  try {
    const updated = await adminPanelService.updateMenu(menu.id, {
      name: editForm.name.trim(),
      location: editForm.location,
    })
    const idx = menus.value.findIndex((m) => m.id === menu.id)
    if (idx !== -1) menus.value[idx] = updated
    cancelEdit()
    success('Menu actualizado')
  } catch {
    error('Error al actualizar el menu')
  } finally {
    saving.value = false
  }
}

async function deleteMenu(id: number): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar menu',
    message: '¿Eliminar este menu? Esta accion no se puede deshacer.',
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await adminPanelService.deleteMenu(id)
    menus.value = menus.value.filter((m) => m.id !== id)
    success('Menu eliminado')
  } catch {
    error('Error al eliminar el menu')
  }
}

function locationLabel(location: string): string {
  const map: Record<string, string> = { header: 'Header', footer: 'Footer', sidebar: 'Sidebar' }
  return map[location] ?? location
}
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Menus de Navegacion</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Contenido <span>/</span> Menus</div>
      </div>
      <Button label="Nuevo Menu" icon="pi pi-plus" @click="showNewForm = true" />
    </div>

    <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <template v-else>
      <p v-if="menus.length === 0 && !showNewForm" style="text-align:center; padding:3rem; color:var(--admin-text-muted);">
        No hay menus registrados. Crea uno con el boton "Nuevo Menu".
      </p>

      <Card v-for="menu in menus" :key="menu.id" style="margin-bottom:1rem;">
        <template #content>
          <!-- Editing mode -->
          <template v-if="editingId === menu.id">
            <div style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap; margin-bottom:1rem;">
              <div class="menu-field" style="flex:1; min-width:200px;">
                <label>Nombre</label>
                <InputText v-model="editForm.name" fluid />
              </div>
              <div class="menu-field" style="min-width:160px;">
                <label>Ubicacion</label>
                <Select v-model="editForm.location" :options="locationOptions" optionLabel="label" optionValue="value" fluid />
              </div>
              <div style="display:flex; gap:0.5rem; padding-bottom:0.1rem;">
                <Button :label="saving ? 'Guardando...' : 'Guardar'" :loading="saving" size="small" @click="saveEdit(menu)" />
                <Button label="Cancelar" severity="secondary" outlined size="small" :disabled="saving" @click="cancelEdit" />
              </div>
            </div>
          </template>

          <!-- View mode -->
          <template v-else>
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
              <div style="display:flex; align-items:center; gap:0.75rem;">
                <h3 style="margin:0; font-size:1rem; font-weight:600;">{{ menu.name }}</h3>
                <Tag :value="locationLabel(menu.location)" severity="secondary" />
              </div>
              <div style="display:flex; gap:0.25rem;">
                <Button icon="pi pi-pencil" size="small" severity="info" text rounded title="Editar" @click="startEdit(menu)" />
                <Button icon="pi pi-trash" size="small" severity="danger" text rounded title="Eliminar" @click="deleteMenu(menu.id)" />
              </div>
            </div>
          </template>

          <!-- Menu items -->
          <template v-if="menu.items && menu.items.length > 0">
            <ul class="menu-items-list">
              <li v-for="item in menu.items" :key="item.id">
                <div class="menu-item-row">
                  <span>
                    <strong>{{ item.label }}</strong>
                    <small style="margin-left:0.5rem; color:var(--admin-text-muted);">{{ item.url }}</small>
                  </span>
                  <small style="color:var(--admin-text-muted);">Pos. {{ item.position }}</small>
                </div>
                <ul v-if="item.children && item.children.length > 0" class="menu-items-list menu-items-list--nested">
                  <li v-for="child in item.children" :key="child.id">
                    <div class="menu-item-row">
                      <span>
                        <strong>{{ child.label }}</strong>
                        <small style="margin-left:0.5rem; color:var(--admin-text-muted);">{{ child.url }}</small>
                      </span>
                      <small style="color:var(--admin-text-muted);">Pos. {{ child.position }}</small>
                    </div>
                  </li>
                </ul>
              </li>
            </ul>
          </template>
          <p v-else style="color:var(--admin-text-muted); margin:0; font-size:0.85rem;">Este menu no tiene elementos.</p>
        </template>
      </Card>
    </template>

    <!-- New menu dialog -->
    <Dialog v-model:visible="showNewForm" modal header="Crear nuevo menu" :style="{ width: '440px' }">
      <div class="menu-field">
        <label>Nombre</label>
        <InputText v-model="newForm.name" fluid placeholder="Nombre del menu" />
      </div>
      <div class="menu-field">
        <label>Ubicacion</label>
        <Select v-model="newForm.location" :options="locationOptions" optionLabel="label" optionValue="value" fluid />
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined :disabled="saving" @click="showNewForm = false" />
        <Button :label="saving ? 'Guardando...' : 'Guardar'" :loading="saving" :disabled="!newForm.name.trim()" @click="createMenu" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.menu-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.menu-field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
.menu-items-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.menu-item-row {
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--admin-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}
.menu-item-row:last-child { border-bottom: none; }
.menu-items-list--nested { padding-left: 1.5rem; }
</style>
