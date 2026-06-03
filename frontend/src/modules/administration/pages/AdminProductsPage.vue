<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import { adminPanelService, type AdminProduct } from '../services/adminPanelService'

const products = ref<AdminProduct[]>([])
const loading = ref(true)
const showForm = ref(false)

const form = reactive({
  name: '',
  slug: '',
  base_price_amount: 0,
  base_price_currency: 'CRC',
  is_active: true,
})

function money(amount: number, currency = 'CRC'): string {
  return new Intl.NumberFormat('es-CR', { style: 'currency', currency }).format(amount / 100)
}

async function load(): Promise<void> {
  loading.value = true
  try {
    products.value = await adminPanelService.adminProducts()
  } finally {
    loading.value = false
  }
}

async function submitCreate(): Promise<void> {
  const created = await adminPanelService.createProduct({
    name: form.name,
    slug: form.slug,
    base_price: { amount: form.base_price_amount, currency: form.base_price_currency },
    is_active: form.is_active,
  })
  products.value.push(created)
  showForm.value = false
  form.name = ''
  form.slug = ''
  form.base_price_amount = 0
  form.base_price_currency = 'CRC'
  form.is_active = true
}

async function toggleActive(product: AdminProduct): Promise<void> {
  const updated = await adminPanelService.updateProduct(product.id, { is_active: !product.is_active })
  const idx = products.value.findIndex((p) => p.id === product.id)
  if (idx !== -1) {
    products.value[idx] = updated
  }
}

async function deleteProduct(product: AdminProduct): Promise<void> {
  if (!confirm(`¿Eliminar el producto "${product.name}"?`)) return
  await adminPanelService.deleteProduct(product.id)
  products.value = products.value.filter((p) => p.id !== product.id)
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
        <button class="admin-btn admin-btn--primary" @click="showForm = !showForm">
          Nuevo Producto
        </button>
      </div>
    </div>

    <!-- New product form -->
    <div v-if="showForm" class="admin-content-card admin-form-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Nuevo Producto</h3>
      </div>
      <div class="admin-content-card__body">
        <form @submit.prevent="submitCreate">
          <div class="admin-form-grid">
            <div class="admin-form-group">
              <label for="product-name">Nombre</label>
              <input id="product-name" v-model="form.name" type="text" required placeholder="Nombre del producto" />
            </div>
            <div class="admin-form-group">
              <label for="product-slug">Slug</label>
              <input id="product-slug" v-model="form.slug" type="text" required placeholder="slug-del-producto" />
            </div>
            <div class="admin-form-group">
              <label for="product-price">Precio Base (céntimos)</label>
              <input id="product-price" v-model.number="form.base_price_amount" type="number" min="0" required />
            </div>
            <div class="admin-form-group">
              <label for="product-currency">Moneda</label>
              <select id="product-currency" v-model="form.base_price_currency">
                <option value="CRC">CRC</option>
                <option value="USD">USD</option>
              </select>
            </div>
            <div class="admin-form-group" style="justify-content: flex-end;">
              <label>
                <input v-model="form.is_active" type="checkbox" />
                Activo
              </label>
            </div>
          </div>
          <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="admin-btn admin-btn--primary">Guardar</button>
            <button type="button" class="admin-btn" @click="showForm = false">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Products table -->
    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Listado de Productos</h3>
      </div>
      <div class="admin-content-card__body">
        <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
          Cargando productos...
        </p>

        <template v-else>
          <p v-if="products.length === 0" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
            No hay productos registrados.
          </p>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Slug</th>
                <th>Precio Base</th>
                <th>Estado</th>
                <th>Opciones</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in products" :key="product.id">
                <td>{{ product.name }}</td>
                <td>{{ product.slug }}</td>
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
                <td style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                  <button class="admin-btn admin-btn--sm" @click="$router.push(`/admin/productos/${product.id}`)">
                    Editar
                  </button>
                  <button class="admin-btn admin-btn--sm" @click="toggleActive(product)">
                    {{ product.is_active ? 'Desactivar' : 'Activar' }}
                  </button>
                  <button class="admin-btn admin-btn--sm" style="color: var(--admin-error, #e53e3e);" @click="deleteProduct(product)">
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
.admin-form-card { margin-bottom: 1.5rem; }
.admin-form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.admin-form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.admin-form-group label { font-size: 0.8rem; font-weight: 500; color: var(--admin-text-muted); }
.admin-form-group input, .admin-form-group select { padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.875rem; }
</style>
