<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
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
const currentPage = ref(1)
const perPage = ref(10)

function money(amount: number, currency = 'MXN'): string {
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency }).format(amount / 100)
}

const filtered = computed(() => {
  if (!search.value.trim()) return products.value
  const q = search.value.toLowerCase()
  return products.value.filter(
    (p) => p.name.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q),
  )
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
    title: activating ? '¿Activar producto?' : '¿Desactivar producto?',
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
    title: '¿Eliminar producto?',
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

onMounted(load)
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></svg>
                <input v-model="search" type="text" placeholder="Buscar producto..." @input="onSearchInput" />
              </div>
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

            <p v-if="filtered.length === 0" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
              No se encontraron productos.
            </p>

            <table v-else class="admin-table">
              <thead>
                <tr>
                  <th style="width: 40px;">#</th>
                  <th>Nombre</th>
                  <th>Slug</th>
                  <th>Precio Base</th>
                  <th>Estado</th>
                  <th>Opciones</th>
                  <th style="width: 120px;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, idx) in paginated" :key="product.id">
                  <td style="color: var(--admin-text-muted);">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
                  <td style="font-weight: 500;">{{ product.name }}</td>
                  <td><code>{{ product.slug }}</code></td>
                  <td>{{ money(product.base_price.amount, product.base_price.currency) }}</td>
                  <td>
                    <span
                      class="admin-badge"
                      :class="product.is_active ? 'admin-badge--success' : 'admin-badge--default'"
                    >
                      {{ product.is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td>{{ product.options_count ?? 0 }}</td>
                  <td>
                    <div style="display: flex; gap: 0.25rem;">
                      <button
                        class="admin-action-btn admin-action-btn--edit"
                        title="Editar"
                        @click="router.push(`/admin/productos/${product.id}`)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                      </button>
                      <button
                        class="admin-action-btn admin-action-btn--toggle"
                        :title="product.is_active ? 'Desactivar' : 'Activar'"
                        @click="toggleActive(product)"
                      >
                        <svg v-if="product.is_active" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                      </button>
                      <button
                        class="admin-action-btn admin-action-btn--delete"
                        title="Eliminar"
                        @click="deleteProduct(product)"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                <button
                  class="admin-datatable__page-btn"
                  :disabled="currentPage <= 1"
                  @click="currentPage--"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6" /></svg>
                </button>
                <button
                  v-for="p in pageRange"
                  :key="p"
                  class="admin-datatable__page-btn"
                  :class="{ 'admin-datatable__page-btn--active': p === currentPage }"
                  @click="currentPage = p"
                >
                  {{ p }}
                </button>
                <button
                  class="admin-datatable__page-btn"
                  :disabled="currentPage >= totalPages"
                  @click="currentPage++"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6" /></svg>
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

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
