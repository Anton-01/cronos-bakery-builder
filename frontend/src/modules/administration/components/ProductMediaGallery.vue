<script setup lang="ts">
import type { Ref } from 'vue'
import type { GalleryImage } from '../composables/useMediaGallery'
interface FileMeta {
  name: string
  size: string
  type: string
}
defineProps<{
  thumbnail: string | null
  thumbnailMeta: FileMeta | null
  gallery: GalleryImage[]
  dragOverThumb: boolean
  dragOverGallery: boolean
  thumbInput: HTMLInputElement | null
  galleryInput: HTMLInputElement | null
}>()
defineEmits<{
  'update:dragOverThumb': [val: boolean]
  'update:dragOverGallery': [val: boolean]
  'thumb-drop': [e: DragEvent]
  'thumb-select': [e: Event]
  'thumb-remove': []
  'gallery-drop': [e: DragEvent]
  'gallery-select': [e: Event]
  'gallery-remove': [idx: number]
}>()
</script>

<template>
  <!-- Thumbnail -->
  <div class="admin-content-card" style="margin-bottom: 1.5rem;">
    <div class="admin-content-card__header">
      <h3 class="admin-content-card__title">Imagen Principal</h3>
    </div>
    <div class="admin-content-card__body">
      <div
          class="media-drop-area"
          :class="{ 'media-drop-area--active': dragOverThumb }"
          @dragover.prevent="$emit('update:dragOverThumb', true)"
          @dragleave="$emit('update:dragOverThumb', false)"
          @drop.prevent="$emit('thumb-drop', $event)"
      >
        <div v-if="thumbnail" class="media-thumb-preview">
          <div class="media-thumb-preview__img-wrap">
            <img :src="thumbnail" alt="Imagen principal" />
            <button type="button" class="media-thumb-preview__remove" @click.stop="$emit('thumb-remove')">&times;</button>
          </div>
        </div>
        <div v-if="!thumbnail" class="media-drop-placeholder" @click="thumbInput?.click()">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" /><circle cx="8.5" cy="8.5" r="1.5" /><polyline points="21 15 16 10 5 21" />
          </svg>
          <p>Arrastra una imagen o haz clic para seleccionar</p>
          <span>JPG, PNG o WebP — máx 5 MB</span>
        </div>
        <div v-else class="media-drop-clickable" @click="thumbInput?.click()"></div>
      </div>
      <input ref="thumbInput" type="file" accept="image/*" style="display: none;" @change="$emit('thumb-select', $event)" />
      <div v-if="thumbnailMeta" class="media-file-meta">
        <span class="media-file-meta__label">Archivo:</span>
        <div class="media-file-meta__details">
          <span>{{ thumbnailMeta.name }}</span>
          <span class="media-file-meta__sep">·</span>
          <span>{{ thumbnailMeta.size }}</span>
          <span class="media-file-meta__sep">·</span>
          <span>{{ thumbnailMeta.type }}</span>
        </div>
      </div>
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
          @dragover.prevent="$emit('update:dragOverGallery', true)"
          @dragleave="$emit('update:dragOverGallery', false)"
          @drop.prevent="$emit('gallery-drop', $event)"
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
      <input ref="galleryInput" type="file" accept="image/*" multiple style="display: none;" @change="$emit('gallery-select', $event)" />
      <div v-if="gallery.length" class="admin-gallery-grid">
        <div v-for="(img, idx) in gallery" :key="img.id" class="admin-gallery-item">
          <img :src="img._preview || img.path" alt="" class="admin-gallery-item__img" />
          <div class="admin-gallery-item__body">
            <input v-model="img.name" class="admin-gallery-item__input" placeholder="Nombre" />
            <input v-model="img.alt_text" class="admin-gallery-item__input" placeholder="Texto alternativo" />
          </div>
          <div class="admin-gallery-item__actions">
            <button type="button" class="admin-action-btn admin-action-btn--delete" @click="$emit('gallery-remove', idx)">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>