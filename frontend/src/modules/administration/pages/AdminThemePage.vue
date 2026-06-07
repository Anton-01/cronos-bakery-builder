<script setup lang="ts">
import { onMounted, ref } from 'vue'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { adminPanelService, type Theme, type CmsBanner } from '../services/adminPanelService'

const { success, error } = useToast()
const {
  visible: confirmVisible,
  title: confirmTitle,
  message: confirmMessage,
  action: confirmAction,
  confirmText,
  cancelText,
  confirm,
  handleConfirm,
  handleCancel,
} = useConfirm()

const themes = ref<Theme[]>([])
const banners = ref<CmsBanner[]>([])
const loadingThemes = ref(true)
const loadingBanners = ref(true)
const savingTheme = ref<Record<string, boolean>>({})

interface StoreSettings {
  currency: string
  currency_symbol: string
  locale: string
  country: string
  tax_rate: number
  tax_name: string
  timezone: string
}

const settingsForms = ref<Record<string, StoreSettings>>({})

async function loadThemes(): Promise<void> {
  loadingThemes.value = true
  try {
    themes.value = await adminPanelService.themes()
    for (const theme of themes.value) {
      const s = theme.settings as Partial<StoreSettings> | null
      settingsForms.value[theme.id] = {
        currency: s?.currency ?? 'MXN',
        currency_symbol: s?.currency_symbol ?? '$',
        locale: s?.locale ?? 'es-MX',
        country: s?.country ?? 'MX',
        tax_rate: s?.tax_rate ?? 16,
        tax_name: s?.tax_name ?? 'IVA',
        timezone: s?.timezone ?? 'America/Mexico_City',
      }
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
  const ok = await confirm({
    title: '¿Activar este tema?',
    message: 'El tema seleccionado se aplicará a toda la tienda.',
    action: 'activate',
    confirmText: 'Activar',
  })
  if (!ok) return

  savingTheme.value[id] = true
  try {
    await adminPanelService.updateTheme(id, { is_active: true })
    await loadThemes()
    success('Tema activado exitosamente')
  } catch {
    error('Error al activar el tema')
  } finally {
    savingTheme.value[id] = false
  }
}

function onCurrencyChange(themeId: string) {
  const form = settingsForms.value[themeId]
  if (form.currency === 'MXN') form.currency_symbol = '$'
  else if (form.currency === 'USD') form.currency_symbol = 'US$'
}

async function saveSettings(theme: Theme): Promise<void> {
  savingTheme.value[theme.id] = true
  try {
    const form = settingsForms.value[theme.id]
    const merged = { ...theme.settings, ...form }
    await adminPanelService.updateTheme(theme.id, { settings: merged })
    await loadThemes()
    success('Configuración guardada')
  } catch {
    error('Error al guardar la configuración')
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
          <!-- Structured settings form -->
          <div v-if="settingsForms[theme.id]" class="theme-settings-grid">
            <div class="theme-settings-field">
              <label class="theme-settings-label">Moneda</label>
              <select
                v-model="settingsForms[theme.id].currency"
                class="theme-settings-select"
                @change="onCurrencyChange(theme.id)"
              >
                <option value="MXN">MXN — Peso Mexicano</option>
                <option value="USD">USD — Dólar Estadounidense</option>
              </select>
            </div>
            <div class="theme-settings-field">
              <label class="theme-settings-label">Símbolo</label>
              <input v-model="settingsForms[theme.id].currency_symbol" class="theme-settings-input" />
            </div>
            <div class="theme-settings-field">
              <label class="theme-settings-label">Idioma / Locale</label>
              <select v-model="settingsForms[theme.id].locale" class="theme-settings-select">
                <option value="es-MX">es-MX — Español (México)</option>
                <option value="es-CR">es-CR — Español (Costa Rica)</option>
                <option value="en-US">en-US — English (US)</option>
              </select>
            </div>
            <div class="theme-settings-field">
              <label class="theme-settings-label">País</label>
              <select v-model="settingsForms[theme.id].country" class="theme-settings-select">
                <option value="MX">México</option>
                <option value="CR">Costa Rica</option>
                <option value="US">Estados Unidos</option>
              </select>
            </div>
            <div class="theme-settings-field">
              <label class="theme-settings-label">Impuesto (%)</label>
              <input v-model.number="settingsForms[theme.id].tax_rate" type="number" min="0" max="100" class="theme-settings-input" />
            </div>
            <div class="theme-settings-field">
              <label class="theme-settings-label">Nombre del impuesto</label>
              <input v-model="settingsForms[theme.id].tax_name" class="theme-settings-input" placeholder="IVA" />
            </div>
            <div class="theme-settings-field">
              <label class="theme-settings-label">Zona horaria</label>
              <select v-model="settingsForms[theme.id].timezone" class="theme-settings-select">
                <option value="America/Mexico_City">America/Mexico_City</option>
                <option value="America/Cancun">America/Cancun</option>
                <option value="America/Tijuana">America/Tijuana</option>
                <option value="America/Costa_Rica">America/Costa_Rica</option>
                <option value="America/New_York">America/New_York</option>
                <option value="America/Los_Angeles">America/Los_Angeles</option>
              </select>
            </div>
          </div>

          <div style="display: flex; gap: 0.5rem; margin-top: 1.25rem; flex-wrap: wrap;">
            <button
              class="admin-btn admin-btn--sm admin-btn--primary"
              :disabled="savingTheme[theme.id]"
              @click="saveSettings(theme)"
            >
              {{ savingTheme[theme.id] ? 'Guardando...' : 'Guardar configuración' }}
            </button>
            <button
              v-if="!theme.is_active"
              class="admin-btn admin-btn--sm admin-btn--success"
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
                <th>Ubicación</th>
                <th>Título</th>
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
                  <img v-if="banner.image" :src="banner.image" alt="Banner" class="admin-banner-thumb" />
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
                  <button class="admin-action-btn admin-action-btn--edit" title="Editar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
      </div>
    </div>

    <!-- Confirm dialog -->
    <ConfirmDialog
      :visible="confirmVisible"
      :title="confirmTitle"
      :message="confirmMessage"
      :action="confirmAction"
      :confirm-text="confirmText"
      :cancel-text="cancelText"
      @confirm="handleConfirm"
      @cancel="handleCancel"
    />
  </div>
</template>

<style scoped>
.admin-section-title { font-size: 1.1rem; font-weight: 600; margin: 1.5rem 0 1rem; color: var(--admin-text); }
.admin-theme-card { margin-bottom: 1rem; }
.admin-theme-header { display: flex; justify-content: space-between; align-items: center; width: 100%; }
.admin-banner-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }

.theme-settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}

.theme-settings-field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.theme-settings-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
}

.theme-settings-input,
.theme-settings-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  font-family: var(--admin-font);
  font-size: 0.85rem;
  color: var(--admin-text);
  background: var(--admin-surface);
  transition: border-color 0.15s ease;
}

.theme-settings-input:focus,
.theme-settings-select:focus {
  outline: none;
  border-color: var(--admin-primary);
  box-shadow: 0 0 0 3px var(--admin-primary-light);
}
</style>
