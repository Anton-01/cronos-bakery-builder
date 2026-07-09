<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import ProgressBar from 'primevue/progressbar'
import Tag from 'primevue/tag'

import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import {
  mediaLibraryService,
  type AllowedFileType,
  type MediaAsset,
} from '@/services/mediaLibrary'

/**
 * Media Library reutilizable (galería grid + drag & drop + filtros).
 * En modo `selectable` emite `select` con el asset elegido — pensado para
 * embeberse en un Dialog desde cualquier formulario (Theme Builder, CMS…).
 *
 * `accept` restringe la galería y la subida a un prefijo MIME (ej. "image/")
 * — útil cuando el campo destino solo admite imágenes (logo, favicon).
 */
const props = withDefaults(defineProps<{
  selectable?: boolean
  accept?: string | null
}>(), {
  selectable: false,
  accept: null,
})

const emit = defineEmits<{
  select: [asset: MediaAsset]
}>()

const { success, error } = useToast()
const { confirm } = useConfirm()

const assets = ref<MediaAsset[]>([])
const fileTypes = ref<AllowedFileType[]>([])
const loading = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const dragOver = ref(false)

const search = ref('')
const filterTypeId = ref<number | null>(null)
const page = ref(1)
const perPage = 24
const total = ref(0)

const fileInput = ref<HTMLInputElement | null>(null)

const typeOptions = computed(() => [
  { label: 'Todos los tipos', value: null as number | null },
  ...fileTypes.value
    .filter((t) => !props.accept || t.mime_types.some((m) => m.startsWith(props.accept!)))
    .map((t) => ({ label: `${t.category} · ${t.name}`, value: t.id as number | null })),
])

const acceptAttr = computed(() => {
  const active = fileTypes.value
  const relevant = props.accept
    ? active.filter((t) => t.mime_types.some((m) => m.startsWith(props.accept!)))
    : active
  return relevant.flatMap((t) => t.extensions.map((e) => `.${e}`)).join(',') || undefined
})

function isImage(asset: MediaAsset): boolean {
  return asset.mime_type.startsWith('image/')
}

function iconFor(asset: MediaAsset): string {
  const type = fileTypes.value.find((t) => t.mime_types.includes(asset.mime_type))
  if (type) return type.icon_reference
  if (asset.mime_type.startsWith('video/')) return 'pi pi-video'
  if (asset.mime_type.startsWith('audio/')) return 'pi pi-volume-up'
  if (asset.mime_type === 'application/pdf') return 'pi pi-file-pdf'
  return 'pi pi-file'
}

function formatSize(bytes: number): string {
  if (bytes >= 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
  if (bytes >= 1024) return `${(bytes / 1024).toFixed(0)} KB`
  return `${bytes} B`
}

async function loadAssets() {
  loading.value = true
  try {
    const res = await mediaLibraryService.assets({
      page: page.value,
      per_page: perPage,
      search: search.value || undefined,
      mime: props.accept ?? undefined,
      file_type_id: filterTypeId.value ?? undefined,
    })
    assets.value = res.data
    total.value = res.meta?.total ?? res.data.length
  } catch {
    error('Error al cargar la biblioteca de medios')
  } finally {
    loading.value = false
  }
}

async function loadFileTypes() {
  try {
    fileTypes.value = await mediaLibraryService.fileTypes(true)
  } catch {
    fileTypes.value = []
  }
}

async function handleUpload(files: FileList | File[] | null) {
  if (!files || files.length === 0) return
  uploading.value = true
  uploadProgress.value = 0
  try {
    for (const file of Array.from(files)) {
      const asset = await mediaLibraryService.upload(file, (pct) => { uploadProgress.value = pct })
      assets.value.unshift(asset)
      total.value += 1
    }
    success('Archivos subidos correctamente')
  } catch (e) {
    const detail = (e as { response?: { data?: { errors?: { file?: string[] } } } })
      .response?.data?.errors?.file?.[0]
    error(detail ?? 'Error al subir archivos')
  } finally {
    uploading.value = false
    uploadProgress.value = 0
  }
}

function onDrop(e: DragEvent) {
  dragOver.value = false
  void handleUpload(e.dataTransfer?.files ?? null)
}

function onFileInput(e: Event) {
  const input = e.target as HTMLInputElement
  void handleUpload(input.files)
  input.value = ''
}

async function removeAsset(asset: MediaAsset) {
  const ok = await confirm({
    title: 'Eliminar archivo',
    message: `Se eliminará "${asset.original_name}" del almacenamiento. Esta acción no se puede deshacer.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    await mediaLibraryService.delete(asset.id)
    assets.value = assets.value.filter((a) => a.id !== asset.id)
    total.value -= 1
    success('Archivo eliminado')
  } catch {
    error('Error al eliminar el archivo')
  }
}

function selectAsset(asset: MediaAsset) {
  if (props.selectable) emit('select', asset)
}

function onPage(event: { page: number }) {
  page.value = event.page + 1
  void loadAssets()
}

function onFilterChange() {
  page.value = 1
  void loadAssets()
}

function openInNewTab(url: string) {
  window.open(url, '_blank', 'noopener')
}

let searchTimer: ReturnType<typeof setTimeout> | undefined
function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { page.value = 1; void loadAssets() }, 350)
}

onMounted(() => {
  void loadFileTypes()
  void loadAssets()
})

defineExpose({ reload: loadAssets })
</script>

<template>
  <div class="media-library">
    <!-- Toolbar: búsqueda + filtro por tipo + botón subir -->
    <div class="media-library__toolbar">
      <InputText
        v-model="search"
        placeholder="Buscar por nombre..."
        class="media-library__search"
        @input="onSearchInput"
      />
      <Select
        v-model="filterTypeId"
        :options="typeOptions"
        optionLabel="label"
        optionValue="value"
        placeholder="Tipo de archivo"
        class="media-library__filter"
        @change="onFilterChange"
      />
      <Button
        v-tooltip.top="'Subir archivos'"
        icon="pi pi-upload"
        size="small"
        rounded
        aria-label="Subir archivos"
        :loading="uploading"
        @click="fileInput?.click()"
      />
      <input
        ref="fileInput"
        type="file"
        multiple
        :accept="acceptAttr"
        style="display:none"
        @change="onFileInput"
      >
    </div>

    <!-- Zona drag & drop -->
    <div
      class="media-library__dropzone"
      :class="{ 'media-library__dropzone--over': dragOver }"
      @dragover.prevent="dragOver = true"
      @dragleave.prevent="dragOver = false"
      @drop.prevent="onDrop"
    >
      <i class="pi pi-cloud-upload" style="font-size:1.25rem;" />
      <span>Arrastra archivos aquí o usa el botón de subida</span>
    </div>

    <ProgressBar
      v-if="uploading"
      :value="uploadProgress"
      style="height:6px; margin-bottom:0.75rem;"
    />

    <!-- Galería grid -->
    <div v-if="loading" class="media-library__empty">Cargando…</div>
    <div v-else-if="!assets.length" class="media-library__empty">
      No hay archivos que coincidan con el filtro.
    </div>
    <div v-else class="media-library__grid">
      <div
        v-for="asset in assets"
        :key="asset.id"
        class="media-library__card"
        :class="{ 'media-library__card--selectable': selectable }"
        role="button"
        :tabindex="selectable ? 0 : -1"
        @click="selectAsset(asset)"
        @keydown.enter="selectAsset(asset)"
      >
        <div class="media-library__preview">
          <img
            v-if="isImage(asset) && asset.url"
            :src="asset.url"
            :alt="asset.original_name"
            loading="lazy"
          >
          <i v-else :class="iconFor(asset)" style="font-size:2rem; color:var(--admin-text-muted, #7c8fac);" />
        </div>
        <div class="media-library__meta">
          <span class="media-library__name" :title="asset.original_name">{{ asset.original_name }}</span>
          <div class="media-library__sub">
            <Tag :value="asset.mime_type.split('/')[1]?.toUpperCase() ?? asset.mime_type" severity="secondary" style="font-size:0.55rem;" />
            <span class="media-library__size">{{ formatSize(asset.size) }}</span>
          </div>
        </div>
        <div class="media-library__actions" @click.stop>
          <Button
            v-if="asset.url"
            v-tooltip.top="'Abrir en pestaña nueva'"
            icon="pi pi-external-link"
            size="small"
            severity="secondary"
            text
            rounded
            aria-label="Abrir en pestaña nueva"
            @click="asset.url && openInNewTab(asset.url)"
          />
          <Button
            v-tooltip.top="'Eliminar archivo'"
            icon="pi pi-trash"
            size="small"
            severity="danger"
            text
            rounded
            aria-label="Eliminar archivo"
            @click="removeAsset(asset)"
          />
        </div>
      </div>
    </div>

    <Paginator
      v-if="total > perPage"
      :rows="perPage"
      :totalRecords="total"
      :first="(page - 1) * perPage"
      style="margin-top:0.75rem;"
      @page="onPage"
    />
  </div>
</template>

<style scoped>
.media-library__toolbar {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 0.75rem;
}
.media-library__search { flex: 1; }
.media-library__filter { min-width: 220px; }

.media-library__dropzone {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 2px dashed var(--admin-border, #e5eaef);
  border-radius: 10px;
  padding: 0.9rem;
  margin-bottom: 0.75rem;
  color: var(--admin-text-muted, #7c8fac);
  font-size: 0.85rem;
  transition: border-color 0.15s ease, background 0.15s ease;
}
.media-library__dropzone--over {
  border-color: var(--admin-primary, #5d87ff);
  background: rgba(93, 135, 255, 0.06);
}

.media-library__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 0.75rem;
}

.media-library__card {
  position: relative;
  border: 1px solid var(--admin-border, #e5eaef);
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
}
.media-library__card--selectable { cursor: pointer; }
.media-library__card--selectable:hover {
  border-color: var(--admin-primary, #5d87ff);
  box-shadow: 0 2px 8px rgba(93, 135, 255, 0.18);
}

.media-library__preview {
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--admin-bg, #f5f7fa);
  overflow: hidden;
}
.media-library__preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.media-library__meta { padding: 0.5rem 0.6rem 0.4rem; }
.media-library__name {
  display: block;
  font-size: 0.72rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.media-library__sub {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.25rem;
}
.media-library__size { font-size: 0.65rem; color: var(--admin-text-muted, #7c8fac); }

.media-library__actions {
  display: flex;
  justify-content: flex-end;
  padding: 0 0.35rem 0.35rem;
}

.media-library__empty {
  text-align: center;
  color: var(--admin-text-muted, #7c8fac);
  font-size: 0.85rem;
  padding: 1.5rem 0;
}
</style>
