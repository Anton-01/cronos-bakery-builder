<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { adminPanelService, type AdminProduct } from '../services/adminPanelService'

const router = useRouter()
const { success, error } = useToast()
const { confirm } = useConfirm()

const products = ref<AdminProduct[]>([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('all')
const lightboxImage = ref<string | null>(null)
const lightboxProduct = ref<AdminProduct | null>(null)

const statusOptions = [
  { label: 'Todos los estados', value: 'all' },
  { label: 'Activos', value: 'active' },
  { label: 'Inactivos', value: 'inactive' },
]

function money(amount: number, currency = 'MXN'): string {
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency }).format(amount / 100)
}

function validImageUrl(url: string | null | undefined): string | null {
  if (!url || url.startsWith('blob:')) return null
  return url
}

const filtered = computed(() => {
  let result = products.value
  if (statusFilter.value === 'active') result = result.filter((p) => p.is_active)
  else if (statusFilter.value === 'inactive') result = result.filter((p) => !p.is_active)
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    result = result.filter((p) => p.name.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q))
  }
  return result
})

async function load(): Promise<void> {
  loading.value = true
  try {
    products.value = await adminPanelService.adminProducts()
  } finally {
    loading.value = false
  }
}

async function toggleActive(product: AdminProduct): Promise<void> {
  const activating = !product.is_active
  const ok = await confirm({
    title: activating ? 'Activar producto' : 'Desactivar producto',
    message: activating
      ? `El producto "${product.name}" será visible en la tienda.`
      : `El producto "${product.name}" dejará de ser visible en la tienda.`,
    action: activating ? 'activate' : 'deactivate',
    confirmText: activating ? 'Activar' : 'Desactivar',
  })
  if (!ok) return
  try {
    const updated = await adminPanelService.updateProduct(product.id, { is_active: !product.is_active })
    const idx = products.value.findIndex((p) => p.id === product.id)
    if (idx !== -1) products.value[idx] = updated
    success(`Producto ${updated.is_active ? 'activado' : 'desactivado'}`)
  } catch {
    error('Error al actualizar el producto')
  }
}

async function deleteProduct(product: AdminProduct): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar producto',
    message: `Esta acción eliminará permanentemente "${product.name}". No se puede deshacer.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await adminPanelService.deleteProduct(product.id)
    products.value = products.value.filter((p) => p.id !== product.id)
    success('Producto eliminado')
  } catch {
    error('Error al eliminar el producto')
  }
}

function openLightbox(product: AdminProduct) {
  const url = validImageUrl(product.image)
  if (!url) return
  lightboxImage.value = url
  lightboxProduct.value = product
}
function closeLightbox() {
  lightboxImage.value = null
  lightboxProduct.value = null
}
function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && lightboxImage.value) closeLightbox()
}
onMounted(() => { load(); document.addEventListener('keydown', onKeydown) })
onBeforeUnmount(() => { document.removeEventListener('keydown', onKeydown) })
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Productos</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Catalogo <span>/</span> Productos</div>
      </div>
      <Button label="Nuevo Producto" icon="pi pi-plus" @click="router.push('/admin/productos/new')" />
    </div>

    <Card>
      <template #title>Listado de Productos</template>
      <template #content>
        <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>

        <template v-else>
          <!-- Toolbar -->
          <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem; flex-wrap:wrap;">
            <div class="p-inputgroup" style="max-width:280px;">
              <span class="p-inputgroup-addon"><i class="pi pi-search"></i></span>
              <InputText v-model="search" placeholder="Buscar producto..." />
            </div>
            <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Estado" />
          </div>

          <DataTable
            :value="filtered"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 25, 50]"
            paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
            currentPageReportTemplate="{first} - {last} de {totalRecords}"
            class="p-datatable-sm"
          >
            <template #empty>
              <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">No se encontraron productos.</div>
            </template>

            <Column header="Imagen" style="width:80px;">
              <template #body="{ data }">
                <div
                  class="product-thumb"
                  :class="{ 'product-thumb--clickable': !!validImageUrl(data.image) }"
                  @click="openLightbox(data)"
                >
                  <img v-if="validImageUrl(data.image)" :src="validImageUrl(data.image)!" :alt="data.name" />
                  <i v-else class="pi pi-image" style="color:var(--admin-text-muted); opacity:0.4; font-size:1.2rem;"></i>
                </div>
              </template>
            </Column>

            <Column header="Nombre" sortable field="name">
              <template #body="{ data }">
                <span style="font-weight:500; display:block;">{{ data.name }}</span>
                <small style="color:var(--admin-primary); font-family:monospace; opacity:0.65;">/{{ data.slug }}</small>
              </template>
            </Column>

            <Column header="Precio Base" sortable field="base_price.amount">
              <template #body="{ data }">
                {{ money(data.base_price.amount, data.base_price.currency) }}
              </template>
            </Column>

            <Column header="Estado" style="width:120px;">
              <template #body="{ data }">
                <Tag :value="data.is_active ? 'Activo' : 'Inactivo'" :severity="data.is_active ? 'success' : 'danger'" />
              </template>
            </Column>

            <Column header="Opciones" style="width:90px; text-align:center;">
              <template #body="{ data }">{{ data.options_count ?? 0 }}</template>
            </Column>

            <Column header="Acciones" style="width:130px;">
              <template #body="{ data }">
                <div style="display:flex; gap:0.25rem;">
                  <Button icon="pi pi-pencil" size="small" severity="info" text rounded title="Editar" @click="router.push(`/admin/productos/${data.id}`)" />
                  <Button
                    :icon="data.is_active ? 'pi pi-times-circle' : 'pi pi-check-circle'"
                    size="small"
                    severity="warn"
                    text
                    rounded
                    :title="data.is_active ? 'Desactivar' : 'Activar'"
                    @click="toggleActive(data)"
                  />
                  <Button icon="pi pi-trash" size="small" severity="danger" text rounded title="Eliminar" @click="deleteProduct(data)" />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </template>
    </Card>

    <!-- Lightbox dialog -->
    <Dialog :visible="!!lightboxImage" modal :showHeader="false" @update:visible="(v: boolean) => { if (!v) closeLightbox() }" :style="{ background: 'transparent', boxShadow: 'none' }" contentStyle="background:rgba(0,0,0,0.88); padding:2rem; border-radius:12px; position:relative;" @hide="closeLightbox">
      <Button icon="pi pi-times" rounded text :style="{ position:'absolute', top:'1rem', right:'1rem', color:'#fff', background:'rgba(255,255,255,0.15)' }" @click="closeLightbox" />
      <div style="display:flex; flex-direction:column; align-items:center;">
        <img :src="lightboxImage!" :alt="lightboxProduct?.name" style="max-width:100%; max-height:70vh; object-fit:contain; border-radius:10px;" />
        <div v-if="lightboxProduct" style="margin-top:1.25rem; text-align:center; color:#fff;">
          <h4 style="font-size:1.1rem; font-weight:600; margin:0 0 0.35rem;">{{ lightboxProduct.name }}</h4>
          <div style="font-size:0.82rem; opacity:0.7;">/{{ lightboxProduct.slug }} · {{ money(lightboxProduct.base_price.amount, lightboxProduct.base_price.currency) }}</div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<style scoped>
.product-thumb {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  overflow: hidden;
  background: var(--admin-bg);
  border: 1px solid var(--admin-border);
  display: flex;
  align-items: center;
  justify-content: center;
}
.product-thumb--clickable { cursor: pointer; }
.product-thumb--clickable:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
.product-thumb img { width: 100%; height: 100%; object-fit: cover; }
</style>
