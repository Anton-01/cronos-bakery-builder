<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { onBeforeRouteLeave, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Editor from 'primevue/editor'
import ProgressSpinner from 'primevue/progressspinner'

import ProductGeneralForm from '../components/ProductGeneralForm.vue'
import ProductMediaGallery from '../components/ProductMediaGallery.vue'
import ProductPricing from '../components/ProductPricing.vue'
import ProductOptionsManager from '../components/ProductOptionsManager.vue'

import { useMediaGallery } from '../composables/useMediaGallery'
import { useProductOptions } from '../composables/useProductOptions'
import { useProductForm, statusOptions } from '../composables/useProductForm'
import { useProductPreview } from '../composables/useProductPreview'

const router = useRouter()

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

// --- Legend rich text (PrimeVue Editor v-model string) ---
const legendContent = ref('')

// --- Product Options ---
const {
  optionLinks, showAddOption, addOptionTemplateId, availableTemplates,
  expandedLinks, legendModal, legendHasChanged,
  getOptionTypeLabel, isValueEnabled, toggleLinkExpand,
  openLegendModal, closeLegendModal, saveLegend,
  toggleValue, addOptionLink, removeOptionLink,
  loadOptionLinks, setLinksFromOptions,
} = useProductOptions(() => productId.value, legendContent)

// --- Form & Submit ---
const {
  productId, isEdit, loading, saving, form, isDirty,
  onNameInput, loadProduct, submitForm,
} = useProductForm({
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

// --- Unsaved changes dialog ---
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

onMounted(() => {
  loadProduct()
  loadOptionLinks()
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
          <a href="/admin/productos" @click.prevent="router.push('/admin/productos')">Productos</a>
          <span>/</span> {{ isEdit ? 'Editar' : 'Nuevo' }}
        </div>
      </div>
      <div style="display:flex; gap:0.5rem;">
        <Button label="Cancelar" severity="secondary" outlined @click="router.push('/admin/productos')" />
        <Button
          :label="saving ? 'Guardando...' : (isEdit ? 'Actualizar' : 'Crear Producto')"
          :loading="saving"
          :disabled="saving || (isEdit && !isDirty)"
          @click="submitForm"
        />
      </div>
    </div>

    <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <form v-else class="admin-product-form" @submit.prevent="submitForm">
      <!-- LEFT COLUMN -->
      <div>
        <ProductGeneralForm v-model="form" @name-input="onNameInput" />

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

      <!-- RIGHT COLUMN -->
      <div>
        <!-- Status + Preview -->
        <Card style="margin-bottom:1.5rem;">
          <template #title>Estado</template>
          <template #content>
            <Select
              v-model="form.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              fluid
              style="margin-bottom:0.5rem;"
            />
            <p class="product-status-hint">
              {{ statusOptions.find(o => o.value === form.status)?.desc }}
            </p>

            <hr class="product-section-divider" />

            <div style="display:flex; align-items:center; justify-content:space-between;">
              <span style="font-size:0.8rem; font-weight:600; color:var(--admin-text-secondary);">Vista Previa</span>
              <Button
                v-if="isEdit && form.slug"
                label="Ver como usuario"
                icon="pi pi-eye"
                size="small"
                severity="secondary"
                outlined
                type="button"
                @click="openPreview"
              />
              <span v-else style="font-size:0.75rem; color:var(--admin-text-muted);">
                Guarda primero para ver la vista previa
              </span>
            </div>
          </template>
        </Card>

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
        <Card style="margin-bottom:1.5rem;">
          <template #title>Detalles del Producto</template>
          <template #content>
            <div style="display:flex; flex-direction:column; gap:0.4rem;">
              <label style="font-size:0.8rem; font-weight:600; color:var(--admin-text-secondary);" for="pf-tags">Etiquetas</label>
              <InputText id="pf-tags" v-model="form.tags" fluid placeholder="pastel, chocolate, cumpleaños" />
              <small style="font-size:0.7rem; color:var(--admin-text-muted);">Separa las etiquetas con comas</small>
            </div>
          </template>
        </Card>
      </div>
    </form>

    <!-- Preview dialog -->
    <Dialog v-model:visible="previewVisible" modal header="Vista previa del producto" :style="{ width: '800px' }" @hide="closePreview">
      <div v-if="previewLoading" style="display:flex; justify-content:center; padding:3rem;">
        <ProgressSpinner />
      </div>
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
              <span
                v-for="tag in ((previewData as any).tags || '').split(',').map((t: string) => t.trim()).filter(Boolean)"
                :key="tag"
                class="preview-product__tag"
              >{{ tag }}</span>
            </div>
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Legend dialog -->
    <Dialog
      v-model:visible="legendModal"
      modal
      header="Leyenda de la Opción"
      :style="{ width: '560px' }"
      @hide="closeLegendModal"
    >
      <p style="font-size:0.78rem; color:var(--admin-text-muted); margin:0 0 0.75rem;">
        Nota interna del administrador sobre esta opción para este producto específico.
      </p>
      <Editor v-model="legendContent" editorStyle="height: 180px" />
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined @click="closeLegendModal" />
        <Button label="Guardar" :disabled="!legendHasChanged" @click="saveLegend" />
      </template>
    </Dialog>

    <!-- Unsaved changes dialog -->
    <Dialog
      v-model:visible="unsavedModalVisible"
      modal
      header="Cambios pendientes sin guardar"
      :style="{ width: '420px' }"
      :closable="false"
    >
      <p style="font-size:0.9rem; color:var(--admin-text-secondary); margin:0;">
        Tienes cambios sin guardar en este producto. ¿Qué deseas hacer?
      </p>
      <template #footer>
        <div style="display:flex; flex-direction:column; gap:0.5rem; width:100%;">
          <Button label="Guardar y cerrar" fluid @click="saveAndLeave" />
          <Button label="Salir sin guardar" severity="danger" outlined fluid @click="discardAndLeave" />
          <Button label="Seguir editando" severity="secondary" outlined fluid @click="cancelLeave" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style>
.product-status-hint {
  font-size: 0.75rem;
  color: var(--admin-text-muted);
  margin: 0;
}
.product-section-divider {
  border: none;
  border-top: 1px solid var(--admin-border);
  margin: 1rem 0;
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
.option-link-action-btn:hover { background: var(--admin-primary-light); }
.option-link-action-btn--delete { color: var(--admin-danger, #dc3545); }
.option-link-action-btn--delete:hover { background: rgba(220, 53, 69, 0.08); }
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
.option-link-item__legend-content p { margin: 0 0 0.3rem; }

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
.option-link-value-row--disabled { opacity: 0.45; }
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
.media-drop-placeholder svg { opacity: 0.5; }
.media-drop-placeholder p {
  font-size: 0.82rem;
  margin: 0;
  font-weight: 500;
  color: var(--admin-text-secondary);
}
.media-drop-placeholder span { font-size: 0.72rem; }
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
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  flex-shrink: 0;
  background: #fff;
}
.media-thumb-preview__img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.media-thumb-preview__remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(0,0,0,0.55);
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
.media-thumb-preview__remove:hover { background: rgba(0,0,0,0.8); }
.media-drop-clickable {
  position: absolute;
  inset: 0;
  cursor: pointer;
  z-index: 0;
}
.media-thumb-preview { z-index: 1; }
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
.media-file-meta__sep { opacity: 0.4; }

/* Preview product layout */
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
.preview-product__img-wrap img { width: 100%; height: 100%; object-fit: cover; }
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
.preview-product__name { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.5rem; }
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
.preview-product__desc p { margin: 0 0 0.5rem; }
.preview-product__tags { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.preview-product__tag {
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  background: var(--admin-bg);
  font-size: 0.75rem;
  color: var(--admin-text-secondary);
  font-weight: 500;
}
@media (max-width: 640px) {
  .preview-product { grid-template-columns: 1fr; }
}
</style>
