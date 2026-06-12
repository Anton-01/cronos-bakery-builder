<script setup lang="ts">

import { onBeforeUnmount, onMounted, ref } from 'vue'
import { onBeforeRouteLeave, useRouter } from 'vue-router'
import { EditorContent } from '@tiptap/vue-3'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useConfirm } from '@/composables/useConfirm'

import ProductGeneralForm from '../components/ProductGeneralForm.vue'
import ProductMediaGallery from '../components/ProductMediaGallery.vue'
import ProductPricing from '../components/ProductPricing.vue'
import ProductOptionsManager from '../components/ProductOptionsManager.vue'

import { useRichTextEditor } from '../composables/useRichTextEditor'
import { useMediaGallery } from '../composables/useMediaGallery'
import { useProductOptions } from '../composables/useProductOptions'
import { useProductForm, statusOptions } from '../composables/useProductForm'
import { useProductPreview } from '../composables/useProductPreview'

const router = useRouter()
const {
  visible: confirmVisible,
  title: confirmTitle,
  message: confirmMessage,
  action: confirmAction,
  confirmText,
  cancelText,
  handleConfirm,
  handleCancel,
} = useConfirm()


// --- Media ---
const {
  thumbnail, thumbnailFile, thumbnailMeta, gallery, galleryDirty,
  dragOverThumb, dragOverGallery,
  onThumbDrop, onThumbSelect, removeThumb,
  onGalleryDrop, onGallerySelect, removeGalleryImage, markGalleryDirty,
  setThumbnailFromUrl, setGalleryFromImages,
} = useMediaGallery()

function onGalleryMetaChange() {
  markGalleryDirty()
}

// --- Rich Text Editors ---
const editor = useRichTextEditor({
  placeholder: 'Describe tu producto...',
  onUpdate: (html) => { form.description = html },
})


const legendEditor = useRichTextEditor({
  placeholder: 'Escribe la leyenda para esta opción en este producto...',
})

// --- Product Options ---
const {
  optionLinks, showAddOption, addOptionTemplateId, availableTemplates,
  expandedLinks, legendModal, legendHasChanged,
  getOptionTypeLabel, isValueEnabled, toggleLinkExpand,
  openLegendModal, closeLegendModal, saveLegend,
  toggleValue, addOptionLink, removeOptionLink,
  loadOptionLinks, setLinksFromOptions,
} = useProductOptions(() => productId.value, legendEditor)
// --- Form & Submit ---
const {
  productId, isEdit, loading, saving, form, isDirty,
  onNameInput, loadProduct, submitForm,
} = useProductForm({
  editor,
  thumbnail,
  thumbnailFile,
  gallery,
  galleryDirty,
  setThumbnailFromUrl,
  setGalleryFromImages,
  setLinksFromOptions,
  loadOptionLinks,
})

const {
  previewVisible, previewLoading, previewData,
  openPreview, closePreview,
} = useProductPreview(() => productId.value)


// --- Unsaved changes modal ---
const unsavedModalVisible = ref(false)
let pendingNavigation: (() => void) | null = null
function saveAndLeave() {
  unsavedModalVisible.value = false
  submitForm().then(() => {
    if (pendingNavigation) {
      pendingNavigation()
      pendingNavigation = null
    }
  })
}
function discardAndLeave() {
  unsavedModalVisible.value = false
  if (pendingNavigation) {
    pendingNavigation()
    pendingNavigation = null
  }
}
function cancelLeave() {
  unsavedModalVisible.value = false
  pendingNavigation = null
}
onBeforeRouteLeave((_to, _from, next) => {
  if (isEdit.value && isDirty.value && !saving.value) {
    pendingNavigation = () => next()
    unsavedModalVisible.value = true
    next(false)
  } else {
    next()
  }
})


// --- Lifecycle ---
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
        <button class="admin-btn admin-btn--primary" :disabled="saving || (isEdit && !isDirty)" @click="submitForm">
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
        <ProductGeneralForm v-model="form" :editor="editor" @name-input="onNameInput" />

        <ProductMediaGallery
            :thumbnail="thumbnail"
            :thumbnail-meta="thumbnailMeta"
            :gallery="gallery"
            v-model:drag-over-thumb="dragOverThumb"
            v-model:drag-over-gallery="dragOverGallery"
            @thumb-drop="onThumbDrop"
            @thumb-select="onThumbSelect"
            @thumb-remove="removeThumb"
            @gallery-drop="onGalleryDrop"
            @gallery-select="onGallerySelect"
            @gallery-remove="removeGalleryImage"
            @gallery-meta-change="onGalleryMetaChange"
        />

        <ProductPricing v-model="form" />
      </div>

      <!-- RIGHT COLUMN — Sidebar -->
      <div>
        <!-- Status + Preview -->
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

            <!-- Vista Previa link -->
            <div style="display: flex; align-items: center; justify-content: space-between;">
              <span style="font-size: 0.8rem; font-weight: 600; color: var(--admin-text-secondary);">Vista Previa</span>
              <button v-if="isEdit && form.slug" type="button" class="admin-btn admin-btn--sm admin-btn--outline" @click="openPreview">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
                Ver como usuario
              </button>
              <span v-else style="font-size: 0.75rem; color: var(--admin-text-muted);">
                Guarda primero para ver la vista previa
              </span>
            </div>

          </div>

        </div>

        <ProductOptionsManager
            :is-edit="isEdit"
            :option-links="optionLinks"
            v-model:show-add-option="showAddOption"
            v-model:add-option-template-id="addOptionTemplateId"
            :available-templates="availableTemplates"
            :expanded-links="expandedLinks"
            :get-option-type-label="getOptionTypeLabel"
            :is-value-enabled="isValueEnabled"
            @toggle-expand="toggleLinkExpand"
            @open-legend="openLegendModal"
            @remove-link="removeOptionLink"
            @toggle-value="toggleValue"
            @add-link="addOptionLink"
        />
        <!-- Product Details -->
        <div class="admin-content-card" style="margin-bottom: 1.5rem;">
          <div class="admin-content-card__header">
            <h3 class="admin-content-card__title">Detalles del Producto</h3>
          </div>
          <div class="admin-content-card__body">
            <div class="admin-product-form__field">
              <label class="admin-product-form__label" for="pf-tags">Etiquetas</label>
              <input id="pf-tags" v-model="form.tags" type="text" class="admin-product-form__input" placeholder="pastel, chocolate, cumpleaños"/>
              <p style="font-size: 0.7rem; color: var(--admin-text-muted); margin-top: 0.25rem;">
                Separa las etiquetas con comas
              </p>
            </div>
          </div>
        </div>

      </div>
    </form>

    <!-- Preview modal -->
    <Teleport to="body">
      <Transition name="confirm-fade">
        <div v-if="previewVisible" class="preview-modal-backdrop" @click.self="closePreview" @keydown.esc="closePreview">
          <div class="preview-modal">
            <div class="preview-modal__header">
              <h3 class="preview-modal__title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" /></svg>
                Vista previa del producto
              </h3>
              <button type="button" class="preview-modal__close" @click="closePreview">&times;</button>
            </div>
            <div class="preview-modal__body">
              <p v-if="previewLoading" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
                Cargando vista previa...
              </p>
              <template v-else-if="previewData">
                <div class="preview-product">
                  <div class="preview-product__media">
                    <div v-if="(previewData as any).image" class="preview-product__img-wrap">
                      <img :src="(previewData as any).image" :alt="(previewData as any).name" />
                    </div>
                    <div v-else class="preview-product__no-img">Sin imagen</div>
                  </div>
                  <div class="preview-product__info">
                    <h2 class="preview-product__name">{{ (previewData as any).name }}</h2>
                    <div class="preview-product__price">
                      {{ new Intl.NumberFormat('es-MX', { style: 'currency', currency: (previewData as any).base_price?.currency || 'MXN' }).format(((previewData as any).base_price?.amount || 0) / 100) }}
                    </div>
                    <div v-if="(previewData as any).description" class="preview-product__desc" v-html="(previewData as any).description"></div>
                    <div v-if="(previewData as any).tags" class="preview-product__tags">
                      <span v-for="tag in ((previewData as any).tags || '').split(',').map((t: string) => t.trim()).filter(Boolean)" :key="tag" class="preview-product__tag">
                        {{ tag }}
                      </span>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

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
              <button type="button" class="admin-btn admin-btn--primary" :disabled="!legendHasChanged" @click="saveLegend">Guardar</button>
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

    <!-- Unsaved changes modal -->
    <Teleport to="body">
      <div v-if="unsavedModalVisible" style="position: fixed; inset: 0; background: rgba(0,0,0,0.35); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 9999;">
        <div style="background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); padding: 2rem 2.5rem; max-width: 420px; width: 90vw; text-align: center; font-family: 'Plus Jakarta Sans', sans-serif;">
          <!-- Warning icon -->
          <div style="width: 64px; height: 64px; border-radius: 50%; background: #fef5e5; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffae1f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
          </div>

          <h3 style="font-size: 1.15rem; font-weight: 600; color: #2a3547; margin: 0 0 0.5rem; line-height: 1.3;">
            Cambios pendientes sin guardar
          </h3>
          <p style="font-size: 0.9rem; color: #5a6a85; margin: 0 0 1.5rem; line-height: 1.5;">
            Tienes cambios sin guardar en este producto. ¿Qué deseas hacer?
          </p>

          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <button type="button" style="width: 100%; padding: 0.65rem 1.5rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer; background: #5d87ff; color: #fff; font-family: inherit;" @click="saveAndLeave">
              Guardar y cerrar
            </button>
            <button type="button" style="width: 100%; padding: 0.65rem 1.5rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer; background: #f0f2f5; color: #fa896b; font-family: inherit;" @click="discardAndLeave">
              Salir sin guardar
            </button>
            <button type="button" style="width: 100%; padding: 0.65rem 1.5rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer; background: #f0f2f5; color: #5a6a85; font-family: inherit;" @click="cancelLeave">
              Seguir editando
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<style>
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

/* Media drop area */
.media-drop-area {
  border: 2px dashed var(--admin-border);
  border-radius: 10px;
  background: var(--admin-primary-light);
  min-height: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: border-color 0.15s ease, background 0.15s ease;
}
.media-drop-area--active {
  border-color: var(--admin-primary);
  background: #dce6ff;
}
.media-drop-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  cursor: pointer;
  padding: 1.5rem;
  color: var(--admin-text-muted);
  text-align: center;
}
.media-drop-placeholder svg {
  opacity: 0.5;
}
.media-drop-placeholder p {
  font-size: 0.82rem;
  margin: 0;
  font-weight: 500;
  color: var(--admin-text-secondary);
}
.media-drop-placeholder span {
  font-size: 0.72rem;
}
.media-thumb-preview {
  padding: 1rem;
  display: flex;
  align-items: flex-start;
  width: 100%;
}
.media-thumb-preview__img-wrap {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
  background: #fff;
}
.media-thumb-preview__img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.media-thumb-preview__remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  border: none;
  font-size: 14px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  transition: background 0.15s ease;
}
.media-thumb-preview__remove:hover {
  background: rgba(0, 0, 0, 0.8);
}
.media-drop-clickable {
  position: absolute;
  inset: 0;
  cursor: pointer;
  z-index: 0;
}
.media-thumb-preview {
  z-index: 1;
}
.media-file-meta {
  padding: 0.6rem 0 0;
  font-size: 0.78rem;
  color: var(--admin-text-secondary);
}
.media-file-meta__label {
  font-weight: 600;
  color: var(--admin-text);
  display: block;
  margin-bottom: 0.2rem;
}
.media-file-meta__details {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-wrap: wrap;
  font-size: 0.72rem;
  color: var(--admin-text-muted);
}
.media-file-meta__sep {
  opacity: 0.4;
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

.tiptap-content .tiptap {
  padding: 0.75rem;
  font-size: 0.875rem;
  line-height: 1.6;
  color: var(--admin-text);
  outline: none;
  min-height: 120px;
}

.tiptap-content .tiptap p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  color: var(--admin-text-muted);
  pointer-events: none;
  float: left;
  height: 0;
}

.tiptap-content .tiptap h2 { font-size: 1.25rem; font-weight: 600; margin: 0.75rem 0 0.5rem; }
.tiptap-content .tiptap h3 { font-size: 1.1rem; font-weight: 600; margin: 0.5rem 0 0.35rem; }
.tiptap-content .tiptap p { margin: 0 0 0.5rem; }
.tiptap-content .tiptap ul,
.tiptap-content .tiptap ol { padding-left: 1.5rem; margin: 0.25rem 0 0.5rem; }
.tiptap-content .tiptap li { margin-bottom: 0.2rem; }

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

.option-link-item__legend-content p {
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

/* Preview modal */
.preview-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9998;
  padding: 2rem;
}
.preview-modal {
  background: var(--admin-surface, #fff);
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.25);
  width: 100%;
  max-width: 800px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}
.preview-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--admin-border);
}
.preview-modal__title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  color: var(--admin-text);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.preview-modal__close {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  font-size: 1.4rem;
  cursor: pointer;
  color: var(--admin-text-muted);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s ease;
}
.preview-modal__close:hover {
  background: var(--admin-bg);
}
.preview-modal__body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}
.preview-product {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 2rem;
}
.preview-product__img-wrap {
  border-radius: 12px;
  overflow: hidden;
  background: var(--admin-bg);
  aspect-ratio: 1;
}
.preview-product__img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.preview-product__no-img {
  aspect-ratio: 1;
  background: var(--admin-bg);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--admin-text-muted);
  font-size: 0.85rem;
}
.preview-product__name {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}
.preview-product__price {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--admin-primary);
  margin-bottom: 1rem;
}
.preview-product__desc {
  font-size: 0.9rem;
  line-height: 1.6;
  color: var(--admin-text-secondary);
  margin-bottom: 1rem;
}
.preview-product__desc p {
  margin: 0 0 0.5rem;
}
.preview-product__tags {
  display: flex;
  gap: 0.35rem;
  flex-wrap: wrap;
}
.preview-product__tag {
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  background: var(--admin-bg);
  font-size: 0.75rem;
  color: var(--admin-text-secondary);
  font-weight: 500;
}
@media (max-width: 640px) {
  .preview-product {
    grid-template-columns: 1fr;
  }
}
</style>
