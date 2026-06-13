<script setup lang="ts">
import { ref, computed } from 'vue'
import { cmsContentService } from '../services/cmsContentService'
import { useToast } from '@/composables/useToast'
import type { MediaAsset } from '../types'

const props = defineProps<{
  visible: boolean
}>()

const emit = defineEmits<{
  close: []
  select: [asset: MediaAsset]
}>()

const { success, error } = useToast()

const assets = ref<MediaAsset[]>([])
const loading = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const search = ref('')
const dragOver = ref(false)

const filteredAssets = computed(() => {
  if (!search.value) return assets.value
  const q = search.value.toLowerCase()
  return assets.value.filter((a) => a.original_name.toLowerCase().includes(q))
})

async function loadAssets() {
  loading.value = true
  try {
    const res = await cmsContentService.mediaAssets()
    assets.value = res.data
  } finally {
    loading.value = false
  }
}

async function handleUpload(files: FileList | null) {
  if (!files?.length) return

  uploading.value = true
  uploadProgress.value = 0

  try {
    for (const file of Array.from(files)) {
      const asset = await cmsContentService.uploadMedia(file, (pct) => {
        uploadProgress.value = pct
      })
      assets.value.unshift(asset)
    }
    success('Archivos subidos correctamente')
  } catch (err) {
    error('Error al subir archivos')
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

function handleDrop(e: DragEvent) {
  dragOver.value = false
  handleUpload(e.dataTransfer?.files ?? null)
}

function handleFileInput(e: Event) {
  const input = e.target as HTMLInputElement
  handleUpload(input.files)
  input.value = ''
}

function selectAsset(asset: MediaAsset) {
  emit('select', asset)
  emit('close')
}

function statusLabel(status: string): string {
  const labels: Record<string, string> = {
    pending: 'Pendiente',
    processing: 'Procesando',
    completed: 'Listo',
    failed: 'Error',
  }
  return labels[status] ?? status
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

// Load assets when modal opens
import { watch } from 'vue'
watch(() => props.visible, (v) => {
  if (v) loadAssets()
})
</script>

<template>
  <Transition name="media-overlay">
    <div v-if="visible" class="media-overlay" @click.self="emit('close')">
      <Transition name="media-dialog" appear>
        <div class="media-dialog" role="dialog" aria-modal="true" aria-label="Media Library">
          <!-- Header -->
          <div class="media-dialog__header">
            <h2 class="media-dialog__title">Media Library</h2>
            <div class="media-dialog__search">
              <input
                v-model="search"
                type="search"
                placeholder="Buscar archivos..."
                class="media-dialog__search-input"
              />
            </div>
            <button class="media-dialog__close" @click="emit('close')" title="Cerrar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                <path d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Drag & Drop Zone -->
          <div
            class="media-dialog__dropzone"
            :class="{ 'media-dialog__dropzone--active': dragOver }"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="handleDrop"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="36" height="36">
              <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p>Arrastra archivos aquí o <label class="media-dialog__browse">selecciona<input type="file" multiple accept="image/*" @change="handleFileInput" /></label></p>
          </div>

          <!-- Upload Progress -->
          <Transition name="media-progress">
            <div v-if="uploading" class="media-dialog__progress">
              <div class="media-dialog__progress-bar">
                <div class="media-dialog__progress-fill" :style="{ width: `${uploadProgress}%` }"></div>
              </div>
              <span class="media-dialog__progress-text">{{ uploadProgress }}%</span>
            </div>
          </Transition>

          <!-- Grid -->
          <div class="media-dialog__body">
            <div v-if="loading" class="media-dialog__grid">
              <div v-for="n in 8" :key="n" class="media-card media-card--skeleton">
                <div class="media-card__thumb-skeleton"></div>
                <div class="media-card__name-skeleton"></div>
              </div>
            </div>

            <div v-else-if="filteredAssets.length === 0" class="media-dialog__empty">
              <p>No se encontraron archivos.</p>
            </div>

            <div v-else class="media-dialog__grid">
              <button
                v-for="asset in filteredAssets"
                :key="asset.id"
                class="media-card"
                :class="{ 'media-card--processing': asset.processing_status !== 'completed' }"
                @click="selectAsset(asset)"
              >
                <div class="media-card__thumb">
                  <img
                    v-if="asset.url && asset.processing_status === 'completed'"
                    :src="asset.url"
                    :alt="asset.original_name"
                    loading="lazy"
                  />
                  <div v-else class="media-card__placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="24" height="24">
                      <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>
                <div class="media-card__info">
                  <span class="media-card__name" :title="asset.original_name">{{ asset.original_name }}</span>
                  <span class="media-card__meta">{{ formatSize(asset.size) }}</span>
                </div>
                <span
                  v-if="asset.processing_status !== 'completed'"
                  class="media-card__status"
                  :class="`media-card__status--${asset.processing_status}`"
                >
                  {{ statusLabel(asset.processing_status) }}
                </span>
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
.media-overlay {
  position: fixed;
  inset: 0;
  z-index: 9000;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}
.media-dialog {
  background: #fff;
  border-radius: 16px;
  width: 90%;
  max-width: 900px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}
.media-dialog__header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}
.media-dialog__title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  white-space: nowrap;
}
.media-dialog__search {
  flex: 1;
}
.media-dialog__search-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  outline: none;
  transition: border-color 0.15s;
  box-sizing: border-box;
}
.media-dialog__search-input:focus {
  border-color: #6366f1;
}
.media-dialog__close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: none;
  border-radius: 8px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s;
}
.media-dialog__close:hover {
  background: #f3f4f6;
  color: #111827;
}

/* Dropzone */
.media-dialog__dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  margin: 1rem 1.5rem 0;
  padding: 1.25rem;
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  color: #9ca3af;
  transition: all 0.2s;
  text-align: center;
}
.media-dialog__dropzone--active {
  border-color: #6366f1;
  background: #eef2ff;
  color: #6366f1;
}
.media-dialog__dropzone p {
  margin: 0;
  font-size: 0.875rem;
}
.media-dialog__browse {
  color: #6366f1;
  cursor: pointer;
  font-weight: 500;
  text-decoration: underline;
}
.media-dialog__browse input {
  display: none;
}

/* Progress */
.media-dialog__progress {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0.75rem 1.5rem 0;
}
.media-dialog__progress-bar {
  flex: 1;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
}
.media-dialog__progress-fill {
  height: 100%;
  background: #6366f1;
  border-radius: 3px;
  transition: width 0.3s ease;
}
.media-dialog__progress-text {
  font-size: 0.75rem;
  font-weight: 500;
  color: #6366f1;
  min-width: 36px;
  text-align: right;
}

/* Grid */
.media-dialog__body {
  flex: 1;
  overflow-y: auto;
  padding: 1rem 1.5rem 1.5rem;
}
.media-dialog__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.75rem;
}
.media-dialog__empty {
  text-align: center;
  padding: 3rem;
  color: #9ca3af;
}

/* Cards */
.media-card {
  position: relative;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.15s;
  background: none;
  padding: 0;
  text-align: left;
  width: 100%;
}
.media-card:hover {
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}
.media-card--processing {
  opacity: 0.7;
}
.media-card__thumb {
  width: 100%;
  aspect-ratio: 1;
  overflow: hidden;
  background: #f9fafb;
}
.media-card__thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.media-card__placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  color: #d1d5db;
}
.media-card__info {
  padding: 0.5rem 0.625rem;
}
.media-card__name {
  display: block;
  font-size: 0.75rem;
  font-weight: 500;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.media-card__meta {
  font-size: 0.6875rem;
  color: #9ca3af;
}
.media-card__status {
  position: absolute;
  top: 0.375rem;
  right: 0.375rem;
  font-size: 0.625rem;
  font-weight: 600;
  padding: 0.125rem 0.5rem;
  border-radius: 9999px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.media-card__status--pending {
  background: #fef3c7;
  color: #d97706;
}
.media-card__status--processing {
  background: #dbeafe;
  color: #2563eb;
}
.media-card__status--failed {
  background: #fef2f2;
  color: #dc2626;
}

/* Skeletons */
.media-card--skeleton {
  pointer-events: none;
}
.media-card__thumb-skeleton {
  width: 100%;
  aspect-ratio: 1;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}
.media-card__name-skeleton {
  height: 12px;
  width: 70%;
  margin: 0.625rem;
  border-radius: 4px;
  background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s ease-in-out infinite;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Transitions */
.media-overlay-enter-active,
.media-overlay-leave-active { transition: opacity 0.2s ease; }
.media-overlay-enter-from,
.media-overlay-leave-to { opacity: 0; }
.media-dialog-enter-active { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.media-dialog-leave-active { transition: all 0.15s ease-in; }
.media-dialog-enter-from { opacity: 0; transform: scale(0.95) translateY(10px); }
.media-dialog-leave-to { opacity: 0; transform: scale(0.95); }
.media-progress-enter-active,
.media-progress-leave-active { transition: all 0.2s ease; }
.media-progress-enter-from,
.media-progress-leave-to { opacity: 0; height: 0; margin: 0; }
</style>
