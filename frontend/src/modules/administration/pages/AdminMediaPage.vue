<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { cmsContentService } from '@/modules/cms/services/cmsContentService'
import { useToast } from '@/composables/useToast'
import type { MediaAsset } from '@/modules/cms/types'

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
  return assets.value.filter((a: MediaAsset) => a.original_name.toLowerCase().includes(q))
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
      const asset = await cmsContentService.uploadMedia(file, (pct: number) => {
        uploadProgress.value = pct
      })
      assets.value.unshift(asset)
    }
    success('Archivos subidos correctamente')
  } catch (_e) {
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

async function deleteAsset(id: string) {
  try {
    await cmsContentService.deleteMedia(id)
    assets.value = assets.value.filter((a) => a.id !== id)
    success('Archivo eliminado')
  } catch (_e) {
    error('Error al eliminar')
  }
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function statusClass(status: string): string {
  const map: Record<string, string> = {
    pending: 'admin-badge--warning',
    processing: 'admin-badge--info',
    completed: 'admin-badge--success',
    failed: 'admin-badge--error',
  }
  return map[status] ?? ''
}

function statusLabel(status: string): string {
  const map: Record<string, string> = {
    pending: 'Pendiente',
    processing: 'Procesando',
    completed: 'Listo',
    failed: 'Error',
  }
  return map[status] ?? status
}

onMounted(loadAssets)
</script>

<template>
  <div class="admin-page">
    <div class="admin-page__header">
      <div>
        <h1 class="admin-page__title">Media Library</h1>
        <p class="admin-page__subtitle">Gestiona los archivos multimedia del CMS</p>
      </div>
    </div>

    <!-- Upload Zone -->
    <div
      class="media-dropzone"
      :class="{ 'media-dropzone--active': dragOver }"
      @dragover.prevent="dragOver = true"
      @dragleave="dragOver = false"
      @drop.prevent="handleDrop"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40">
        <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
      </svg>
      <p>Arrastra archivos aquí o <label class="media-dropzone__browse">selecciona archivos<input type="file" multiple accept="image/*" @change="handleFileInput" /></label></p>
    </div>

    <!-- Progress -->
    <div v-if="uploading" class="media-progress">
      <div class="media-progress__bar">
        <div class="media-progress__fill" :style="{ width: `${uploadProgress}%` }"></div>
      </div>
      <span>{{ uploadProgress }}%</span>
    </div>

    <!-- Search -->
    <div class="media-toolbar">
      <input v-model="search" type="search" placeholder="Buscar archivos..." class="media-toolbar__search" />
      <span class="media-toolbar__count">{{ filteredAssets.length }} archivos</span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="media-grid">
      <div v-for="n in 8" :key="n" class="media-card media-card--skeleton">
        <div class="media-card__thumb-skeleton"></div>
        <div class="media-card__info-skeleton"></div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="filteredAssets.length === 0" class="media-empty">
      <p>No se encontraron archivos multimedia.</p>
    </div>

    <!-- Grid -->
    <div v-else class="media-grid">
      <div v-for="asset in filteredAssets" :key="asset.id" class="media-card">
        <div class="media-card__thumb">
          <img v-if="asset.url && asset.processing_status === 'completed'" :src="asset.url" :alt="asset.original_name" loading="lazy" />
          <div v-else class="media-card__placeholder">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28">
              <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
        </div>
        <div class="media-card__body">
          <span class="media-card__name" :title="asset.original_name">{{ asset.original_name }}</span>
          <div class="media-card__meta">
            <span>{{ formatSize(asset.size) }}</span>
            <span :class="['admin-badge', statusClass(asset.processing_status)]">{{ statusLabel(asset.processing_status) }}</span>
          </div>
        </div>
        <button class="media-card__delete" title="Eliminar" @click="deleteAsset(asset.id)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" /></svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-page { max-width: 1200px; }
.admin-page__header { margin-bottom: 1.5rem; }
.admin-page__title { margin: 0; font-size: 1.5rem; font-weight: 600; }
.admin-page__subtitle { margin: 0.25rem 0 0; font-size: 0.875rem; color: var(--admin-text-secondary); }

.media-dropzone {
  display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
  padding: 2rem; border: 2px dashed var(--admin-border); border-radius: var(--admin-radius);
  background: var(--admin-surface); color: var(--admin-text-muted); text-align: center;
  transition: all 0.2s; margin-bottom: 1rem; cursor: pointer;
}
.media-dropzone--active { border-color: var(--admin-primary); background: var(--admin-primary-light); color: var(--admin-primary); }
.media-dropzone p { margin: 0; font-size: 0.875rem; }
.media-dropzone__browse { color: var(--admin-primary); font-weight: 600; cursor: pointer; text-decoration: underline; }
.media-dropzone__browse input { display: none; }

.media-progress { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; font-size: 0.8125rem; color: var(--admin-primary); font-weight: 500; }
.media-progress__bar { flex: 1; height: 6px; background: var(--admin-border); border-radius: 3px; overflow: hidden; }
.media-progress__fill { height: 100%; background: var(--admin-primary); border-radius: 3px; transition: width 0.3s; }

.media-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; gap: 1rem; }
.media-toolbar__search {
  padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: var(--admin-radius-sm);
  font-size: 0.875rem; outline: none; width: 280px; font-family: var(--admin-font);
}
.media-toolbar__search:focus { border-color: var(--admin-primary); }
.media-toolbar__count { font-size: 0.8125rem; color: var(--admin-text-muted); }

.media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
.media-card {
  position: relative; background: var(--admin-surface); border: 1px solid var(--admin-border);
  border-radius: var(--admin-radius); overflow: hidden; transition: box-shadow 0.15s;
}
.media-card:hover { box-shadow: var(--admin-shadow-lg); }
.media-card__thumb { width: 100%; aspect-ratio: 1; background: #f9fafb; overflow: hidden; }
.media-card__thumb img { width: 100%; height: 100%; object-fit: cover; }
.media-card__placeholder { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: #d1d5db; }
.media-card__body { padding: 0.625rem 0.75rem; }
.media-card__name { display: block; font-size: 0.8125rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.media-card__meta { display: flex; align-items: center; justify-content: space-between; margin-top: 0.25rem; font-size: 0.75rem; color: var(--admin-text-muted); }
.media-card__delete {
  position: absolute; top: 0.5rem; right: 0.5rem; width: 28px; height: 28px; border: none;
  background: rgba(255,255,255,0.9); border-radius: 6px; cursor: pointer; display: none;
  align-items: center; justify-content: center; color: var(--admin-error);
}
.media-card:hover .media-card__delete { display: inline-flex; }

.admin-badge { display: inline-block; padding: 0.1rem 0.5rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 600; }
.admin-badge--success { background: var(--admin-success-light); color: #0d9488; }
.admin-badge--warning { background: var(--admin-warning-light); color: #b45309; }
.admin-badge--info { background: var(--admin-info-light); color: #1d4ed8; }
.admin-badge--error { background: var(--admin-error-light); color: #dc2626; }

.media-card--skeleton .media-card__thumb-skeleton { width: 100%; aspect-ratio: 1; background: linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 50%,#f3f4f6 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
.media-card--skeleton .media-card__info-skeleton { height: 14px; width: 60%; margin: 0.75rem; border-radius: 4px; background: linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 50%,#f3f4f6 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
@keyframes shimmer { 0%{background-position:200% 0}100%{background-position:-200% 0} }

.media-empty { text-align: center; padding: 4rem; color: var(--admin-text-muted); }
</style>
