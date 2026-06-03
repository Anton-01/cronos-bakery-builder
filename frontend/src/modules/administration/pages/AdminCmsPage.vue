<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'

import { adminPanelService, type CmsSection, type CmsPage } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'

const { success, error } = useToast()

const sections = ref<CmsSection[]>([])
const pages = ref<CmsPage[]>([])
const loading = ref(true)
const activeSectionId = ref<string | null>(null)

// Form state
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

function cancelForm(): void {
  showForm.value = false
  editingPage.value = null
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
    cancelForm()
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
  if (!confirm(`¿Eliminar la pagina "${page.title}"?`)) return
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
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>CMS - Paginas</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Contenido <span>/</span> CMS
        </div>
      </div>
      <div>
        <button class="admin-btn admin-btn--primary" @click="openNewForm">Nueva Pagina</button>
      </div>
    </div>

    <!-- New / Edit form -->
    <div v-if="showForm" class="admin-content-card admin-form-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">{{ editingPage ? 'Editar Pagina' : 'Nueva Pagina' }}</h3>
      </div>
      <div class="admin-content-card__body">
        <div class="admin-form-grid">
          <div class="admin-form-group">
            <label for="form-title">Titulo</label>
            <input id="form-title" v-model="formTitle" type="text" placeholder="Titulo de la pagina" />
          </div>
          <div class="admin-form-group">
            <label for="form-slug">Slug</label>
            <input id="form-slug" v-model="formSlug" type="text" placeholder="url-slug" />
          </div>
          <div class="admin-form-group">
            <label for="form-section">Seccion</label>
            <select id="form-section" v-model="formSectionId">
              <option v-for="section in sections" :key="section.id" :value="section.id">
                {{ section.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="admin-form-group" style="flex-direction: row; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
          <input id="form-published" v-model="formIsPublished" type="checkbox" />
          <label for="form-published" style="font-size: 0.875rem; color: inherit; font-weight: normal;">Publicado</label>
        </div>
        <div style="display: flex; gap: 0.5rem;">
          <button class="admin-btn admin-btn--primary" :disabled="formSaving" @click="saveForm">
            {{ formSaving ? 'Guardando...' : 'Guardar' }}
          </button>
          <button class="admin-btn" @click="cancelForm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- Section filter chips -->
    <div v-if="!loading" class="admin-filters">
      <button
        class="admin-filter-chip"
        :class="{ 'admin-filter-chip--active': activeSectionId === null }"
        @click="activeSectionId = null"
      >
        Todas
      </button>
      <button
        v-for="section in sections"
        :key="section.id"
        class="admin-filter-chip"
        :class="{ 'admin-filter-chip--active': activeSectionId === section.id }"
        @click="activeSectionId = section.id"
      >
        {{ section.name }}
      </button>
    </div>

    <!-- Pages table -->
    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Listado de Paginas</h3>
      </div>
      <div class="admin-content-card__body">
        <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
          Cargando paginas...
        </p>

        <template v-else>
          <p v-if="filteredPages.length === 0" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
            No hay paginas registradas.
          </p>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Titulo</th>
                <th>Slug</th>
                <th>Seccion</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="page in filteredPages" :key="page.id">
                <td>{{ page.title }}</td>
                <td><code>{{ page.slug }}</code></td>
                <td>{{ sectionName(page.section_id) }}</td>
                <td>
                  <span
                    class="admin-badge"
                    :class="page.is_published ? 'admin-badge--success' : 'admin-badge--default'"
                  >
                    {{ page.is_published ? 'Publicado' : 'Borrador' }}
                  </span>
                </td>
                <td style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                  <button class="admin-btn admin-btn--sm" @click="openEditForm(page)">Editar</button>
                  <button class="admin-btn admin-btn--sm" @click="togglePublish(page)">
                    {{ page.is_published ? 'Despublicar' : 'Publicar' }}
                  </button>
                  <button class="admin-btn admin-btn--sm" style="color: var(--admin-error, #e53e3e);" @click="deletePage(page)">
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
.admin-filters { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.admin-filter-chip { padding: 0.35rem 0.85rem; border: 1px solid var(--admin-border); border-radius: 20px; background: white; cursor: pointer; font-size: 0.8rem; transition: all 0.2s; }
.admin-filter-chip--active { background: var(--admin-primary); color: white; border-color: var(--admin-primary); }
.admin-form-card { margin-bottom: 1.5rem; }
.admin-form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.admin-form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.admin-form-group label { font-size: 0.8rem; font-weight: 500; color: var(--admin-text-muted); }
.admin-form-group input, .admin-form-group select { padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.875rem; }
</style>
