<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import TreeTable from 'primevue/treetable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import type { TreeNode } from 'primevue/treenode'

import { adminPanelService, type CmsMenu, type CmsMenuItem } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { success, error } = useToast()
const { confirm } = useConfirm()

const locationOptions = [
  { label: 'Header', value: 'header' },
  { label: 'Footer', value: 'footer' },
  { label: 'Sidebar', value: 'sidebar' },
]

const targetOptions = [
  { label: 'Misma pestaña', value: '_self' },
  { label: 'Pestaña nueva', value: '_blank' },
]

const menus = ref<CmsMenu[]>([])
const loading = ref(true)
const saving = ref(false)
const expandedKeys = ref<Record<string, boolean>>({})

// --- TreeTable nodes: menús como raíz, enlaces e hijos anidados ---
interface MenuNodeData {
  type: 'menu' | 'item'
  label: string
  url: string | null
  location?: string
  position: number | null
  menuId: number
  item?: CmsMenuItem
  menu?: CmsMenu
  depth: number
}

function itemToNode(item: CmsMenuItem, menuId: number, depth: number): TreeNode {
  return {
    key: `item-${item.id}`,
    data: {
      type: 'item',
      label: item.label,
      url: item.url,
      position: item.position,
      menuId,
      item,
      depth,
    } satisfies MenuNodeData,
    children: (item.children ?? []).map((child) => itemToNode(child, menuId, depth + 1)),
  }
}

const nodes = computed<TreeNode[]>(() =>
  menus.value.map((menu) => ({
    key: `menu-${menu.id}`,
    data: {
      type: 'menu',
      label: menu.name,
      url: null,
      location: menu.location,
      position: null,
      menuId: menu.id,
      menu,
      depth: 0,
    } satisfies MenuNodeData,
    children: (menu.items ?? []).map((item) => itemToNode(item, menu.id, 1)),
  })),
)

function expandAll(): void {
  const keys: Record<string, boolean> = {}
  const walk = (list: TreeNode[]) => {
    for (const node of list) {
      if (node.children?.length) {
        keys[node.key as string] = true
        walk(node.children)
      }
    }
  }
  walk(nodes.value)
  expandedKeys.value = keys
}

async function loadMenus(expand = true): Promise<void> {
  try {
    menus.value = await adminPanelService.menus()
    if (expand) expandAll()
  } catch {
    error('Error al cargar los menus')
  } finally {
    loading.value = false
  }
}

onMounted(() => loadMenus())

// --- Menú: crear / editar / eliminar ---
const menuDialog = ref(false)
const menuForm = reactive({ id: null as number | null, name: '', location: 'header' })

function openMenuDialog(menu?: CmsMenu): void {
  menuForm.id = menu?.id ?? null
  menuForm.name = menu?.name ?? ''
  menuForm.location = menu?.location ?? 'header'
  menuDialog.value = true
}

async function saveMenu(): Promise<void> {
  if (!menuForm.name.trim()) return
  saving.value = true
  try {
    if (menuForm.id === null) {
      await adminPanelService.createMenu({ name: menuForm.name.trim(), location: menuForm.location })
      success('Menu creado exitosamente')
    } else {
      await adminPanelService.updateMenu(menuForm.id, { name: menuForm.name.trim(), location: menuForm.location })
      success('Menu actualizado')
    }
    menuDialog.value = false
    await loadMenus(false)
  } catch {
    error('Error al guardar el menu')
  } finally {
    saving.value = false
  }
}

async function deleteMenu(menu: CmsMenu): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar menu',
    message: `¿Eliminar el menu "${menu.name}" y todos sus enlaces? Esta accion no se puede deshacer.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await adminPanelService.deleteMenu(menu.id)
    menus.value = menus.value.filter((m) => m.id !== menu.id)
    success('Menu eliminado')
  } catch {
    error('Error al eliminar el menu')
  }
}

// --- Enlaces (menu items): crear / editar / eliminar ---
const itemDialog = ref(false)
const itemForm = reactive({
  id: null as number | null,
  menuId: null as number | null,
  parentId: null as number | null,
  parentLabel: '' as string,
  label: '',
  url: '',
  target: '_self' as '_self' | '_blank',
  position: 0,
})

function openItemDialog(menuId: number, parent?: CmsMenuItem, item?: CmsMenuItem): void {
  itemForm.id = item?.id ?? null
  itemForm.menuId = menuId
  itemForm.parentId = item ? (item.parent_id ?? null) : (parent?.id ?? null)
  itemForm.parentLabel = parent?.label ?? ''
  itemForm.label = item?.label ?? ''
  itemForm.url = item?.url ?? ''
  itemForm.target = (item?.target as '_self' | '_blank') ?? '_self'
  itemForm.position = item?.position ?? 0
  itemDialog.value = true
}

async function saveItem(): Promise<void> {
  if (!itemForm.label.trim() || itemForm.menuId === null) return
  saving.value = true
  const payload = {
    label: itemForm.label.trim(),
    url: itemForm.url.trim() || null,
    target: itemForm.target,
    parent_id: itemForm.parentId,
    position: itemForm.position,
  }
  try {
    if (itemForm.id === null) {
      await adminPanelService.createMenuItem(itemForm.menuId, payload)
      success('Enlace agregado al menu')
    } else {
      await adminPanelService.updateMenuItem(itemForm.menuId, itemForm.id, payload)
      success('Enlace actualizado')
    }
    itemDialog.value = false
    await loadMenus(false)
  } catch {
    error('Error al guardar el enlace')
  } finally {
    saving.value = false
  }
}

async function deleteItem(menuId: number, item: CmsMenuItem): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar enlace',
    message: `¿Eliminar el enlace "${item.label}"? Sus subenlaces tambien se eliminaran.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await adminPanelService.deleteMenuItem(menuId, item.id)
    success('Enlace eliminado')
    await loadMenus(false)
  } catch {
    error('Error al eliminar el enlace')
  }
}

function locationLabel(location?: string): string {
  const map: Record<string, string> = { header: 'Header', footer: 'Footer', sidebar: 'Sidebar' }
  return location ? (map[location] ?? location) : ''
}
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Menus de Navegacion</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Contenido <span>/</span> Menus</div>
      </div>
      <Button label="Nuevo Menu" icon="pi pi-plus" @click="openMenuDialog()" />
    </div>

    <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <Card v-else>
      <template #content>
        <p v-if="menus.length === 0" style="text-align:center; padding:2.5rem; color:var(--admin-text-muted); margin:0;">
          No hay menus registrados. Crea uno con el boton "Nuevo Menu".
        </p>

        <TreeTable
          v-else
          :value="nodes"
          v-model:expandedKeys="expandedKeys"
          class="menus-tree"
        >
          <Column field="label" header="Menu / Enlace" expander style="min-width:16rem;">
            <template #body="{ node }">
              <span
                class="menus-tree__label"
                :class="{ 'menus-tree__label--menu': node.data.type === 'menu' }"
              >
                <i
                  :class="node.data.type === 'menu' ? 'pi pi-bars' : 'pi pi-link'"
                  class="menus-tree__label-icon"
                />
                {{ node.data.label }}
              </span>
            </template>
          </Column>

          <Column field="url" header="URL" style="min-width:12rem;">
            <template #body="{ node }">
              <code v-if="node.data.url" class="menus-tree__url">{{ node.data.url }}</code>
              <span v-else-if="node.data.type === 'item'" class="menus-tree__muted">—</span>
            </template>
          </Column>

          <Column header="Ubicacion / Posicion" style="width:11rem;">
            <template #body="{ node }">
              <Tag v-if="node.data.type === 'menu'" :value="locationLabel(node.data.location)" severity="secondary" />
              <span v-else class="menus-tree__muted">Pos. {{ node.data.position }}</span>
            </template>
          </Column>

          <Column header="Acciones" style="width:9rem;">
            <template #body="{ node }">
              <!-- Menú raíz -->
              <div v-if="node.data.type === 'menu'" class="menus-tree__actions">
                <Button
                  v-tooltip.top="'Agregar enlace'"
                  icon="pi pi-plus"
                  size="small"
                  severity="secondary"
                  text
                  rounded
                  aria-label="Agregar enlace"
                  @click="openItemDialog(node.data.menuId)"
                />
                <Button
                  v-tooltip.top="'Editar menu'"
                  icon="pi pi-pencil"
                  size="small"
                  severity="info"
                  text
                  rounded
                  aria-label="Editar menu"
                  @click="openMenuDialog(node.data.menu)"
                />
                <Button
                  v-tooltip.top="'Eliminar menu'"
                  icon="pi pi-trash"
                  size="small"
                  severity="danger"
                  text
                  rounded
                  aria-label="Eliminar menu"
                  @click="deleteMenu(node.data.menu)"
                />
              </div>

              <!-- Enlace / subenlace -->
              <div v-else class="menus-tree__actions">
                <Button
                  v-if="node.data.depth === 1"
                  v-tooltip.top="'Agregar submenu'"
                  icon="pi pi-plus"
                  size="small"
                  severity="secondary"
                  text
                  rounded
                  aria-label="Agregar submenu"
                  @click="openItemDialog(node.data.menuId, node.data.item)"
                />
                <Button
                  v-tooltip.top="'Editar enlace'"
                  icon="pi pi-pencil"
                  size="small"
                  severity="info"
                  text
                  rounded
                  aria-label="Editar enlace"
                  @click="openItemDialog(node.data.menuId, undefined, node.data.item)"
                />
                <Button
                  v-tooltip.top="'Eliminar enlace'"
                  icon="pi pi-trash"
                  size="small"
                  severity="danger"
                  text
                  rounded
                  aria-label="Eliminar enlace"
                  @click="deleteItem(node.data.menuId, node.data.item)"
                />
              </div>
            </template>
          </Column>
        </TreeTable>
      </template>
    </Card>

    <!-- Menu dialog (crear / editar) -->
    <Dialog
      v-model:visible="menuDialog"
      modal
      :header="menuForm.id === null ? 'Crear nuevo menu' : 'Editar menu'"
      :style="{ width: '440px' }"
    >
      <div class="menu-field">
        <label>Nombre</label>
        <InputText v-model="menuForm.name" fluid placeholder="Nombre del menu" />
      </div>
      <div class="menu-field">
        <label>Ubicacion</label>
        <Select v-model="menuForm.location" :options="locationOptions" optionLabel="label" optionValue="value" fluid />
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined :disabled="saving" @click="menuDialog = false" />
        <Button :label="saving ? 'Guardando...' : 'Guardar'" :loading="saving" :disabled="!menuForm.name.trim()" @click="saveMenu" />
      </template>
    </Dialog>

    <!-- Item dialog (crear / editar enlace) -->
    <Dialog
      v-model:visible="itemDialog"
      modal
      :header="itemForm.id === null ? (itemForm.parentLabel ? `Agregar submenu a “${itemForm.parentLabel}”` : 'Agregar enlace') : 'Editar enlace'"
      :style="{ width: '480px' }"
    >
      <div class="menu-field">
        <label>Etiqueta</label>
        <InputText v-model="itemForm.label" fluid placeholder="Ej. Pasteles de temporada" />
      </div>
      <div class="menu-field">
        <label>URL</label>
        <InputText v-model="itemForm.url" fluid placeholder="/catalogo/pasteles" />
      </div>
      <div style="display:flex; gap:1rem;">
        <div class="menu-field" style="flex:1;">
          <label>Abrir en</label>
          <Select v-model="itemForm.target" :options="targetOptions" optionLabel="label" optionValue="value" fluid />
        </div>
        <div class="menu-field" style="width:8rem;">
          <label>Posicion</label>
          <InputNumber v-model="itemForm.position" :min="0" fluid showButtons />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined :disabled="saving" @click="itemDialog = false" />
        <Button :label="saving ? 'Guardando...' : 'Guardar'" :loading="saving" :disabled="!itemForm.label.trim()" @click="saveItem" />
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
.menus-tree__label {
  font-size: 0.875rem;
  color: var(--admin-text);
}
.menus-tree__label--menu {
  font-weight: 600;
}
.menus-tree__label-icon {
  font-size: 0.75rem;
  color: var(--admin-text-muted);
  margin-right: 0.35rem;
}
.menus-tree__url {
  font-size: 0.78rem;
  color: var(--admin-text-secondary);
  background: var(--admin-bg);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
}
.menus-tree__muted {
  font-size: 0.8rem;
  color: var(--admin-text-muted);
}
.menus-tree__actions {
  display: flex;
  gap: 0.2rem;
}
</style>
