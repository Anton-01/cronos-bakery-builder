<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import ProgressSpinner from 'primevue/progressspinner'
import Select from 'primevue/select'
import Tag from 'primevue/tag'

import { pageBuilderService } from '@/modules/cms/services/pageBuilderService'
import type { Brand, CmsPage, PageStatus, PageType } from '@/modules/cms/types'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { success, error } = useToast()
const { confirm } = useConfirm()

const brands = ref<Brand[]>([])
const pages = ref<CmsPage[]>([])
const loading = ref(true)
const activeBrandId = ref<number | null>(null)

const showForm = ref(false)
const formSaving = ref(false)
const formTitle = ref('')
const formSlug = ref('')
const formBrandId = ref<number | null>(null)
const formType = ref<PageType>('landing')

const pageTypeOptions: Array<{ label: string; value: PageType }> = [
  { label: 'Inicio', value: 'home' },
  { label: 'Nosotros', value: 'about' },
  { label: 'Contacto', value: 'contact' },
  { label: 'FAQ', value: 'faq' },
  { label: 'Políticas', value: 'policies' },
  { label: 'Blog', value: 'blog' },
  { label: 'Landing Page', value: 'landing' },
]

const brandOptions = computed(() => brands.value.map((b) => ({ label: b.name, value: b.id })))

const filteredPages = computed(() => {
  if (activeBrandId.value === null) return pages.value
  return pages.value.filter((p) => p.brand_id === activeBrandId.value)
})

function brandName(brandId: number): string {
  return brands.value.find((b) => b.id === brandId)?.name ?? '—'
}

function statusSeverity(status: PageStatus): string {
  return status === 'published' ? 'success' : status === 'archived' ? 'warn' : 'secondary'
}

function statusLabel(status: PageStatus): string {
  return status === 'published' ? 'Publicado' : status === 'archived' ? 'Archivado' : 'Borrador'
}

function typeLabel(type: PageType): string {
  return pageTypeOptions.find((o) => o.value === type)?.label ?? type
}

async function load(): Promise<void> {
  loading.value = true
  try {
    const [brandsData, pagesData] = await Promise.all([
      pageBuilderService.brands(),
      pageBuilderService.pages(),
    ])
    brands.value = brandsData
    pages.value = pagesData
  } finally {
    loading.value = false
  }
}

function openBuilder(page: CmsPage): void {
  router.push({ name: 'admin.cms.builder', params: { id: page.id } })
}

function openNewForm(): void {
  formTitle.value = ''
  formSlug.value = ''
  formType.value = 'landing'
  formBrandId.value = activeBrandId.value ?? brands.value[0]?.id ?? null
  showForm.value = true
}

async function createPage(): Promise<void> {
  if (!formBrandId.value || !formTitle.value.trim()) {
    error('El título y la marca son obligatorios')
    return
  }
  formSaving.value = true
  try {
    const created = await pageBuilderService.createPage({
      brand_id: formBrandId.value,
      title: formTitle.value,
      slug: formSlug.value.trim() || null,
      type: formType.value,
      status: 'draft',
    })
    success('Página creada. Ahora construye su contenido.')
    showForm.value = false
    router.push({ name: 'admin.cms.builder', params: { id: created.id } })
  } catch {
    error('Error al crear la página. Verifica que el slug no esté repetido en la marca.')
  } finally {
    formSaving.value = false
  }
}

async function togglePublish(page: CmsPage): Promise<void> {
  try {
    const updated =
      page.status === 'published'
        ? await pageBuilderService.unpublish(page.id)
        : await pageBuilderService.publish(page.id)
    const idx = pages.value.findIndex((p) => p.id === page.id)
    if (idx !== -1) pages.value[idx] = updated
    success(updated.status === 'published' ? 'Página publicada' : 'Página movida a borrador')
  } catch {
    error('Error al actualizar la página')
  }
}

async function deletePage(page: CmsPage): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar página',
    message: `¿Eliminar la página "${page.title}" y todos sus bloques?`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await pageBuilderService.deletePage(page.id)
    pages.value = pages.value.filter((p) => p.id !== page.id)
    success('Página eliminada')
  } catch {
    error('Error al eliminar la página')
  }
}

onMounted(load)
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>CMS - Páginas</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Contenido <span>/</span> CMS</div>
      </div>
      <Button label="Nueva página" @click="openNewForm" />
    </div>

    <!-- Brand filter -->
    <div v-if="!loading && brands.length > 1" class="cms-filters">
      <Button
        label="Todas las marcas"
        :severity="activeBrandId === null ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="activeBrandId = null"
      />
      <Button
        v-for="brand in brands"
        :key="brand.id"
        :label="brand.name"
        :severity="activeBrandId === brand.id ? 'primary' : 'secondary'"
        size="small"
        rounded
        @click="activeBrandId = brand.id"
      />
    </div>

    <Card>
      <template #title>Listado de páginas</template>
      <template #content>
        <div v-if="loading" style="display: flex; justify-content: center; padding: 3rem">
          <ProgressSpinner />
        </div>

        <DataTable v-else :value="filteredPages" class="p-datatable-sm">
          <template #empty>
            <div style="text-align: center; padding: 2rem; color: var(--admin-text-muted)">
              No hay páginas registradas.
            </div>
          </template>

          <Column header="Título" field="title">
            <template #body="{ data }">
              <a class="cms-page-title" @click.prevent="openBuilder(data)">{{ data.title }}</a>
            </template>
          </Column>

          <Column header="Slug" field="slug">
            <template #body="{ data }">
              <code style="font-size: 0.82rem; color: var(--admin-primary)">/{{ data.slug }}</code>
            </template>
          </Column>

          <Column header="Marca" style="width: 160px">
            <template #body="{ data }">{{ data.brand?.name ?? brandName(data.brand_id) }}</template>
          </Column>

          <Column header="Tipo" style="width: 140px">
            <template #body="{ data }">{{ typeLabel(data.type) }}</template>
          </Column>

          <Column header="Estado" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="Acciones" style="width: 150px">
            <template #body="{ data }">
              <div class="cms-actions">
                <Button
                  v-tooltip.top="'Abrir Constructor'"
                  icon="pi pi-palette"
                  size="small"
                  severity="secondary"
                  text
                  rounded
                  aria-label="Abrir Constructor"
                  @click="openBuilder(data)"
                />
                <Button
                  v-tooltip.top="data.status === 'published' ? 'Despublicar página' : 'Publicar página'"
                  :icon="data.status === 'published' ? 'pi pi-eye-slash' : 'pi pi-eye'"
                  size="small"
                  severity="warn"
                  text
                  rounded
                  :aria-label="data.status === 'published' ? 'Despublicar página' : 'Publicar página'"
                  @click="togglePublish(data)"
                />
                <Button
                  v-tooltip.top="'Eliminar página'"
                  icon="pi pi-trash"
                  size="small"
                  severity="danger"
                  text
                  rounded
                  aria-label="Eliminar página"
                  @click="deletePage(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Create page dialog: only the identity; content is built in the builder. -->
    <Dialog v-model:visible="showForm" modal header="Nueva página" :style="{ width: '480px' }">
      <form @submit.prevent="createPage">
        <div class="cms-field">
          <label>Marca</label>
          <Select v-model="formBrandId" :options="brandOptions" option-label="label" option-value="value" fluid />
        </div>
        <div class="cms-field">
          <label>Título</label>
          <InputText v-model="formTitle" fluid required placeholder="Título de la página" />
        </div>
        <div class="cms-field">
          <label>Slug (opcional)</label>
          <InputText v-model="formSlug" fluid placeholder="se-genera-del-titulo" style="font-family: monospace" />
        </div>
        <div class="cms-field">
          <label>Tipo</label>
          <Select v-model="formType" :options="pageTypeOptions" option-label="label" option-value="value" fluid />
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined @click="showForm = false" />
        <Button
          :label="formSaving ? 'Creando...' : 'Crear y construir'"
          :loading="formSaving"
          @click="createPage"
        />
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
.cms-actions {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}
.cms-page-title {
  font-weight: 500;
  color: var(--admin-text);
  cursor: pointer;
}
.cms-page-title:hover {
  color: var(--admin-primary);
}
</style>
