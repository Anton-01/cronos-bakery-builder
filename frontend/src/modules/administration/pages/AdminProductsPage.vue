<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { adminPanelService, type AdminProduct } from '../services/adminPanelService'

const router = useRouter()
const { success, error } = useToast()
const {
  visible: confirmVisible,
  title: confirmTitle,
  message: confirmMessage,
  action: confirmAction,
  confirmText,
  cancelText,
  confirm,
  handleConfirm,
  handleCancel,
} = useConfirm()

const products = ref<AdminProduct[]>([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref<'all' | 'active' | 'inactive'>('all')
const currentPage = ref(1)
const perPage = ref(10)
const lightboxImage = ref<string | null>(null)
const lightboxProduct = ref<AdminProduct | null>(null)

function money(amount: number, currency = 'MXN'): string {
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency }).format(amount / 100)
}

function validImageUrl(url: string | null | undefined): string | null {
  if (!url || url.startsWith('blob:')) return null
  return url
}

const filtered = computed(() => {
  let result = products.value
  if (statusFilter.value === 'active') {
    result = result.filter((p) => p.is_active)
  } else if (statusFilter.value === 'inactive') {
    result = result.filter((p) => !p.is_active)
  }
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    result = result.filter(
        (p) => p.name.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q),
    )
  }
  return result
})

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)))

const paginated = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filtered.value.slice(start, start + perPage.value)
})

const pageRange = computed(() => {
  const pages: number[] = []
  const total = totalPages.value
  const current = currentPage.value
  const delta = 2
  for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) {
    pages.push(i)
  }
  return pages
})

function onSearchInput() {
  currentPage.value = 1
}

function onStatusFilterChange() {
  currentPage.value = 1
}

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
onMounted(() => {
  load()
  document.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Productos</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Catalogo <span>/</span> Productos
        </div>
      </div>
      <div>
        <button class="admin-btn admin-btn--primary" @click="router.push('/admin/productos/new')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
          Nuevo Producto
        </button>
      </div>
    </div>

    <!-- Products DataTable -->
    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Listado de Productos</h3>
      </div>
      <div class="admin-content-card__body">
        <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
          Cargando productos...
        </p>

        <template v-else>
          <div class="admin-datatable">
            <!-- Toolbar -->
            <div class="admin-datatable__toolbar">
              <div class="admin-datatable__search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>
                <input v-model="search" type="text" placeholder="Buscar producto..." @input="onSearchInput" />
              </div>

              <div class="admin-datatable__filters">

                <select v-model="statusFilter" class="admin-datatable__filter-select" @change="onStatusFilterChange">
                  <option value="all">Todos los estados</option>
                  <option value="active">Activos</option>
                  <option value="inactive">Inactivos</option>
                </select>

                <div class="admin-datatable__per-page">
                  <span>Mostrar</span>
                  <select v-model.number="perPage" @change="currentPage = 1">
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                  </select>
                  <span>registros</span>
                </div>

              </div>

            </div>

            <p v-if="filtered.length === 0" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
              No se encontraron productos.
            </p>

            <table v-else class="admin-table">
              <thead>
                <tr>
                  <th style="width: 40px;">#</th>
                  <th>Nombre</th>
                  <th style="width: 70px;">Imagen</th>
                  <th>Precio Base</th>
                  <th>Estado</th>
                  <th style="width: 80px;">Opciones</th>
                  <th style="width: 120px;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, idx) in paginated" :key="product.id">
                  <td style="color: var(--admin-text-muted);">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                  <td>
                    <span style="font-weight: 500; display: block;">{{ product.name }}</span>
                    <span class="product-list-slug">/{{ product.slug }}</span>
                  </td>
                  <td>
                    <div class="product-list-thumb" :class="{ 'product-list-thumb--clickable': !!validImageUrl(product.image) }" @click="openLightbox(product)">
                      <img v-if="validImageUrl(product.image)" :src="validImageUrl(product.image)!" :alt="product.name" />
                      <span v-else class="product-list-thumb__empty">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                          <rect x="3" y="3" width="18" height="18" rx="2" ry="2" /><circle cx="8.5" cy="8.5" r="1.5" /><polyline points="21 15 16 10 5 21" />
                        </svg>
                      </span>
                    </div>
                  </td>
                  <td>
                    <span class="product-price-currency">{{ product.base_price.currency }}</span>
                    {{ money(product.base_price.amount, product.base_price.currency) }}
                  </td>
                  <td>
                    <span class="product-status-badge" :class="product.is_active ? 'product-status-badge--stock' : 'product-status-badge--out'">
                      {{ product.is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td style="text-align: center;">{{ product.options_count ?? 0 }}</td>
                  <td>
                    <div style="display: flex; gap: 0.25rem;">
                      <button class="admin-action-btn admin-action-btn--edit" title="Editar" @click="router.push(`/admin/productos/${product.id}`)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                      </button>
                      <button class="admin-action-btn admin-action-btn--toggle" :title="product.is_active ? 'Desactivar' : 'Activar'" @click="toggleActive(product)">
                        <svg v-if="product.is_active" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                      </button>
                      <button class="admin-action-btn admin-action-btn--delete" title="Eliminar" @click="deleteProduct(product)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Footer -->
            <div v-if="filtered.length > 0" class="admin-datatable__footer">
              <div class="admin-datatable__info">
                Mostrando {{ (currentPage - 1) * perPage + 1 }} a {{ Math.min(currentPage * perPage, filtered.length) }} de {{ filtered.length }} productos
              </div>
              <div class="admin-datatable__pagination">
                <button class="admin-datatable__page-btn" :disabled="currentPage <= 1" @click="currentPage--">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6" /></svg>
                </button>
                <button v-for="p in pageRange" :key="p" class="admin-datatable__page-btn" :class="{ 'admin-datatable__page-btn--active': p === currentPage }" @click="currentPage = p">
                  {{ p }}
                </button>
                <button class="admin-datatable__page-btn" :disabled="currentPage >= totalPages" @click="currentPage++">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6" /></svg>
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Image lightbox -->
    <Teleport to="body">
      <Transition name="confirm-fade">
        <div v-if="lightboxImage" class="product-lightbox" @click.self="closeLightbox">
          <button class="product-lightbox__close-btn" @click="closeLightbox">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
          <div class="product-lightbox__content">
            <img :src="lightboxImage" :alt="lightboxProduct?.name" />
            <div v-if="lightboxProduct" class="product-lightbox__meta">
              <h4 class="product-lightbox__meta-name">{{ lightboxProduct.name }}</h4>
              <div class="product-lightbox__meta-details">
                <span>/{{ lightboxProduct.slug }}</span>
                <span class="product-lightbox__meta-sep">·</span>
                <span>{{ money(lightboxProduct.base_price.amount, lightboxProduct.base_price.currency) }}</span>
                <span class="product-lightbox__meta-sep">·</span>
                <span :class="lightboxProduct.is_active ? 'product-lightbox__meta-active' : 'product-lightbox__meta-inactive'">
                  {{ lightboxProduct.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Confirm dialog -->
    <ConfirmDialog
      :visible="confirmVisible"
      :title="confirmTitle"
      :message="confirmMessage"
      :action="confirmAction"
      :confirm-text="confirmText"
      :cancel-text="cancelText"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </div>
</template>

<style scoped>

.product-price-currency {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 700;
  background: var(--admin-primary-light, #e8f0fe);
  color: var(--admin-primary, #4361ee);
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  margin-right: 0.3rem;
  vertical-align: middle;
  letter-spacing: 0.02em;
}

.product-list-slug {
  font-size: 0.75rem;
  color: var(--admin-primary);
  opacity: 0.65;
  font-family: monospace;
}

.product-list-thumb {
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

.product-list-thumb--clickable {
  cursor: pointer;
  transition: box-shadow 0.15s ease;
}

.product-list-thumb--clickable:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.product-list-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-list-thumb__empty {
  color: var(--admin-text-muted);
  opacity: 0.5;
}

.product-list-thumb__empty svg {
  display: block;
}

/* Status badges — matching the screenshot style */
.product-status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}
.product-status-badge--stock {
  background: #e8f4fd;
  color: #3b8bdb;
}
.product-status-badge--out {
  background: #fde8e4;
  color: #e8684a;
}
/* Filter select */
.admin-datatable__filters {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.admin-datatable__filter-select {
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--admin-border);
  border-radius: var(--admin-radius-sm, 8px);
  font-family: var(--admin-font);
  font-size: 0.82rem;
  color: var(--admin-text);
  background: var(--admin-surface);
  cursor: pointer;
  transition: border-color 0.15s ease;
}
.admin-datatable__filter-select:focus {
  outline: none;
  border-color: var(--admin-primary);
  box-shadow: 0 0 0 3px var(--admin-primary-light);
}

/* Lightbox — dark background, centered image with metadata */
.product-lightbox {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.88);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 2rem;
}

.product-lightbox__close-btn {
  position: absolute;
  top: 1.25rem;
  right: 1.25rem;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s ease;
  z-index: 10;
}
.product-lightbox__close-btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

.product-lightbox__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  max-width: 720px;
  max-height: 85vh;
}

.product-lightbox__content img {
  display: block;
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  border-radius: 10px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.product-lightbox__meta {
  margin-top: 1.25rem;
  text-align: center;
  color: #fff;
}

.product-lightbox__meta-name {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 0.35rem;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}

.product-lightbox__meta-details {
  font-size: 0.82rem;
  opacity: 0.7;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  justify-content: center;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}
.product-lightbox__meta-sep {
  opacity: 0.4;
}
.product-lightbox__meta-active {
  color: #13deb9;
}

.product-lightbox__meta-inactive {
  color: #fa896b;
}
</style>
