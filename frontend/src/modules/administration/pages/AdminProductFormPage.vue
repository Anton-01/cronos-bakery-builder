<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import Underline from '@tiptap/extension-underline'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import {
  adminPanelService,
  type ProductImage,
  type OptionTemplate,
  type ProductOptionLink,
} from '../services/adminPanelService'

const route = useRoute()
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

// --- Option Links ---
const optionLinks = ref<ProductOptionLink[]>([])
const allTemplates = ref<OptionTemplate[]>([])
const showPreview = ref(false)
const showAddOption = ref(false)
const addOptionTemplateId = ref('')

const availableTemplates = computed(() => {
  const linkedIds = new Set(optionLinks.value.map((l) => l.template_id))
  return allTemplates.value.filter((t) => !linkedIds.has(t.id))
})

// Legend modal
const legendModal = ref(false)
const legendLinkId = ref<string | null>(null)
const legendEditor = useEditor({
  extensions: [
    StarterKit,
    Underline,
    Placeholder.configure({ placeholder: 'Escribe la leyenda para esta opción en este producto...' }),
  ],
  content: '',
})

function openLegendModal(link: ProductOptionLink) {
  legendLinkId.value = link.id
  legendEditor.value?.commands.setContent(link.legend || '')
  legendModal.value = true
}

async function saveLegend() {
  if (!legendLinkId.value || !productId.value) return
  const html = legendEditor.value?.getHTML() || ''
  const content = html === '<p></p>' ? null : html
  try {
    const updated = await adminPanelService.updateProductOptionLink(productId.value, legendLinkId.value, { legend: content })
    const idx = optionLinks.value.findIndex((l) => l.id === legendLinkId.value)
    if (idx !== -1) optionLinks.value[idx] = updated
    legendModal.value = false
    success('Leyenda actualizada')
  } catch {
    error('Error al guardar la leyenda')
  }
}

function closeLegendModal() {
  legendModal.value = false
  legendLinkId.value = null
}

function getOptionTypeLabel(type: string): string {
  const map: Record<string, string> = { select: 'Selector', radio: 'Radio', checkbox: 'Checkbox', color: 'Color', image: 'Imagen', text: 'Texto', textarea: 'Área de texto' }
  return map[type] ?? type
}

function isValueEnabled(link: ProductOptionLink, valueId: string): boolean {
  if (!link.enabled_value_ids) return true
  return link.enabled_value_ids.includes(valueId)
}

async function toggleValue(link: ProductOptionLink, valueId: string) {
  if (!productId.value || !link.template) return
  const allIds = link.template.values.map((v) => v.id)
  let current = link.enabled_value_ids ? [...link.enabled_value_ids] : [...allIds]

  if (current.includes(valueId)) {
    current = current.filter((id) => id !== valueId)
  } else {
    current.push(valueId)
  }

  const enabledIds = current.length === allIds.length ? null : current

  try {
    const updated = await adminPanelService.updateProductOptionLink(productId.value, link.id, { enabled_value_ids: enabledIds })
    const idx = optionLinks.value.findIndex((l) => l.id === link.id)
    if (idx !== -1) optionLinks.value[idx] = updated
  } catch {
    error('Error al actualizar valores')
  }
}

async function addOptionLink() {
  if (!productId.value || !addOptionTemplateId.value) return
  try {
    const link = await adminPanelService.createProductOptionLink(productId.value, { template_id: addOptionTemplateId.value })
    optionLinks.value.push(link)
    addOptionTemplateId.value = ''
    showAddOption.value = false
    success('Opción vinculada al producto')
  } catch {
    error('Error al vincular opción')
  }
}

async function removeOptionLink(link: ProductOptionLink) {
  const tplName = link.template?.label || 'esta opción'
  const ok = await confirm({
    title: 'Desvincular opción',
    message: `Se eliminará la vinculación de "${tplName}" con este producto. Los valores configurados se perderán.`,
    action: 'delete',
    confirmText: 'Desvincular',
  })
  if (!ok || !productId.value) return

  try {
    await adminPanelService.deleteProductOptionLink(productId.value, link.id)
    optionLinks.value = optionLinks.value.filter((l) => l.id !== link.id)
    success('Opción desvinculada')
  } catch {
    error('Error al desvincular opción')
  }
}

// Expanded link panels
const expandedLinks = ref<Set<string>>(new Set())

function toggleLinkExpand(linkId: string) {
  if (expandedLinks.value.has(linkId)) {
    expandedLinks.value.delete(linkId)
  } else {
    expandedLinks.value.add(linkId)
  }
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
    editor.value?.commands.setContent(form.description)
  } catch {
    error('Error al cargar el producto')
  } finally {
    loading.value = false
  }
}

async function loadOptionLinks() {
  if (!productId.value) return
  try {
    const [links, templates] = await Promise.all([
      adminPanelService.productOptionLinks(productId.value),
      adminPanelService.optionTemplates(),
    ])
    optionLinks.value = links
    allTemplates.value = templates
  } catch {
    // silently fail, options are secondary
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

onMounted(() => {
  loadProduct()
  loadOptionLinks()
})
onBeforeUnmount(() => {
  editor.value?.destroy()
  legendEditor.value?.destroy()
})
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
      </div>

      <!-- RIGHT COLUMN — Sidebar -->
      <div>
        <!-- Status + Preview integrated -->
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

            <hr class="product-section-divider" />

            <!-- Vista Previa inline -->
            <div class="product-preview-mini">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="font-size: 0.8rem; font-weight: 600; color: var(--admin-text-secondary);">Vista Previa</span>
                <button v-if="isEdit" type="button" class="admin-btn admin-btn--sm admin-btn--outline" @click="showPreview = !showPreview">
                  {{ showPreview ? 'Ocultar' : 'Mostrar' }}
                </button>
              </div>
              <div v-if="showPreview || !isEdit">
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
              </div>
            </div>
          </div>
        </div>

        <!-- Opciones del Producto (above Detalles) -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Opciones del Producto</h3>
            <button v-if="isEdit && availableTemplates.length" type="button" class="admin-btn admin-btn--sm admin-btn--outline" @click="showAddOption = !showAddOption">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
              Vincular
            </button>
          </div>
          <div class="admin-content-card__body">
            <p v-if="!isEdit" style="font-size: 0.85rem; color: var(--admin-text-secondary);">
              Guarda el producto primero para poder asignar opciones.
            </p>
            <template v-else>
              <!-- Add option selector -->
              <div v-if="showAddOption" class="option-link-add">
                <select v-model="addOptionTemplateId" class="admin-product-form__select">
                  <option value="">Selecciona una opción...</option>
                  <option v-for="tpl in availableTemplates" :key="tpl.id" :value="tpl.id">
                    {{ tpl.label }} ({{ getOptionTypeLabel(tpl.type) }})
                  </option>
                </select>
                <div style="display: flex; gap: 0.35rem; margin-top: 0.5rem;">
                  <button type="button" class="admin-btn admin-btn--sm admin-btn--primary" :disabled="!addOptionTemplateId" @click="addOptionLink">
                    Agregar
                  </button>
                  <button type="button" class="admin-btn admin-btn--sm admin-btn--outline" @click="showAddOption = false; addOptionTemplateId = ''">
                    Cancelar
                  </button>
                </div>
              </div>

              <p v-if="!optionLinks.length && !showAddOption" style="font-size: 0.85rem; color: var(--admin-text-secondary); margin-bottom: 0;">
                Este producto aún no tiene opciones asignadas.
              </p>

              <!-- Option links list -->
              <div v-if="optionLinks.length" class="option-links-list">
                <div v-for="link in optionLinks" :key="link.id" class="option-link-item">
                  <!-- Header row: name + actions -->
                  <div class="option-link-item__header">
                    <button type="button" class="option-link-item__toggle" @click="toggleLinkExpand(link.id)">
                      <svg
                        width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        :style="{ transform: expandedLinks.has(link.id) ? 'rotate(90deg)' : 'rotate(0deg)', transition: 'transform 0.15s ease' }"
                      ><polyline points="9 18 15 12 9 6" /></svg>
                    </button>
                    <span class="option-link-item__name">{{ link.template?.label ?? '...' }}</span>
                    <span class="admin-badge admin-badge--info" style="font-size: 0.6rem;">{{ getOptionTypeLabel(link.template?.type ?? '') }}</span>
                    <div class="option-link-item__actions">
                      <button type="button" class="option-link-action-btn" title="Leyenda" @click="openLegendModal(link)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" />
                        </svg>
                      </button>
                      <button type="button" class="option-link-action-btn option-link-action-btn--delete" title="Desvincular" @click="removeOptionLink(link)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                        </svg>
                      </button>
                    </div>
                  </div>

                  <!-- Expanded: details + value tree -->
                  <div v-if="expandedLinks.has(link.id) && link.template" class="option-link-item__body">
                    <div v-if="link.template.help_text" class="option-link-item__help">
                      {{ link.template.help_text }}
                    </div>
                    <div v-if="link.legend" class="option-link-item__legend">
                      <span class="option-link-item__legend-label">Leyenda:</span>
                      <div class="option-link-item__legend-content" v-html="link.legend"></div>
                    </div>

                    <!-- Values tree -->
                    <div v-if="link.template.values.length" class="option-link-values-tree">
                      <div
                        v-for="val in link.template.values"
                        :key="val.id"
                        class="option-link-value-row"
                        :class="{ 'option-link-value-row--disabled': !isValueEnabled(link, val.id) }"
                      >
                        <label class="option-link-value-check">
                          <input
                            type="checkbox"
                            :checked="isValueEnabled(link, val.id)"
                            @change="toggleValue(link, val.id)"
                          />
                          <span class="option-link-value-check__mark"></span>
                        </label>
                        <template v-if="link.template!.type === 'color' && val.metadata?.hex">
                          <span class="option-link-value-swatch" :style="{ background: (val.metadata.hex as string) }"></span>
                        </template>
                        <span class="option-link-value-label">{{ val.label }}</span>
                        <span v-if="val.price_modifier_type !== 'none'" class="option-link-value-price">
                          {{ val.price_modifier_type === 'add' ? '+' : val.price_modifier_type === 'subtract' ? '-' : '=' }}{{ (val.price_modifier_amount / 100).toFixed(2) }}
                        </span>
                      </div>
                    </div>
                    <p v-else style="font-size: 0.75rem; color: var(--admin-text-muted); margin: 0;">
                      Sin valores configurados
                    </p>
                  </div>
                </div>
              </div>
            </template>
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
      </div>
    </form>

    <!-- Legend modal -->
    <Teleport to="body">
      <Transition name="confirm-fade">
        <div v-if="legendModal" class="legend-modal-backdrop" @click.self="closeLegendModal">
          <div class="legend-modal">
            <div class="legend-modal__header">
              <h3 class="legend-modal__title">Leyenda de la Opción</h3>
              <button type="button" class="legend-modal__close" @click="closeLegendModal">&times;</button>
            </div>
            <div class="legend-modal__body">
              <p style="font-size: 0.78rem; color: var(--admin-text-muted); margin: 0 0 0.75rem;">
                Nota interna del administrador sobre esta opción para este producto específico.
              </p>
              <div class="tiptap-editor-wrapper">
                <div v-if="legendEditor" class="tiptap-toolbar">
                  <button type="button" :class="{ 'is-active': legendEditor.isActive('bold') }" @click="legendEditor.chain().focus().toggleBold().run()">
                    <strong>B</strong>
                  </button>
                  <button type="button" :class="{ 'is-active': legendEditor.isActive('italic') }" @click="legendEditor.chain().focus().toggleItalic().run()">
                    <em>I</em>
                  </button>
                  <button type="button" :class="{ 'is-active': legendEditor.isActive('underline') }" @click="legendEditor.chain().focus().toggleUnderline().run()">
                    <u>U</u>
                  </button>
                  <span class="tiptap-toolbar__sep"></span>
                  <button type="button" :class="{ 'is-active': legendEditor.isActive('bulletList') }" @click="legendEditor.chain().focus().toggleBulletList().run()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>
                  </button>
                </div>
                <EditorContent :editor="legendEditor" class="tiptap-content" />
              </div>
            </div>
            <div class="legend-modal__footer">
              <button type="button" class="admin-btn admin-btn--outline" @click="closeLegendModal">Cancelar</button>
              <button type="button" class="admin-btn admin-btn--primary" @click="saveLegend">Guardar</button>
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

.product-section-divider {
  border: none;
  border-top: 1px solid var(--admin-border);
  margin: 1rem 0;
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

/* Option links */
.option-link-add {
  background: var(--admin-bg);
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  padding: 0.75rem;
  margin-bottom: 0.75rem;
}

.option-links-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.option-link-item {
  background: var(--admin-bg);
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  overflow: hidden;
}

.option-link-item__header {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.6rem 0.75rem;
}

.option-link-item__toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 0;
  color: var(--admin-text-muted);
  flex-shrink: 0;
}

.option-link-item__toggle svg {
  display: block;
  stroke: currentColor;
  fill: none;
}

.option-link-item__name {
  font-weight: 600;
  font-size: 0.83rem;
  color: var(--admin-text);
  flex: 1;
  min-width: 0;
}

.option-link-item__actions {
  display: flex;
  gap: 0.2rem;
  margin-left: auto;
  flex-shrink: 0;
}

.option-link-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  color: var(--admin-primary);
  transition: all 0.15s ease;
  padding: 0;
}

.option-link-action-btn svg {
  display: block;
  stroke: currentColor;
  fill: none;
  flex-shrink: 0;
}

.option-link-action-btn:hover {
  background: var(--admin-primary-light);
}

.option-link-action-btn--delete {
  color: var(--admin-danger, #dc3545);
}

.option-link-action-btn--delete:hover {
  background: rgba(220, 53, 69, 0.08);
}

.option-link-item__body {
  padding: 0 0.75rem 0.75rem;
  border-top: 1px solid var(--admin-border);
}

.option-link-item__help {
  font-size: 0.75rem;
  color: var(--admin-text-muted);
  margin: 0.5rem 0 0;
}

.option-link-item__legend {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: var(--admin-surface);
  border-radius: 6px;
  border: 1px dashed var(--admin-border);
}

.option-link-item__legend-label {
  font-size: 0.68rem;
  font-weight: 600;
  color: var(--admin-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  display: block;
  margin-bottom: 0.25rem;
}

.option-link-item__legend-content {
  font-size: 0.8rem;
  line-height: 1.5;
  color: var(--admin-text-secondary);
}

.option-link-item__legend-content :deep(p) {
  margin: 0 0 0.3rem;
}

/* Values tree */
.option-link-values-tree {
  margin-top: 0.6rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.option-link-value-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.35rem 0.5rem;
  border-radius: 6px;
  transition: opacity 0.15s ease;
}

.option-link-value-row--disabled {
  opacity: 0.45;
}

.option-link-value-check {
  display: flex;
  align-items: center;
  cursor: pointer;
  position: relative;
  flex-shrink: 0;
}

.option-link-value-check input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.option-link-value-check__mark {
  width: 16px;
  height: 16px;
  border: 1.5px solid var(--admin-border);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--admin-surface);
  transition: all 0.15s ease;
}

.option-link-value-check input:checked + .option-link-value-check__mark {
  background: var(--admin-primary);
  border-color: var(--admin-primary);
}

.option-link-value-check input:checked + .option-link-value-check__mark::after {
  content: '';
  width: 4px;
  height: 8px;
  border: solid #fff;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
  margin-top: -1px;
}

.option-link-value-swatch {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 1px solid var(--admin-border);
  flex-shrink: 0;
}

.option-link-value-label {
  font-size: 0.8rem;
  color: var(--admin-text);
  flex: 1;
}

.option-link-value-price {
  font-size: 0.72rem;
  color: var(--admin-text-muted);
  font-family: monospace;
  flex-shrink: 0;
}

/* Legend modal */
.legend-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9998;
  padding: 2rem;
}

.legend-modal {
  background: var(--admin-surface, #fff);
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
  width: 100%;
  max-width: 560px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}

.legend-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--admin-border);
}

.legend-modal__title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  color: var(--admin-text);
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}

.legend-modal__close {
  width: 28px;
  height: 28px;
  border: none;
  background: transparent;
  font-size: 1.2rem;
  cursor: pointer;
  color: var(--admin-text-muted);
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s ease;
}

.legend-modal__close:hover {
  background: var(--admin-bg);
}

.legend-modal__body {
  padding: 1rem 1.25rem;
  overflow-y: auto;
  flex: 1;
}

.legend-modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-top: 1px solid var(--admin-border);
}
</style>
