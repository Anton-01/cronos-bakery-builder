<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { cmsContentService } from '@/modules/cms/services/cmsContentService'
import { useToast } from '@/composables/useToast'
import type { CacheSetting } from '@/modules/cms/types'

const { success, error } = useToast()

const settings = ref<CacheSetting[]>([])
const loading = ref(false)
const flushing = ref<string | null>(null)

async function loadSettings() {
  loading.value = true
  try {
    settings.value = await cmsContentService.cacheSettings()
  } finally {
    loading.value = false
  }
}

async function updateTtl(setting: CacheSetting) {
  try {
    const updated = await cmsContentService.updateCacheTtl(setting.id, setting.ttl_seconds)
    const idx = settings.value.findIndex((s) => s.id === setting.id)
    if (idx !== -1) settings.value[idx] = updated
    success(`TTL de "${setting.tag}" actualizado`)
  } catch (_e) {
    error('Error al actualizar TTL')
  }
}

async function flushTag(tag: string) {
  flushing.value = tag
  try {
    await cmsContentService.flushCacheTag(tag)
    const idx = settings.value.findIndex((s) => s.tag === tag)
    if (idx !== -1) settings.value[idx].last_flushed_at = new Date().toISOString()
    success(`Caché "${tag}" limpiada exitosamente`)
  } catch (_e) {
    error('Error al limpiar caché')
  } finally {
    flushing.value = null
  }
}

function formatDate(d: string | null): string {
  if (!d) return 'Nunca'
  return new Date(d).toLocaleString('es-ES', { dateStyle: 'medium', timeStyle: 'short' })
}

function formatTtl(seconds: number): string {
  if (seconds < 60) return `${seconds}s`
  if (seconds < 3600) return `${Math.floor(seconds / 60)}min`
  return `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}min`
}

onMounted(loadSettings)
</script>

<template>
  <div class="admin-page">
    <div class="admin-page__header">
      <div>
        <h1 class="admin-page__title">Configuración de Caché</h1>
        <p class="admin-page__subtitle">Controla el TTL y limpia tags de caché de la API pública</p>
      </div>
    </div>

    <div v-if="loading" class="cache-loading">Cargando configuración...</div>

    <div v-else-if="settings.length === 0" class="cache-empty">
      <p>No hay configuraciones de caché registradas.</p>
    </div>

    <div v-else class="cache-grid">
      <div v-for="setting in settings" :key="setting.id" class="cache-card">
        <div class="cache-card__header">
          <h3 class="cache-card__tag">{{ setting.tag }}</h3>
          <span class="cache-card__ttl-display">{{ formatTtl(setting.ttl_seconds) }}</span>
        </div>

        <div class="cache-card__body">
          <label class="cache-card__label">TTL (segundos)</label>
          <div class="cache-card__ttl-row">
            <input
              v-model.number="setting.ttl_seconds"
              type="number"
              min="0"
              step="60"
              class="cache-card__input"
            />
            <button class="cache-card__save" @click="updateTtl(setting)">Guardar</button>
          </div>

          <div class="cache-card__meta">
            <span>Última limpieza: {{ formatDate(setting.last_flushed_at) }}</span>
          </div>
        </div>

        <div class="cache-card__footer">
          <button
            class="cache-card__flush"
            :disabled="flushing === setting.tag"
            @click="flushTag(setting.tag)"
          >
            {{ flushing === setting.tag ? 'Limpiando...' : 'Limpiar Caché' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-page__header { margin-bottom: 1.5rem; }
.admin-page__title { margin: 0; font-size: 1.5rem; font-weight: 600; }
.admin-page__subtitle { margin: 0.25rem 0 0; font-size: 0.875rem; color: var(--admin-text-secondary); }

.cache-loading, .cache-empty { text-align: center; padding: 3rem; color: var(--admin-text-muted); background: var(--admin-surface); border-radius: var(--admin-radius); border: 1px solid var(--admin-border); }

.cache-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.25rem; }

.cache-card { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); overflow: hidden; }
.cache-card__header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid var(--admin-border); background: #fafbfc; }
.cache-card__tag { margin: 0; font-size: 1rem; font-weight: 600; text-transform: capitalize; }
.cache-card__ttl-display { font-size: 0.8125rem; font-weight: 500; color: var(--admin-primary); background: var(--admin-primary-light); padding: 0.2rem 0.625rem; border-radius: 9999px; }

.cache-card__body { padding: 1rem 1.25rem; }
.cache-card__label { display: block; font-size: 0.8125rem; font-weight: 500; color: var(--admin-text-secondary); margin-bottom: 0.375rem; }
.cache-card__ttl-row { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; }
.cache-card__input { flex: 1; padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: var(--admin-radius-sm); font-size: 0.875rem; font-family: var(--admin-font); outline: none; }
.cache-card__input:focus { border-color: var(--admin-primary); }
.cache-card__save { padding: 0.5rem 1rem; border: none; border-radius: var(--admin-radius-sm); background: var(--admin-primary); color: #fff; font-size: 0.8125rem; font-weight: 500; cursor: pointer; font-family: var(--admin-font); transition: opacity 0.15s; }
.cache-card__save:hover { opacity: 0.9; }
.cache-card__meta { font-size: 0.75rem; color: var(--admin-text-muted); }

.cache-card__footer { padding: 0.75rem 1.25rem; border-top: 1px solid var(--admin-border); }
.cache-card__flush {
  width: 100%; padding: 0.5rem; border: 1px solid var(--admin-error); border-radius: var(--admin-radius-sm);
  background: transparent; color: var(--admin-error); font-size: 0.8125rem; font-weight: 500;
  cursor: pointer; font-family: var(--admin-font); transition: all 0.15s;
}
.cache-card__flush:hover:not(:disabled) { background: var(--admin-error); color: #fff; }
.cache-card__flush:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
