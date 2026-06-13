<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminPanelService, type CmsPage } from '../services/adminPanelService'
import { cmsContentService } from '@/modules/cms/services/cmsContentService'
import { useToast } from '@/composables/useToast'
import { useSudo } from '@/composables/useSudo'
import type { ContentVersion } from '@/modules/cms/types'
import DiffViewer from '@/modules/cms/components/DiffViewer.vue'

const { success, error } = useToast()
const { withSudo } = useSudo()

const pages = ref<CmsPage[]>([])
const selectedPageId = ref<string | null>(null)
const versions = ref<ContentVersion[]>([])
const loading = ref(false)
const versionsLoading = ref(false)
const selectedVersion = ref<ContentVersion | null>(null)

async function loadPages() {
  loading.value = true
  try {
    pages.value = await adminPanelService.cmsPages()
  } finally {
    loading.value = false
  }
}

async function loadVersions(pageId: string) {
  selectedPageId.value = pageId
  versionsLoading.value = true
  selectedVersion.value = null
  try {
    versions.value = await cmsContentService.versions(pageId)
  } finally {
    versionsLoading.value = false
  }
}

async function rollback(version: ContentVersion) {
  if (!selectedPageId.value) return
  try {
    await withSudo(async () => {
      await cmsContentService.rollback(selectedPageId.value!, version.id)
      success(`Rollback a versión #${version.version_number} exitoso`)
      await loadVersions(selectedPageId.value!)
    })
  } catch (_e) {
    error('Error al ejecutar rollback')
  }
}

function formatDate(d: string): string {
  return new Date(d).toLocaleString('es-ES', { dateStyle: 'medium', timeStyle: 'short' })
}

onMounted(loadPages)
</script>

<template>
  <div class="admin-page">
    <div class="admin-page__header">
      <div>
        <h1 class="admin-page__title">Historial de Versiones</h1>
        <p class="admin-page__subtitle">Auditoría y rollback de contenido CMS</p>
      </div>
    </div>

    <div class="versions-layout">
      <!-- Page selector -->
      <div class="versions-panel">
        <h3 class="versions-panel__title">Páginas</h3>
        <div v-if="loading" class="versions-panel__loading">Cargando...</div>
        <div v-else class="versions-panel__list">
          <button
            v-for="page in pages" :key="page.id"
            class="versions-panel__item"
            :class="{ 'versions-panel__item--active': selectedPageId === page.id }"
            @click="loadVersions(page.id)"
          >
            <span class="versions-panel__item-title">{{ page.title }}</span>
            <span class="versions-panel__item-slug">/{{ page.slug }}</span>
          </button>
        </div>
      </div>

      <!-- Version list + diff -->
      <div class="versions-content">
        <div v-if="!selectedPageId" class="versions-empty">
          <p>Selecciona una página para ver su historial de versiones.</p>
        </div>

        <div v-else-if="versionsLoading" class="versions-empty">
          <p>Cargando versiones...</p>
        </div>

        <div v-else-if="versions.length === 0" class="versions-empty">
          <p>Esta página no tiene versiones registradas.</p>
        </div>

        <template v-else>
          <!-- Version timeline -->
          <div class="versions-timeline">
            <button
              v-for="v in versions" :key="v.id"
              class="versions-timeline__item"
              :class="{ 'versions-timeline__item--active': selectedVersion?.id === v.id }"
              @click="selectedVersion = v"
            >
              <div class="versions-timeline__dot"></div>
              <div class="versions-timeline__info">
                <span class="versions-timeline__version">v{{ v.version_number }}</span>
                <span class="versions-timeline__summary">{{ v.change_summary ?? 'Sin descripción' }}</span>
                <span class="versions-timeline__date">{{ formatDate(v.created_at) }}</span>
                <span class="versions-timeline__status">{{ v.status_before ?? '—' }} → {{ v.status_after }}</span>
              </div>
              <button
                class="versions-timeline__rollback"
                title="Rollback a esta versión"
                @click.stop="rollback(v)"
              >
                Rollback
              </button>
            </button>
          </div>

          <!-- Diff viewer -->
          <div v-if="selectedVersion" class="versions-diff">
            <DiffViewer
              :before="selectedVersion.payload_before"
              :after="selectedVersion.payload_after"
              :title="`Versión #${selectedVersion.version_number}`"
            />
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-page__header { margin-bottom: 1.5rem; }
.admin-page__title { margin: 0; font-size: 1.5rem; font-weight: 600; }
.admin-page__subtitle { margin: 0.25rem 0 0; font-size: 0.875rem; color: var(--admin-text-secondary); }

.versions-layout { display: grid; grid-template-columns: 280px 1fr; gap: 1.5rem; align-items: start; }
@media (max-width: 768px) { .versions-layout { grid-template-columns: 1fr; } }

.versions-panel { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); overflow: hidden; }
.versions-panel__title { margin: 0; padding: 1rem 1.25rem; font-size: 0.875rem; font-weight: 600; border-bottom: 1px solid var(--admin-border); }
.versions-panel__loading { padding: 2rem; text-align: center; color: var(--admin-text-muted); font-size: 0.875rem; }
.versions-panel__list { max-height: 500px; overflow-y: auto; }
.versions-panel__item {
  display: block; width: 100%; padding: 0.75rem 1.25rem; border: none; background: none;
  text-align: left; cursor: pointer; border-bottom: 1px solid var(--admin-border);
  font-family: var(--admin-font); transition: background 0.15s;
}
.versions-panel__item:hover { background: var(--admin-primary-light); }
.versions-panel__item--active { background: var(--admin-primary-light); border-left: 3px solid var(--admin-primary); }
.versions-panel__item-title { display: block; font-size: 0.875rem; font-weight: 500; }
.versions-panel__item-slug { font-size: 0.75rem; color: var(--admin-text-muted); }

.versions-content { min-height: 300px; }
.versions-empty { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); padding: 3rem; text-align: center; color: var(--admin-text-muted); }

.versions-timeline { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); overflow: hidden; margin-bottom: 1.5rem; }
.versions-timeline__item {
  display: flex; align-items: center; gap: 1rem; width: 100%; padding: 0.875rem 1.25rem;
  border: none; background: none; text-align: left; cursor: pointer;
  border-bottom: 1px solid var(--admin-border); font-family: var(--admin-font); transition: background 0.15s;
}
.versions-timeline__item:last-child { border-bottom: none; }
.versions-timeline__item:hover { background: #fafbfc; }
.versions-timeline__item--active { background: var(--admin-primary-light); }
.versions-timeline__dot { width: 10px; height: 10px; border-radius: 50%; background: var(--admin-border); flex-shrink: 0; }
.versions-timeline__item--active .versions-timeline__dot { background: var(--admin-primary); }
.versions-timeline__info { flex: 1; min-width: 0; }
.versions-timeline__version { font-weight: 600; font-size: 0.875rem; margin-right: 0.5rem; }
.versions-timeline__summary { font-size: 0.8125rem; color: var(--admin-text-secondary); }
.versions-timeline__date { display: block; font-size: 0.75rem; color: var(--admin-text-muted); margin-top: 0.15rem; }
.versions-timeline__status { display: block; font-size: 0.6875rem; color: var(--admin-text-muted); font-style: italic; }
.versions-timeline__rollback {
  padding: 0.35rem 0.75rem; border: 1px solid var(--admin-border); border-radius: var(--admin-radius-sm);
  background: var(--admin-surface); font-size: 0.75rem; font-weight: 500; cursor: pointer;
  font-family: var(--admin-font); color: var(--admin-text); transition: all 0.15s; flex-shrink: 0;
}
.versions-timeline__rollback:hover { border-color: var(--admin-primary); color: var(--admin-primary); background: var(--admin-primary-light); }
</style>
