<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'

import { useToast } from '@/composables/useToast'
import { mediaLibraryService, type AllowedFileType } from '@/services/mediaLibrary'

/**
 * Catálogo de tipos de archivo permitidos en la Media Library.
 * El Seeder Maestro puebla el catálogo completo; aquí el administrador
 * habitualmente solo prende/apaga cada tipo (`is_active`). La edición
 * avanzada (MIME/extensiones) queda disponible vía el diálogo.
 */
const { success, error } = useToast()

const types = ref<AllowedFileType[]>([])
const loading = ref(false)
const togglingId = ref<number | null>(null)

const editDialog = ref(false)
const editingId = ref<number | null>(null)
const form = reactive({
  name: '',
  description: '' as string | null,
  mimeTypesText: '',
  extensionsText: '',
  icon_reference: 'pi pi-file',
})

async function load() {
  loading.value = true
  try {
    types.value = await mediaLibraryService.fileTypes()
  } catch {
    error('Error al cargar los tipos de archivo')
  } finally {
    loading.value = false
  }
}

async function toggleActive(type: AllowedFileType, value: boolean) {
  togglingId.value = type.id
  const previous = type.is_active
  type.is_active = value
  try {
    await mediaLibraryService.updateFileType(type.id, { is_active: value })
    success(value ? `"${type.name}" habilitado para subidas` : `"${type.name}" deshabilitado`)
  } catch {
    type.is_active = previous
    error('Error al actualizar el tipo de archivo')
  } finally {
    togglingId.value = null
  }
}

function openEdit(type: AllowedFileType) {
  editingId.value = type.id
  form.name = type.name
  form.description = type.description
  form.mimeTypesText = type.mime_types.join(', ')
  form.extensionsText = type.extensions.join(', ')
  form.icon_reference = type.icon_reference
  editDialog.value = true
}

async function submitEdit() {
  if (editingId.value === null) return
  const mime_types = form.mimeTypesText.split(',').map((s) => s.trim()).filter(Boolean)
  const extensions = form.extensionsText.split(',').map((s) => s.trim().replace(/^\./, '')).filter(Boolean)
  if (!mime_types.length || !extensions.length) {
    error('Debe indicar al menos un MIME type y una extensión')
    return
  }
  try {
    const updated = await mediaLibraryService.updateFileType(editingId.value, {
      name: form.name,
      description: form.description || null,
      mime_types,
      extensions,
      icon_reference: form.icon_reference,
    })
    const idx = types.value.findIndex((t) => t.id === updated.id)
    if (idx !== -1) types.value[idx] = updated
    editDialog.value = false
    success('Tipo de archivo actualizado')
  } catch {
    error('Error al guardar el tipo de archivo')
  }
}

onMounted(() => { void load() })
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1 style="margin:0 0 0.25rem;">Tipos de Archivo</h1>
        <p style="margin:0; color:var(--admin-text-secondary); font-size:0.85rem;">
          Controla qué formatos acepta la Biblioteca de Medios. La validación de
          subidas se aplica dinámicamente según los tipos activos.
        </p>
      </div>
    </div>

    <Card>
      <template #content>
        <DataTable
          :value="types"
          :loading="loading"
          rowGroupMode="subheader"
          groupRowsBy="category"
          sortField="category"
          :sortOrder="1"
          size="small"
          dataKey="id"
        >
          <template #groupheader="{ data }">
            <span style="font-weight:700; font-size:0.85rem;">{{ data.category }}</span>
          </template>

          <Column field="name" header="Tipo" style="min-width:12rem;">
            <template #body="{ data }">
              <div style="display:flex; align-items:center; gap:0.6rem;">
                <i :class="data.icon_reference" style="font-size:1rem; color:var(--admin-primary);" />
                <span style="font-weight:600;">{{ data.name }}</span>
              </div>
            </template>
          </Column>

          <Column field="extensions" header="Extensiones" style="min-width:10rem;">
            <template #body="{ data }">
              <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                <Tag
                  v-for="ext in data.extensions"
                  :key="ext"
                  :value="`.${ext}`"
                  severity="secondary"
                  style="font-size:0.6rem;"
                />
              </div>
            </template>
          </Column>

          <Column field="description" header="Descripción" style="min-width:18rem;">
            <template #body="{ data }">
              <span style="font-size:0.8rem; color:var(--admin-text-secondary);">
                {{ data.description ?? '—' }}
              </span>
            </template>
          </Column>

          <Column field="is_active" header="Activo" style="width:6rem; text-align:center;">
            <template #body="{ data }">
              <ToggleSwitch
                v-tooltip.top="data.is_active ? 'Deshabilitar subidas de este tipo' : 'Habilitar subidas de este tipo'"
                :modelValue="data.is_active"
                :disabled="togglingId === data.id"
                :aria-label="`${data.is_active ? 'Deshabilitar' : 'Habilitar'} ${data.name}`"
                @update:modelValue="(v: boolean) => toggleActive(data, v)"
              />
            </template>
          </Column>

          <Column header="Acciones" style="width:5rem;">
            <template #body="{ data }">
              <Button
                v-tooltip.top="'Editar tipo de archivo'"
                icon="pi pi-pencil"
                size="small"
                severity="secondary"
                text
                rounded
                aria-label="Editar tipo de archivo"
                @click="openEdit(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Diálogo de edición avanzada -->
    <Dialog
      v-model:visible="editDialog"
      header="Editar tipo de archivo"
      modal
      :style="{ width: '540px' }"
    >
      <div style="display:flex; flex-direction:column; gap:0.9rem;">
        <div>
          <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Nombre</label>
          <InputText v-model="form.name" fluid />
        </div>
        <div>
          <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Descripción</label>
          <Textarea v-model="form.description as string" rows="2" fluid autoResize />
        </div>
        <div>
          <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">MIME types (separados por coma)</label>
          <Textarea v-model="form.mimeTypesText" rows="2" fluid autoResize />
        </div>
        <div>
          <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Extensiones (separadas por coma, sin punto)</label>
          <InputText v-model="form.extensionsText" fluid />
        </div>
        <div>
          <label style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.25rem;">Icono (clase PrimeIcons)</label>
          <div style="display:flex; align-items:center; gap:0.6rem;">
            <InputText v-model="form.icon_reference" fluid style="flex:1;" />
            <i :class="form.icon_reference" style="font-size:1.1rem;" />
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined size="small" @click="editDialog = false" />
        <Button label="Guardar" size="small" @click="submitEdit" />
      </template>
    </Dialog>
  </div>
</template>
