<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'

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
  'gallery-meta-change': []
}>()

const thumbInputRef = ref<HTMLInputElement | null>(null)
const galleryInputRef = ref<HTMLInputElement | null>(null)
</script>

<template>
  <!-- Thumbnail -->
  <Card style="margin-bottom:1.5rem;">
    <template #title>Imagen Principal</template>
    <template #content>
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
            <Button
              icon="pi pi-times"
              severity="danger"
              rounded
              size="small"
              class="media-thumb-preview__remove"
              @click.stop="$emit('thumb-remove')"
            />
          </div>
        </div>
        <div v-if="!thumbnail" class="media-drop-placeholder" @click="thumbInputRef?.click()">
          <i class="pi pi-image" style="font-size:2rem; opacity:0.5;" />
          <p>Arrastra una imagen o haz clic para seleccionar</p>
          <span>JPG, PNG o WebP — máx 5 MB</span>
        </div>
        <div v-else class="media-drop-clickable" @click="thumbInputRef?.click()" />
      </div>
      <input ref="thumbInputRef" type="file" accept="image/*" style="display:none;" @change="$emit('thumb-select', $event)" />
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
    </template>
  </Card>

  <!-- Gallery -->
  <Card style="margin-bottom:1.5rem;">
    <template #title>Galería de Imágenes</template>
    <template #content>
      <div
        class="admin-drop-zone"
        :class="{ 'admin-drop-zone--active': dragOverGallery }"
        style="margin-bottom:1rem;"
        @dragover.prevent="$emit('update:dragOverGallery', true)"
        @dragleave="$emit('update:dragOverGallery', false)"
        @drop.prevent="$emit('gallery-drop', $event)"
        @click="galleryInputRef?.click()"
      >
        <div class="admin-drop-zone__icon">
          <i class="pi pi-plus" style="font-size:2rem; opacity:0.5;" />
        </div>
        <p class="admin-drop-zone__text">Agrega imágenes a la galería</p>
        <p class="admin-drop-zone__hint">Puedes seleccionar varias a la vez</p>
      </div>
      <input ref="galleryInputRef" type="file" accept="image/*" multiple style="display:none;" @change="$emit('gallery-select', $event)" />
      <div v-if="gallery.length" class="admin-gallery-grid">
        <div v-for="(img, idx) in gallery" :key="img.id" class="admin-gallery-item">
          <img :src="img._preview || img.path" alt="" class="admin-gallery-item__img" />
          <div class="admin-gallery-item__body">
            <InputText v-model="img.name" placeholder="Nombre" size="small" fluid @input="$emit('gallery-meta-change')" />
            <InputText v-model="img.alt_text" placeholder="Texto alternativo" size="small" fluid style="margin-top:0.35rem;" @input="$emit('gallery-meta-change')" />
          </div>
          <div class="admin-gallery-item__actions">
            <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="$emit('gallery-remove', idx)" />
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>
