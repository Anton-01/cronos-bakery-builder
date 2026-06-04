<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Underline from '@tiptap/extension-underline'

import { useToast } from '@/composables/useToast'
import { adminPanelService, type ProductImage, type PbOption, type PbOptionValue } from '../services/adminPanelService'

const route = useRoute()
const router = useRouter()
const { success, error } = useToast()

const productId = computed(() => route.params.id as string | undefined)
const isEdit = computed(() => !!productId.value)
const loading = ref(false)
const saving = ref(false)

type ProductStatus = 'draft' | 'private' | 'public'

const form = reactive({
  name: '',
  slug: '',
  description: '',
  status: 'draft' as ProductStatus,
  base_price_amount: 0,
  base_price_currency: 'MXN',
  discount_type: 'none' as 'none' | 'percentage' | 'fixed',
  discount_value: 0,
  tax_class: 'standard',
  vat: 16,
  tags: '',
})

const thumbnail = ref<string | null>(null)
const thumbnailFile = ref<File | null>(null)
const gallery = ref<(ProductImage & { _file?: File; _preview?: string })[]>([])
const dragOverThumb = ref(false)
const dragOverGallery = ref(false)
const thumbInput = ref<HTMLInputElement | null>(null)
const galleryInput = ref<HTMLInputElement | null>(null)

const editor = useEditor({
  extensions: [
    StarterKit,
    Underline,
    Placeholder.configure({ placeholder: 'Describe tu producto...' }),
  ],
  content: '',
  onUpdate({ editor: e }) {
    form.description = e.getHTML()
  },
})

const statusOptions: { value: ProductStatus; label: string; desc: string }[] = [
  { value: 'draft', label: 'Borrador', desc: 'No visible, en edición' },
  { value: 'private', label: 'Privado', desc: 'Solo accesible con enlace directo' },
  { value: 'public', label: 'Público', desc: 'Visible en la tienda' },
]

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
  }
}

function removeGalleryImage(idx: number) {
  gallery.value.splice(idx, 1)
}

// Options
const productOptions = ref<PbOption[]>([])
const showPreview = ref(false)

const previewSelections = ref<Record<string, string>>({})

function getOptionTypeLabel(type: string): string {
  const map: Record<string, string> = { select: 'Selector', radio: 'Radio', checkbox: 'Checkbox', color: 'Color', image: 'Imagen', text: 'Texto', textarea: 'Área de texto' }
  return map[type] ?? type
}

function selectPreviewValue(optionId: string, value: string) {
  previewSelections.value[optionId] = value
}

async function loadProduct() {
  if (!productId.value) return
  loading.value = true
  try {
    const p = await adminPanelService.showProduct(productId.value)
    form.name = p.name
    form.slug = p.slug
    form.description = p.description ?? ''
    form.status = p.is_active ? 'public' : 'draft'
    form.base_price_amount = p.base_price.amount
    form.base_price_currency = p.base_price.currency
    thumbnail.value = p.image ?? null
    gallery.value = (p.gallery ?? []).map((img) => ({ ...img }))
    productOptions.value = p.options ?? []
    editor.value?.commands.setContent(form.description)
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
      is_active: form.status === 'public',
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
onBeforeUnmount(() => editor.value?.destroy())
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
      <!-- LEFT COLUMN -->
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
              <span v-if="form.slug" class="product-slug-display">
                /{{ form.slug }}
              </span>
            </div>
            <div class="admin-product-form__field">
              <label class="admin-product-form__label">Descripción</label>
              <div class="tiptap-editor-wrapper">
                <div v-if="editor" class="tiptap-toolbar">
                  <button type="button" :class="{ 'is-active': editor.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()">
                    <strong>B</strong>
                  </button>
                  <button type="button" :class="{ 'is-active': editor.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()">
                    <em>I</em>
                  </button>
                  <button type="button" :class="{ 'is-active': editor.isActive('underline') }" @click="editor.chain().focus().toggleUnderline().run()">
                    <u>U</u>
                  </button>
                  <span class="tiptap-toolbar__sep"></span>
                  <button type="button" :class="{ 'is-active': editor.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                  </button>
                  <button type="button" :class="{ 'is-active': editor.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><text x="2" y="8" fill="currentColor" stroke="none" font-size="7" font-weight="600">1</text><text x="2" y="14" fill="currentColor" stroke="none" font-size="7" font-weight="600">2</text><text x="2" y="20" fill="currentColor" stroke="none" font-size="7" font-weight="600">3</text></svg>
                  </button>
                  <span class="tiptap-toolbar__sep"></span>
                  <button type="button" :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
                    H2
                  </button>
                  <button type="button" :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">
                    H3
                  </button>
                </div>
                <EditorContent :editor="editor" class="tiptap-content" />
              </div>
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
              <p class="admin-drop-zone__hint">JPG, PNG o WebP</p>
            </div>
            <div v-else class="admin-drop-zone__preview">
              <img :src="thumbnail" alt="Imagen principal" />
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
                  <input v-model="img.name" class="admin-gallery-item__input" placeholder="Nombre" />
                  <input v-model="img.alt_text" class="admin-gallery-item__input" placeholder="Texto alternativo" />
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
                <label class="admin-product-form__label" for="pf-price">Precio Base</label>
                <input
                  id="pf-price"
                  v-model.number="form.base_price_amount"
                  type="number"
                  min="0"
                  step="0.01"
                  class="admin-product-form__input"
                  required
                />
              </div>
              <div class="admin-product-form__field">
                <label class="admin-product-form__label" for="pf-currency">Moneda</label>
                <select id="pf-currency" v-model="form.base_price_currency" class="admin-product-form__select">
                  <option value="MXN">MXN</option>
                  <option value="USD">USD</option>
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

            <div class="admin-pricing-grid" style="margin-top: 1rem;">
              <div class="admin-product-form__field">
                <label class="admin-product-form__label" for="pf-discount-type">Tipo de descuento</label>
                <select id="pf-discount-type" v-model="form.discount_type" class="admin-product-form__select">
                  <option value="none">Sin descuento</option>
                  <option value="percentage">Porcentaje</option>
                  <option value="fixed">Monto fijo</option>
                </select>
              </div>
              <div v-if="form.discount_type !== 'none'" class="admin-product-form__field">
                <label class="admin-product-form__label" for="pf-discount">
                  {{ form.discount_type === 'percentage' ? 'Descuento (%)' : 'Descuento' }}
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

        <!-- Options assigned (Plantilla) -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Opciones del Producto</h3>
            <button v-if="isEdit" type="button" class="admin-btn admin-btn--sm admin-btn--outline" @click="router.push('/admin/options')">
              Gestionar Opciones
            </button>
          </div>
          <div class="admin-content-card__body">
            <p v-if="!isEdit" style="font-size: 0.85rem; color: var(--admin-text-secondary);">
              Guarda el producto primero para poder asignar opciones.
            </p>
            <template v-else>
              <p v-if="!productOptions.length" style="font-size: 0.85rem; color: var(--admin-text-secondary); margin-bottom: 0;">
                Este producto aún no tiene opciones asignadas. Ve a
                <a href="/admin/options" @click.prevent="router.push('/admin/options')" style="color: var(--admin-primary); font-weight: 500;">Opciones</a>
                para crear y asignar opciones a este producto.
              </p>
              <div v-else class="product-options-list">
                <div v-for="option in productOptions" :key="option.id" class="product-option-item">
                  <div class="product-option-item__header">
                    <span class="product-option-item__label">{{ option.label }}</span>
                    <span class="admin-badge admin-badge--info" style="font-size: 0.65rem;">{{ getOptionTypeLabel(option.type) }}</span>
                    <span v-if="option.is_required" class="admin-badge admin-badge--error" style="font-size: 0.6rem;">Requerido</span>
                  </div>
                  <p v-if="option.help_text" class="product-option-item__help">{{ option.help_text }}</p>
                  <div v-if="option.values.length" class="product-option-item__values">
                    <template v-if="option.type === 'color'">
                      <span v-for="val in option.values" :key="val.id" class="product-option-value-color" :title="val.label">
                        <span class="product-option-value-color__swatch" :style="{ background: (val.metadata?.hex as string) || '#ccc' }"></span>
                        <span class="product-option-value-color__name">{{ val.label }}</span>
                      </span>
                    </template>
                    <template v-else>
                      <span v-for="val in option.values" :key="val.id" class="product-option-value-tag">{{ val.label }}</span>
                    </template>
                  </div>
                </div>
              </div>
            </template>
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
              <select v-model="form.status" class="admin-product-form__select">
                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
              <p class="product-status-hint">
                {{ statusOptions.find(o => o.value === form.status)?.desc }}
              </p>
            </div>
          </div>
        </div>

        <!-- Product Details -->
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

        <!-- Preview card -->
        <div class="admin-content-card">
          <div class="admin-content-card__header">
            <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
              <h3 class="admin-content-card__title">Vista Previa</h3>
              <button v-if="isEdit" type="button" class="admin-btn admin-btn--sm admin-btn--outline" @click="showPreview = !showPreview">
                {{ showPreview ? 'Ocultar' : 'Mostrar' }}
              </button>
            </div>
          </div>
          <div v-if="showPreview || !isEdit" class="admin-content-card__body product-preview-mini">
            <div class="product-preview-mini__thumb">
              <img v-if="thumbnail" :src="thumbnail" alt="" />
              <div v-else class="product-preview-mini__placeholder">Sin imagen</div>
            </div>
            <h4 class="product-preview-mini__name">{{ form.name || 'Sin nombre' }}</h4>
            <span class="product-preview-mini__slug">/{{ form.slug || '...' }}</span>
            <p class="product-preview-mini__price">
              {{ form.base_price_currency }} {{ form.base_price_amount.toLocaleString('es-MX') }}
            </p>
            <span
              class="admin-badge"
              :class="form.status === 'public' ? 'admin-badge--success' : form.status === 'private' ? 'admin-badge--warning' : 'admin-badge--default'"
            >
              {{ statusOptions.find(o => o.value === form.status)?.label }}
            </span>

            <!-- Options preview -->
            <div v-if="productOptions.length" class="product-preview-options">
              <div v-for="option in productOptions" :key="option.id" class="product-preview-option">
                <span class="product-preview-option__label">{{ option.label }}</span>
                <template v-if="option.type === 'color' && option.values.length">
                  <div class="product-preview-option__colors">
                    <button
                      v-for="val in option.values"
                      :key="val.id"
                      type="button"
                      class="product-preview-color-btn"
                      :class="{ 'product-preview-color-btn--selected': previewSelections[option.id] === val.value }"
                      :style="{ background: (val.metadata?.hex as string) || '#ccc' }"
                      :title="val.label"
                      @click="selectPreviewValue(option.id, val.value)"
                    ></button>
                  </div>
                </template>
                <template v-else-if="option.values.length">
                  <select class="product-preview-option__select" @change="selectPreviewValue(option.id, ($event.target as HTMLSelectElement).value)">
                    <option value="">Seleccionar...</option>
                    <option v-for="val in option.values" :key="val.id" :value="val.value">{{ val.label }}</option>
                  </select>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
.product-slug-display {
  display: block;
  font-size: 0.78rem;
  color: var(--admin-primary);
  opacity: 0.7;
  margin-top: 0.25rem;
  font-family: monospace;
}

.product-status-hint {
  font-size: 0.75rem;
  color: var(--admin-text-muted);
  margin-top: 0.35rem;
  margin-bottom: 0;
}

/* TipTap editor */
.tiptap-editor-wrapper {
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  overflow: hidden;
  transition: border-color 0.15s ease;
}

.tiptap-editor-wrapper:focus-within {
  border-color: var(--admin-primary);
  box-shadow: 0 0 0 3px var(--admin-primary-light);
}

.tiptap-toolbar {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 0.35rem 0.5rem;
  background: var(--admin-bg);
  border-bottom: 1px solid var(--admin-border);
  flex-wrap: wrap;
}

.tiptap-toolbar button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 28px;
  border: none;
  background: transparent;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
  font-family: var(--admin-font);
  transition: all 0.1s ease;
  padding: 0;
}

.tiptap-toolbar button:hover {
  background: var(--admin-primary-light);
  color: var(--admin-primary);
}

.tiptap-toolbar button.is-active {
  background: var(--admin-primary);
  color: #fff;
}

.tiptap-toolbar button svg {
  width: 16px;
  height: 16px;
  display: block;
}

.tiptap-toolbar__sep {
  width: 1px;
  height: 18px;
  background: var(--admin-border);
  margin: 0 4px;
}

.tiptap-content {
  min-height: 150px;
  max-height: 400px;
  overflow-y: auto;
}

.tiptap-content :deep(.tiptap) {
  padding: 0.75rem;
  font-size: 0.875rem;
  line-height: 1.6;
  color: var(--admin-text);
  outline: none;
  min-height: 120px;
}

.tiptap-content :deep(.tiptap p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  color: var(--admin-text-muted);
  pointer-events: none;
  float: left;
  height: 0;
}

.tiptap-content :deep(.tiptap h2) { font-size: 1.25rem; font-weight: 600; margin: 0.75rem 0 0.5rem; }
.tiptap-content :deep(.tiptap h3) { font-size: 1.1rem; font-weight: 600; margin: 0.5rem 0 0.35rem; }
.tiptap-content :deep(.tiptap p) { margin: 0 0 0.5rem; }
.tiptap-content :deep(.tiptap ul),
.tiptap-content :deep(.tiptap ol) { padding-left: 1.5rem; margin: 0.25rem 0 0.5rem; }
.tiptap-content :deep(.tiptap li) { margin-bottom: 0.2rem; }

/* Mini preview */
.product-preview-mini__thumb {
  width: 100%;
  aspect-ratio: 4 / 3;
  border-radius: 8px;
  overflow: hidden;
  background: var(--admin-bg);
  margin-bottom: 0.75rem;
}

.product-preview-mini__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-preview-mini__placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--admin-text-muted);
  font-size: 0.8rem;
}

.product-preview-mini__name {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0 0 0.25rem;
  color: var(--admin-text);
}

.product-preview-mini__price {
  font-size: 0.85rem;
  color: var(--admin-text-secondary);
  margin: 0 0 0.5rem;
}

.product-preview-mini__slug {
  font-size: 0.72rem;
  color: var(--admin-primary);
  opacity: 0.6;
  font-family: monospace;
  display: block;
  margin: -0.1rem 0 0.4rem;
}

/* Options in product form */
.product-options-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.product-option-item {
  background: var(--admin-bg);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  border: 1px solid var(--admin-border);
}

.product-option-item__header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.product-option-item__label {
  font-weight: 600;
  font-size: 0.85rem;
  color: var(--admin-text);
}

.product-option-item__help {
  font-size: 0.75rem;
  color: var(--admin-text-muted);
  margin: 0.25rem 0 0;
}

.product-option-item__values {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-top: 0.5rem;
}

.product-option-value-tag {
  background: var(--admin-surface);
  border: 1px solid var(--admin-border);
  border-radius: 4px;
  padding: 0.15rem 0.5rem;
  font-size: 0.75rem;
  color: var(--admin-text-secondary);
}

.product-option-value-color {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.75rem;
}

.product-option-value-color__swatch {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 1.5px solid var(--admin-border);
  display: inline-block;
}

.product-option-value-color__name {
  color: var(--admin-text-secondary);
}

/* Preview options */
.product-preview-options {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--admin-border);
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.product-preview-option__label {
  display: block;
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
  margin-bottom: 0.25rem;
}

.product-preview-option__colors {
  display: flex;
  gap: 0.35rem;
  flex-wrap: wrap;
}

.product-preview-color-btn {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid var(--admin-border);
  cursor: pointer;
  transition: all 0.15s ease;
  padding: 0;
}

.product-preview-color-btn:hover {
  transform: scale(1.15);
}

.product-preview-color-btn--selected {
  border-color: var(--admin-primary);
  box-shadow: 0 0 0 2px var(--admin-primary-light);
}

.product-preview-option__select {
  width: 100%;
  padding: 0.35rem 0.5rem;
  border: 1px solid var(--admin-border);
  border-radius: 6px;
  font-size: 0.78rem;
  font-family: var(--admin-font);
  color: var(--admin-text);
  background: var(--admin-surface);
}
</style>
