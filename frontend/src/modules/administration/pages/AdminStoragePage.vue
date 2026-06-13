<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { cmsContentService } from '@/modules/cms/services/cmsContentService'
import { useSudo } from '@/composables/useSudo'
import { useToast } from '@/composables/useToast'
import type { StorageProvider } from '@/modules/cms/types'

const { success, error } = useToast()
const { withSudo } = useSudo()

const providers = ref<StorageProvider[]>([])
const loading = ref(false)
const editingId = ref<string | null>(null)
const editForm = ref<{
  name: string
  bucket: string
  region: string
  is_active: boolean
  is_default: boolean
  credentials: Record<string, string>
}>({ name: '', bucket: '', region: '', is_active: false, is_default: false, credentials: {} })

async function loadProviders() {
  loading.value = true
  try {
    providers.value = await cmsContentService.storageProviders()
  } finally {
    loading.value = false
  }
}

function startEdit(provider: StorageProvider) {
  editingId.value = provider.id
  editForm.value = {
    name: provider.name,
    bucket: provider.bucket,
    region: provider.region ?? '',
    is_active: provider.is_active,
    is_default: provider.is_default,
    credentials: {},
  }
}

function cancelEdit() {
  editingId.value = null
}

async function saveProvider() {
  if (!editingId.value) return
  try {
    await withSudo(async () => {
      const updated = await cmsContentService.updateStorageProvider(editingId.value!, {
        name: editForm.value.name,
        bucket: editForm.value.bucket,
        region: editForm.value.region || null,
        is_active: editForm.value.is_active,
        is_default: editForm.value.is_default,
        credentials: Object.keys(editForm.value.credentials).length > 0 ? editForm.value.credentials : undefined,
      })
      const idx = providers.value.findIndex((p) => p.id === editingId.value)
      if (idx !== -1) providers.value[idx] = updated
      editingId.value = null
      success('Proveedor actualizado')
    })
  } catch (_e) {
    error('Error al actualizar proveedor')
  }
}

function driverLabel(driver: string): string {
  const map: Record<string, string> = { s3: 'Amazon S3', gcs: 'Google Cloud Storage', azure: 'Azure Blob' }
  return map[driver] ?? driver
}

function driverColor(driver: string): string {
  const map: Record<string, string> = { s3: '#ff9900', gcs: '#4285f4', azure: '#0078d4' }
  return map[driver] ?? '#6b7280'
}

onMounted(loadProviders)
</script>

<template>
  <div class="admin-page">
    <div class="admin-page__header">
      <div>
        <h1 class="admin-page__title">Proveedores de Almacenamiento</h1>
        <p class="admin-page__subtitle">Configura las credenciales de S3, Google Cloud Storage o Azure</p>
      </div>
    </div>

    <div v-if="loading" class="storage-loading">Cargando proveedores...</div>

    <div v-else-if="providers.length === 0" class="storage-empty">
      <p>No hay proveedores de almacenamiento configurados.</p>
    </div>

    <div v-else class="storage-grid">
      <div v-for="provider in providers" :key="provider.id" class="storage-card">
        <!-- View Mode -->
        <template v-if="editingId !== provider.id">
          <div class="storage-card__header">
            <div class="storage-card__driver" :style="{ borderColor: driverColor(provider.driver) }">
              {{ driverLabel(provider.driver) }}
            </div>
            <div class="storage-card__badges">
              <span v-if="provider.is_default" class="storage-badge storage-badge--primary">Default</span>
              <span :class="['storage-badge', provider.is_active ? 'storage-badge--success' : 'storage-badge--muted']">
                {{ provider.is_active ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
          </div>
          <div class="storage-card__body">
            <h3 class="storage-card__name">{{ provider.name }}</h3>
            <div class="storage-card__detail"><span>Bucket:</span> {{ provider.bucket }}</div>
            <div class="storage-card__detail"><span>Región:</span> {{ provider.region ?? '—' }}</div>
          </div>
          <div class="storage-card__footer">
            <button class="storage-card__edit" @click="startEdit(provider)">Configurar</button>
          </div>
        </template>

        <!-- Edit Mode -->
        <template v-else>
          <div class="storage-card__header">
            <div class="storage-card__driver" :style="{ borderColor: driverColor(provider.driver) }">
              {{ driverLabel(provider.driver) }}
            </div>
            <span class="storage-badge storage-badge--info">Editando</span>
          </div>
          <form class="storage-card__form" @submit.prevent="saveProvider">
            <label>
              <span>Nombre</span>
              <input v-model="editForm.name" type="text" required />
            </label>
            <label>
              <span>Bucket</span>
              <input v-model="editForm.bucket" type="text" required />
            </label>
            <label>
              <span>Región</span>
              <input v-model="editForm.region" type="text" placeholder="us-east-1" />
            </label>
            <label v-if="provider.driver === 's3'">
              <span>Access Key</span>
              <input v-model="editForm.credentials.key" type="password" placeholder="Nueva key (dejar vacío para mantener)" />
            </label>
            <label v-if="provider.driver === 's3'">
              <span>Secret Key</span>
              <input v-model="editForm.credentials.secret" type="password" placeholder="Nuevo secret (dejar vacío para mantener)" />
            </label>
            <label v-if="provider.driver === 'gcs'">
              <span>Project ID</span>
              <input v-model="editForm.credentials.project_id" type="text" />
            </label>
            <div class="storage-card__toggles">
              <label class="storage-card__toggle">
                <input v-model="editForm.is_active" type="checkbox" />
                <span>Activo</span>
              </label>
              <label class="storage-card__toggle">
                <input v-model="editForm.is_default" type="checkbox" />
                <span>Default</span>
              </label>
            </div>
            <div class="storage-card__form-actions">
              <button type="button" class="storage-card__cancel" @click="cancelEdit">Cancelar</button>
              <button type="submit" class="storage-card__save">Guardar</button>
            </div>
          </form>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-page__header { margin-bottom: 1.5rem; }
.admin-page__title { margin: 0; font-size: 1.5rem; font-weight: 600; }
.admin-page__subtitle { margin: 0.25rem 0 0; font-size: 0.875rem; color: var(--admin-text-secondary); }

.storage-loading, .storage-empty { text-align: center; padding: 3rem; color: var(--admin-text-muted); background: var(--admin-surface); border-radius: var(--admin-radius); border: 1px solid var(--admin-border); }

.storage-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1.25rem; }

.storage-card { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); overflow: hidden; }
.storage-card__header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--admin-border); background: #fafbfc; }
.storage-card__driver { font-size: 0.8125rem; font-weight: 600; padding: 0.2rem 0.75rem; border-radius: 6px; border-left: 3px solid; }
.storage-card__badges { display: flex; gap: 0.375rem; }

.storage-badge { font-size: 0.6875rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 9999px; }
.storage-badge--primary { background: var(--admin-primary-light); color: var(--admin-primary); }
.storage-badge--success { background: var(--admin-success-light); color: #0d9488; }
.storage-badge--muted { background: #f3f4f6; color: #9ca3af; }
.storage-badge--info { background: var(--admin-info-light); color: #1d4ed8; }

.storage-card__body { padding: 1rem 1.25rem; }
.storage-card__name { margin: 0 0 0.5rem; font-size: 1.05rem; font-weight: 600; }
.storage-card__detail { font-size: 0.8125rem; color: var(--admin-text-secondary); margin-bottom: 0.25rem; }
.storage-card__detail span { font-weight: 500; color: var(--admin-text); margin-right: 0.25rem; }

.storage-card__footer { padding: 0.75rem 1.25rem; border-top: 1px solid var(--admin-border); }
.storage-card__edit {
  width: 100%; padding: 0.5rem; border: 1px solid var(--admin-primary); border-radius: var(--admin-radius-sm);
  background: transparent; color: var(--admin-primary); font-size: 0.8125rem; font-weight: 500;
  cursor: pointer; font-family: var(--admin-font); transition: all 0.15s;
}
.storage-card__edit:hover { background: var(--admin-primary); color: #fff; }

.storage-card__form { padding: 1rem 1.25rem; }
.storage-card__form label { display: block; margin-bottom: 0.75rem; }
.storage-card__form label span { display: block; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.25rem; }
.storage-card__form input[type="text"],
.storage-card__form input[type="password"] {
  width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: var(--admin-radius-sm);
  font-size: 0.875rem; font-family: var(--admin-font); outline: none; box-sizing: border-box;
}
.storage-card__form input:focus { border-color: var(--admin-primary); }
.storage-card__toggles { display: flex; gap: 1.25rem; margin-bottom: 1rem; }
.storage-card__toggle { display: flex; align-items: center; gap: 0.375rem; font-size: 0.8125rem; cursor: pointer; }
.storage-card__toggle input { cursor: pointer; }
.storage-card__form-actions { display: flex; gap: 0.5rem; justify-content: flex-end; }
.storage-card__cancel { padding: 0.5rem 1rem; border: 1px solid var(--admin-border); border-radius: var(--admin-radius-sm); background: transparent; font-size: 0.8125rem; cursor: pointer; font-family: var(--admin-font); }
.storage-card__save { padding: 0.5rem 1rem; border: none; border-radius: var(--admin-radius-sm); background: var(--admin-primary); color: #fff; font-size: 0.8125rem; font-weight: 500; cursor: pointer; font-family: var(--admin-font); }
</style>
