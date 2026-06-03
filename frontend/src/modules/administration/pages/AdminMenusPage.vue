<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'

import { adminPanelService, type CmsMenu, type CmsMenuItem } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'

const { success, error } = useToast()

const menus = ref<CmsMenu[]>([])
const loading = ref(true)

// --- New menu form ---
const showNewForm = ref(false)
const newForm = reactive({ name: '', location: 'header' as 'header' | 'footer' | 'sidebar' })
const saving = ref(false)

// --- Edit state ---
interface EditState { name: string; location: string }
const editingId = ref<string | null>(null)
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

async function deleteMenu(id: string): Promise<void> {
  if (!confirm('¿Eliminar este menu? Esta accion no se puede deshacer.')) return
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
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Menus de Navegacion</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Contenido <span>/</span> Menus
        </div>
      </div>
      <button class="admin-btn admin-btn--primary" @click="showNewForm = !showNewForm">
        Nuevo Menu
      </button>
    </div>

    <!-- New menu form -->
    <div v-if="showNewForm" class="admin-content-card" style="margin-bottom: 1.5rem;">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Crear nuevo menu</h3>
      </div>
      <div class="admin-content-card__body">
        <div class="admin-form-row">
          <label>
            Nombre
            <input v-model="newForm.name" type="text" placeholder="Nombre del menu" />
          </label>
          <label>
            Ubicacion
            <select v-model="newForm.location">
              <option value="header">Header</option>
              <option value="footer">Footer</option>
              <option value="sidebar">Sidebar</option>
            </select>
          </label>
          <button
            class="admin-btn admin-btn--primary admin-btn--sm"
            :disabled="saving || !newForm.name.trim()"
            @click="createMenu"
          >
            {{ saving ? 'Guardando...' : 'Guardar' }}
          </button>
          <button
            class="admin-btn admin-btn--sm"
            :disabled="saving"
            @click="showNewForm = false"
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
      Cargando menus...
    </p>

    <!-- Empty state -->
    <div
      v-else-if="menus.length === 0"
      style="text-align: center; padding: 3rem; color: var(--admin-text-muted);"
    >
      No hay menus registrados. Crea uno con el boton "Nuevo Menu".
    </div>

    <!-- Menu cards -->
    <template v-else>
      <div
        v-for="menu in menus"
        :key="menu.id"
        class="admin-content-card admin-menu-card"
      >
        <!-- Card header -->
        <div class="admin-content-card__header admin-menu-header">
          <!-- Editing mode -->
          <template v-if="editingId === menu.id">
            <div class="admin-form-row" style="margin-bottom: 0;">
              <label>
                Nombre
                <input v-model="editForm.name" type="text" />
              </label>
              <label>
                Ubicacion
                <select v-model="editForm.location">
                  <option value="header">Header</option>
                  <option value="footer">Footer</option>
                  <option value="sidebar">Sidebar</option>
                </select>
              </label>
            </div>
          </template>

          <!-- View mode -->
          <template v-else>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
              <h3 class="admin-content-card__title">{{ menu.name }}</h3>
              <span class="admin-badge">{{ locationLabel(menu.location) }}</span>
            </div>
          </template>

          <!-- Actions -->
          <div class="admin-menu-actions">
            <template v-if="editingId === menu.id">
              <button
                class="admin-btn admin-btn--primary admin-btn--sm"
                :disabled="saving"
                @click="saveEdit(menu)"
              >
                {{ saving ? 'Guardando...' : 'Guardar' }}
              </button>
              <button
                class="admin-btn admin-btn--sm"
                :disabled="saving"
                @click="cancelEdit"
              >
                Cancelar
              </button>
            </template>
            <template v-else>
              <button class="admin-btn admin-btn--sm" @click="startEdit(menu)">Editar</button>
              <button
                class="admin-btn admin-btn--sm"
                style="color: var(--admin-danger, #e53e3e);"
                @click="deleteMenu(menu.id)"
              >
                Eliminar
              </button>
            </template>
          </div>
        </div>

        <!-- Card body: menu items -->
        <div class="admin-content-card__body">
          <template v-if="menu.items && menu.items.length > 0">
            <ul class="admin-menu-items">
              <li v-for="item in menu.items" :key="item.id">
                <div class="admin-menu-item">
                  <span>
                    <strong>{{ item.label }}</strong>
                    <span style="margin-left: 0.5rem; color: var(--admin-text-muted); font-size: 0.8rem;">{{ item.url }}</span>
                  </span>
                  <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Pos. {{ item.position }}</span>
                </div>
                <ul v-if="item.children && item.children.length > 0" class="admin-menu-items admin-menu-children">
                  <li v-for="child in item.children" :key="child.id">
                    <div class="admin-menu-item">
                      <span>
                        <strong>{{ child.label }}</strong>
                        <span style="margin-left: 0.5rem; color: var(--admin-text-muted); font-size: 0.8rem;">{{ child.url }}</span>
                      </span>
                      <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Pos. {{ child.position }}</span>
                    </div>
                  </li>
                </ul>
              </li>
            </ul>
          </template>
          <p v-else style="color: var(--admin-text-muted); margin: 0;">
            Este menu no tiene elementos.
          </p>
        </div>
      </div>
    </template>
  </div>
</template>


<style scoped>
.admin-menu-card { margin-bottom: 1rem; }
.admin-menu-header { display: flex; justify-content: space-between; align-items: center; }
.admin-menu-actions { display: flex; gap: 0.5rem; }
.admin-menu-items { list-style: none; padding: 0; margin: 0; }
.admin-menu-item { padding: 0.5rem 0; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center; }
.admin-menu-item:last-child { border-bottom: none; }
.admin-menu-children { padding-left: 1.5rem; }
.admin-form-row { display: flex; gap: 0.75rem; align-items: end; margin-bottom: 1rem; }
.admin-form-row label { display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.8rem; font-weight: 500; }
.admin-form-row input, .admin-form-row select { padding: 0.4rem 0.6rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.85rem; }
</style>
