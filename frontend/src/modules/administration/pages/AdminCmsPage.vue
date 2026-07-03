<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

import { adminPanelService, type CmsSection, type CmsPage } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'

const { success, error } = useToast()
const { confirm } = useConfirm()

const sections = ref<CmsSection[]>([])
const pages = ref<CmsPage[]>([])
const loading = ref(true)
const activeSectionId = ref<string | null>(null)

const showForm = ref(false)
const editingPage = ref<CmsPage | null>(null)
const formTitle = ref('')
const formSlug = ref('')
const formSectionId = ref('')
const formIsPublished = ref(false)
const formSaving = ref(false)

const filteredPages = computed(() => {
  if (!activeSectionId.value) return pages.value
  return pages.value.filter((p) => p.section_id === activeSectionId.value)
})

const sectionOptions = computed(() =>
  sections.value.map((s) => ({ label: s.name, value: s.id }))
)

function sectionName(sectionId: string): string {
  return sections.value.find((s) => s.id === sectionId)?.name ?? '—'
}

async function load(): Promise<void> {
  loading.value = true
  try {
    const [sectionsData, pagesData] = await Promise.all([
      adminPanelService.cmsSections(),
      adminPanelService.cmsPages(),
    ])
    sections.value = sectionsData
    pages.value = pagesData
  } finally {
    loading.value = false
  }
}

function openNewForm(): void {
  editingPage.value = null
  formTitle.value = ''
  formSlug.value = ''
  formSectionId.value = sections.value[0]?.id ?? ''
  formIsPublished.value = false
  showForm.value = true
}

function openEditForm(page: CmsPage): void {
  editingPage.value = page
  formTitle.value = page.title
  formSlug.value = page.slug
  formSectionId.value = page.section_id
  formIsPublished.value = page.is_published
  showForm.value = true
}

async function saveForm(): Promise<void> {
  formSaving.value = true
  try {
    const data: Partial<CmsPage> = {
      title: formTitle.value,
      slug: formSlug.value,
      section_id: formSectionId.value,
      is_published: formIsPublished.value,
    }
    if (editingPage.value) {
      const updated = await adminPanelService.cmsUpdatePage(editingPage.value.id, data)
      const idx = pages.value.findIndex((p) => p.id === editingPage.value!.id)
      if (idx !== -1) pages.value[idx] = updated
    } else {
      const created = await adminPanelService.cmsCreatePage(data)
      pages.value.push(created)
    }
    success(editingPage.value ? 'Pagina actualizada' : 'Pagina creada exitosamente')
    showForm.value = false
    editingPage.value = null
  } catch {
    error('Error al guardar la pagina')
  } finally {
    formSaving.value = false
  }
}

async function togglePublish(page: CmsPage): Promise<void> {
  try {
    const updated = await adminPanelService.cmsUpdatePage(page.id, { is_published: !page.is_published })
    const idx = pages.value.findIndex((p) => p.id === page.id)
    if (idx !== -1) pages.value[idx] = updated
    success(updated.is_published ? 'Pagina publicada' : 'Pagina despublicada')
  } catch {
    error('Error al actualizar la pagina')
  }
}

async function deletePage(page: CmsPage): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar pagina',
    message: `¿Eliminar la pagina "${page.title}"?`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await adminPanelService.cmsDeletePage(page.id)
    pages.value = pages.value.filter((p) => p.id !== page.id)
    success('Pagina eliminada')
  } catch {
    error('Error al eliminar la pagina')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>CMS - Paginas</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Contenido <span>/</span> CMS</div>
      </div>
      <Button label="Nueva Pagina" icon="pi pi-plus" @click="openNewForm" />
    </div>

    <!-- Section filter chips -->
    <div v-if="!loading" class="cms-filters">
      <Button
        :label="'Todas'"
        :severity="activeSectionId === null ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="activeSectionId = null"
      />
      <Button
        v-for="section in sections"
        :key="section.id"
        :label="section.name"
        :severity="activeSectionId === section.id ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="activeSectionId = section.id"
      />
    </div>

    <Card>
      <template #title>Listado de Paginas</template>
      <template #content>
        <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>

        <DataTable v-else :value="filteredPages" class="p-datatable-sm">
          <template #empty>
            <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">No hay paginas registradas.</div>
          </template>

          <Column header="Titulo" field="title">
            <template #body="{ data }">
              <span style="font-weight:500;">{{ data.title }}</span>
            </template>
          </Column>

          <Column header="Slug" field="slug">
            <template #body="{ data }">
              <code style="font-size:0.82rem; color:var(--admin-primary);">{{ data.slug }}</code>
            </template>
          </Column>

          <Column header="Seccion" style="width:160px;">
            <template #body="{ data }">{{ sectionName(data.section_id) }}</template>
          </Column>

          <Column header="Estado" style="width:120px;">
            <template #body="{ data }">
              <Tag :value="data.is_published ? 'Publicado' : 'Borrador'" :severity="data.is_published ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column header="Acciones" style="width:160px;">
            <template #body="{ data }">
              <div style="display:flex; gap:0.25rem;">
                <Button icon="pi pi-pencil" size="small" severity="info" text rounded title="Editar" @click="openEditForm(data)" />
                <Button
                  :icon="data.is_published ? 'pi pi-eye-slash' : 'pi pi-eye'"
                  size="small"
                  severity="warn"
                  text
                  rounded
                  :title="data.is_published ? 'Despublicar' : 'Publicar'"
                  @click="togglePublish(data)"
                />
                <Button icon="pi pi-trash" size="small" severity="danger" text rounded title="Eliminar" @click="deletePage(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Page form dialog -->
    <Dialog
      v-model:visible="showForm"
      modal
      :header="editingPage ? 'Editar Pagina' : 'Nueva Pagina'"
      :style="{ width: '480px' }"
      @hide="editingPage = null"
    >
      <form @submit.prevent="saveForm">
        <div class="cms-field">
          <label>Titulo</label>
          <InputText v-model="formTitle" fluid required placeholder="Titulo de la pagina" />
        </div>
        <div class="cms-field">
          <label>Slug</label>
          <InputText v-model="formSlug" fluid required placeholder="url-slug" style="font-family:monospace;" />
        </div>
        <div class="cms-field">
          <label>Seccion</label>
          <Select v-model="formSectionId" :options="sectionOptions" optionLabel="label" optionValue="value" fluid />
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.5rem;">
          <Checkbox v-model="formIsPublished" inputId="form-published" binary />
          <label for="form-published" style="font-size:0.875rem; cursor:pointer;">Publicado</label>
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined @click="showForm = false" />
        <Button :label="formSaving ? 'Guardando...' : 'Guardar'" :loading="formSaving" @click="saveForm" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.cms-filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.cms-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.cms-field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
</style>
