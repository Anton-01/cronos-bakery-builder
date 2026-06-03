<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminPanelService, type Theme, type CmsBanner } from '../services/adminPanelService'

const themes = ref<Theme[]>([])
const banners = ref<CmsBanner[]>([])
const loadingThemes = ref(true)
const loadingBanners = ref(true)

// Per-theme editable settings JSON
const settingsJson = ref<Record<string, string>>({})
const savingTheme = ref<Record<string, boolean>>({})

async function loadThemes(): Promise<void> {
  loadingThemes.value = true
  try {
    themes.value = await adminPanelService.themes()
    for (const theme of themes.value) {
      settingsJson.value[theme.id] = JSON.stringify(theme.settings, null, 2)
    }
  } finally {
    loadingThemes.value = false
  }
}

async function loadBanners(): Promise<void> {
  loadingBanners.value = true
  try {
    banners.value = await adminPanelService.banners()
  } finally {
    loadingBanners.value = false
  }
}

async function activateTheme(id: string): Promise<void> {
  savingTheme.value[id] = true
  try {
    await adminPanelService.updateTheme(id, { is_active: true })
    await loadThemes()
  } finally {
    savingTheme.value[id] = false
  }
}

async function saveSettings(theme: Theme): Promise<void> {
  savingTheme.value[theme.id] = true
  try {
    let parsed: Record<string, unknown>
    try {
      parsed = JSON.parse(settingsJson.value[theme.id] ?? '{}')
    } catch {
      alert('JSON inválido. Revisa el formato antes de guardar.')
      return
    }
    await adminPanelService.updateTheme(theme.id, { settings: parsed })
    await loadThemes()
  } finally {
    savingTheme.value[theme.id] = false
  }
}

onMounted(() => {
  loadThemes()
  loadBanners()
})
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Theme Builder</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Contenido <span>/</span> Theme Builder
        </div>
      </div>
    </div>

    <!-- Temas -->
    <h2 class="admin-section-title">Temas</h2>

    <p v-if="loadingThemes" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
      Cargando temas...
    </p>

    <template v-else>
      <p v-if="themes.length === 0" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
        No hay temas registrados.
      </p>

      <div
        v-for="theme in themes"
        :key="theme.id"
        class="admin-content-card admin-theme-card"
      >
        <div class="admin-content-card__header">
          <div class="admin-theme-header">
            <h3 class="admin-content-card__title">{{ theme.name }}</h3>
            <span
              class="admin-badge"
              :class="theme.is_active ? 'admin-badge--success' : 'admin-badge--default'"
            >
              {{ theme.is_active ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
        </div>
        <div class="admin-content-card__body">
          <!-- Settings display -->
          <dl class="admin-settings-list">
            <template v-for="(val, key) in theme.settings" :key="key">
              <dt>{{ key }}</dt>
              <dd>{{ JSON.stringify(val) }}</dd>
            </template>
          </dl>

          <!-- Editable settings -->
          <div style="margin-top: 1rem;">
            <label style="font-size: 0.8rem; font-weight: 500; color: var(--admin-text-muted);">
              Configuración (JSON)
            </label>
            <textarea
              v-model="settingsJson[theme.id]"
              rows="6"
              style="width: 100%; margin-top: 0.25rem; padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 6px; font-size: 0.8rem; font-family: monospace; resize: vertical;"
            />
          </div>

          <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; flex-wrap: wrap;">
            <button
              class="admin-btn admin-btn--sm admin-btn--primary"
              :disabled="savingTheme[theme.id]"
              @click="saveSettings(theme)"
            >
              {{ savingTheme[theme.id] ? 'Guardando...' : 'Guardar configuración' }}
            </button>
            <button
              v-if="!theme.is_active"
              class="admin-btn admin-btn--sm admin-btn--primary"
              :disabled="savingTheme[theme.id]"
              @click="activateTheme(theme.id)"
            >
              Activar
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- Banners -->
    <h2 class="admin-section-title">Banners</h2>

    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Gestión de Banners</h3>
      </div>
      <div class="admin-content-card__body">
        <p v-if="loadingBanners" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
          Cargando banners...
        </p>

        <template v-else>
          <p v-if="banners.length === 0" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
            No hay banners registrados.
          </p>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Ubicacion</th>
                <th>Titulo</th>
                <th>URL</th>
                <th>Imagen</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="banner in banners" :key="banner.id">
                <td>{{ banner.placement }}</td>
                <td>{{ banner.title }}</td>
                <td>
                  <a v-if="banner.url" :href="banner.url" target="_blank" rel="noopener noreferrer">
                    {{ banner.url }}
                  </a>
                  <span v-else style="color: var(--admin-text-muted);">—</span>
                </td>
                <td>
                  <img
                    v-if="banner.image"
                    :src="banner.image"
                    alt="Banner"
                    class="admin-banner-thumb"
                  />
                  <span v-else style="color: var(--admin-text-muted);">Sin imagen</span>
                </td>
                <td>
                  <span
                    class="admin-badge"
                    :class="banner.is_active ? 'admin-badge--success' : 'admin-badge--default'"
                  >
                    {{ banner.is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td>
                  <button class="admin-btn admin-btn--sm">Editar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-section-title { font-size: 1.1rem; font-weight: 600; margin: 1.5rem 0 1rem; color: var(--admin-text); }
.admin-theme-card { margin-bottom: 1rem; }
.admin-theme-header { display: flex; justify-content: space-between; align-items: center; }
.admin-settings-list { font-size: 0.85rem; }
.admin-settings-list dt { font-weight: 500; color: var(--admin-text-muted); margin-top: 0.5rem; }
.admin-settings-list dd { margin-left: 0; }
.admin-banner-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
</style>
