<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useToast } from '@/composables/useToast'
import { adminPanelService, type ProductImage } from '../services/adminPanelService'

const route = useRoute()
const router = useRouter()
const { success, error } = useToast()

const productId = computed(() => route.params.id as string | undefined)
const isEdit = computed(() => !!productId.value)
const loading = ref(false)
const saving = ref(false)

const form = reactive({
  name: '',
  slug: '',
  description: '',
  is_active: true,
  base_price_amount: 0,
  base_price_currency: 'MXN',
  discount_type: 'none' as 'none' | 'percentage' | 'fixed',
  discount_value: 0,
  tax_class: 'standard',
  vat: 16,
  categories: [] as string[],
  tags: '',
})

const thumbnail = ref<string | null>(null)
const thumbnailFile = ref<File | null>(null)
const gallery = ref<(ProductImage & { _file?: File; _preview?: string })[]>([])
const galleryFiles = ref<File[]>([])
const dragOverThumb = ref(false)
const dragOverGallery = ref(false)
const thumbInput = ref<HTMLInputElement | null>(null)
const galleryInput = ref<HTMLInputElement | null>(null)

function generateSlug(name: string): string {
  return name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, '')
}

function onNameInput() {
  if (!isEdit.value) {
    form.slug = generateSlug(form.name)
  }
}

// Thumbnail
function onThumbDrop(e: DragEvent) {
  dragOverThumb.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file && file.type.startsWith('image/')) {
    thumbnailFile.value = file
    thumbnail.value = URL.createObjectURL(file)
  }
}

function onThumbSelect(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) {
    thumbnailFile.value = file
    thumbnail.value = URL.createObjectURL(file)
  }
}

function removeThumb() {
  thumbnail.value = null
  thumbnailFile.value = null
}

// Gallery
function onGalleryDrop(e: DragEvent) {
  dragOverGallery.value = false
  const files = e.dataTransfer?.files
  if (files) addGalleryFiles(files)
}

function onGallerySelect(e: Event) {
  const files = (e.target as HTMLInputElement).files
  if (files) addGalleryFiles(files)
}

function addGalleryFiles(files: FileList) {
  for (const file of Array.from(files)) {
    if (!file.type.startsWith('image/')) continue
    gallery.value.push({
      id: `new-${Date.now()}-${Math.random()}`,
      path: '',
      name: file.name.replace(/\.[^.]+$/, ''),
      alt_text: '',
      position: gallery.value.length,
      _file: file,
      _preview: URL.createObjectURL(file),
    })
    galleryFiles.value.push(file)
  }
}

function removeGalleryImage(idx: number) {
  gallery.value.splice(idx, 1)
}

async function loadProduct() {
  if (!productId.value) return
  loading.value = true
  try {
    const p = await adminPanelService.showProduct(productId.value)
    form.name = p.name
    form.slug = p.slug
    form.description = p.description ?? ''
    form.is_active = p.is_active
    form.base_price_amount = p.base_price.amount
    form.base_price_currency = p.base_price.currency
    thumbnail.value = p.image ?? null
    gallery.value = (p.gallery ?? []).map((img) => ({ ...img }))
  } catch {
    error('Error al cargar el producto')
  } finally {
    loading.value = false
  }
}

async function submitForm() {
  saving.value = true
  try {
    const payload = {
      name: form.name,
      slug: form.slug,
      description: form.description || null,
      is_active: form.is_active,
      base_price: { amount: form.base_price_amount, currency: form.base_price_currency },
    }

    if (isEdit.value) {
      await adminPanelService.updateProduct(productId.value!, payload)
      success('Producto actualizado exitosamente')
    } else {
      const created = await adminPanelService.createProduct(payload)
      success('Producto creado exitosamente')
      router.replace(`/admin/productos/${created.id}`)
    }
  } catch {
    error('Error al guardar el producto')
  } finally {
    saving.value = false
  }
}

onMounted(loadProduct)
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>{{ isEdit ? 'Editar Producto' : 'Nuevo Producto' }}</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Catalogo <span>/</span>
          <a href="/admin/products" @click.prevent="router.push('/admin/products')">Productos</a>
          <span>/</span> {{ isEdit ? 'Editar' : 'Nuevo' }}
        </div>
      </div>
      <div style="display: flex; gap: 0.5rem;">
        <button class="admin-btn admin-btn--outline" @click="router.push('/admin/products')">
          Cancelar
        </button>
        <button class="admin-btn admin-btn--primary" :disabled="saving" @click="submitForm">
          {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear Producto') }}
        </button>
      </div>
    </div>

    <p v-if="loading" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
      Cargando producto...
    </p>

    <form v-else class="admin-product-form" @submit.prevent="submitForm">
      <!-- LEFT COLUMN — Main content -->
      <div>
        <!-- General -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">General</h3>
          </div>
          <div class="admin-content-card__body">
            <div class="admin-product-form__field">
              <label class="admin-product-form__label" for="pf-name">Nombre del producto</label>
              <input
                id="pf-name"
                v-model="form.name"
                type="text"
                class="admin-product-form__input"
                required
                placeholder="Ej: Pastel de Chocolate"
                @input="onNameInput"
              />
            </div>
            <div class="admin-product-form__field">
              <label class="admin-product-form__label" for="pf-slug">Slug</label>
              <input
                id="pf-slug"
                v-model="form.slug"
                type="text"
                class="admin-product-form__input"
                required
                placeholder="pastel-de-chocolate"
              />
            </div>
            <div class="admin-product-form__field">
              <label class="admin-product-form__label" for="pf-desc">Descripción</label>
              <textarea
                id="pf-desc"
                v-model="form.description"
                class="admin-product-form__textarea"
                placeholder="Describe tu producto..."
              />
            </div>
          </div>
        </div>

        <!-- Thumbnail -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Imagen Principal</h3>
          </div>
          <div class="admin-content-card__body">
            <div
              v-if="!thumbnail"
              class="admin-drop-zone"
              :class="{ 'admin-drop-zone--active': dragOverThumb }"
              @dragover.prevent="dragOverThumb = true"
              @dragleave="dragOverThumb = false"
              @drop.prevent="onThumbDrop"
              @click="thumbInput?.click()"
            >
              <div class="admin-drop-zone__icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2" /><circle cx="8.5" cy="8.5" r="1.5" /><polyline points="21 15 16 10 5 21" />
                </svg>
              </div>
              <p class="admin-drop-zone__text">Arrastra una imagen aquí o haz clic para seleccionar</p>
              <p class="admin-drop-zone__hint">JPG, PNG o WebP — máx. 5 MB</p>
            </div>
            <div v-else class="admin-drop-zone__preview">
              <img :src="thumbnail" alt="Thumbnail" />
              <button type="button" class="admin-drop-zone__remove" @click="removeThumb">&times;</button>
            </div>
            <input ref="thumbInput" type="file" accept="image/*" style="display: none;" @change="onThumbSelect" />
          </div>
        </div>

        <!-- Gallery -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Galería de Imágenes</h3>
          </div>
          <div class="admin-content-card__body">
            <div
              class="admin-drop-zone"
              :class="{ 'admin-drop-zone--active': dragOverGallery }"
              style="margin-bottom: 1rem;"
              @dragover.prevent="dragOverGallery = true"
              @dragleave="dragOverGallery = false"
              @drop.prevent="onGalleryDrop"
              @click="galleryInput?.click()"
            >
              <div class="admin-drop-zone__icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />
                </svg>
              </div>
              <p class="admin-drop-zone__text">Agrega imágenes a la galería</p>
              <p class="admin-drop-zone__hint">Puedes seleccionar varias a la vez</p>
            </div>
            <input ref="galleryInput" type="file" accept="image/*" multiple style="display: none;" @change="onGallerySelect" />

            <div v-if="gallery.length" class="admin-gallery-grid">
              <div v-for="(img, idx) in gallery" :key="img.id" class="admin-gallery-item">
                <img :src="img._preview || img.path" alt="" class="admin-gallery-item__img" />
                <div class="admin-gallery-item__body">
                  <input
                    v-model="img.name"
                    class="admin-gallery-item__input"
                    placeholder="Nombre"
                  />
                  <input
                    v-model="img.alt_text"
                    class="admin-gallery-item__input"
                    placeholder="Texto alternativo"
                  />
                </div>
                <div class="admin-gallery-item__actions">
                  <button type="button" class="admin-action-btn admin-action-btn--delete" @click="removeGalleryImage(idx)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pricing -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Precios</h3>
          </div>
          <div class="admin-content-card__body">
            <div class="admin-pricing-grid">
              <div class="admin-product-form__field">
                <label class="admin-product-form__label" for="pf-price">Precio Base (centavos)</label>
                <input
                  id="pf-price"
                  v-model.number="form.base_price_amount"
                  type="number"
                  min="0"
                  class="admin-product-form__input"
                  required
                />
              </div>
              <div class="admin-product-form__field">
                <label class="admin-product-form__label" for="pf-currency">Moneda</label>
                <select id="pf-currency" v-model="form.base_price_currency" class="admin-product-form__select">
                  <option value="MXN">MXN</option>
                  <option value="USD">USD</option>
                  <option value="CRC">CRC</option>
                </select>
              </div>
              <div class="admin-product-form__field">
                <label class="admin-product-form__label" for="pf-vat">IVA (%)</label>
                <input
                  id="pf-vat"
                  v-model.number="form.vat"
                  type="number"
                  min="0"
                  max="100"
                  class="admin-product-form__input"
                />
              </div>
              <div class="admin-product-form__field">
                <label class="admin-product-form__label" for="pf-tax-class">Clase de impuesto</label>
                <select id="pf-tax-class" v-model="form.tax_class" class="admin-product-form__select">
                  <option value="standard">Estándar</option>
                  <option value="reduced">Reducida</option>
                  <option value="zero">Exento</option>
                </select>
              </div>
            </div>

            <div class="admin-product-form__field" style="margin-top: 1rem;">
              <label class="admin-product-form__label">Tipo de descuento</label>
              <div class="admin-radio-group">
                <label><input v-model="form.discount_type" type="radio" value="none" /> Sin descuento</label>
                <label><input v-model="form.discount_type" type="radio" value="percentage" /> Porcentaje</label>
                <label><input v-model="form.discount_type" type="radio" value="fixed" /> Monto fijo</label>
              </div>
            </div>

            <div v-if="form.discount_type !== 'none'" class="admin-product-form__field" style="max-width: 200px;">
              <label class="admin-product-form__label" for="pf-discount">
                {{ form.discount_type === 'percentage' ? 'Descuento (%)' : 'Descuento (centavos)' }}
              </label>
              <input
                id="pf-discount"
                v-model.number="form.discount_value"
                type="number"
                min="0"
                class="admin-product-form__input"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN — Sidebar -->
      <div>
        <!-- Status -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Estado</h3>
          </div>
          <div class="admin-content-card__body">
            <div class="admin-product-form__field" style="margin-bottom: 0;">
              <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem;">
                <input v-model="form.is_active" type="checkbox" />
                <span>{{ form.is_active ? 'Activo — Visible en la tienda' : 'Inactivo — Oculto' }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Categories / Tags -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Detalles del Producto</h3>
          </div>
          <div class="admin-content-card__body">
            <div class="admin-product-form__field">
              <label class="admin-product-form__label" for="pf-tags">Etiquetas</label>
              <input
                id="pf-tags"
                v-model="form.tags"
                type="text"
                class="admin-product-form__input"
                placeholder="pastel, chocolate, cumpleaños"
              />
              <p style="font-size: 0.7rem; color: var(--admin-text-muted); margin-top: 0.25rem;">
                Separa las etiquetas con comas
              </p>
            </div>
          </div>
        </div>

        <!-- Template info -->
        <div class="admin-content-card">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Plantilla</h3>
          </div>
          <div class="admin-content-card__body">
            <p style="font-size: 0.85rem; color: var(--admin-text-secondary);">
              Plantilla por defecto del producto. Las opciones de personalización se gestionan desde la sección de <strong>Opciones</strong>.
            </p>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>
