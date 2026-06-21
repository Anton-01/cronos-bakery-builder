<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'

import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { adminPanelService, type Theme, type CmsBanner } from '../services/adminPanelService'

const { success, error } = useToast()
const { confirm } = useConfirm()

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

const currencyOptions = [
  { label: 'MXN — Peso Mexicano', value: 'MXN' },
  { label: 'USD — Dólar Estadounidense', value: 'USD' },
]

const localeOptions = [
  { label: 'es-MX — Español (México)', value: 'es-MX' },
  { label: 'es-CR — Español (Costa Rica)', value: 'es-CR' },
  { label: 'en-US — English (US)', value: 'en-US' },
]

const countryOptions = [
  { label: 'México', value: 'MX' },
  { label: 'Costa Rica', value: 'CR' },
  { label: 'Estados Unidos', value: 'US' },
]

const timezoneOptions = [
  { label: 'America/Mexico_City', value: 'America/Mexico_City' },
  { label: 'America/Cancun', value: 'America/Cancun' },
  { label: 'America/Tijuana', value: 'America/Tijuana' },
  { label: 'America/Costa_Rica', value: 'America/Costa_Rica' },
  { label: 'America/New_York', value: 'America/New_York' },
  { label: 'America/Los_Angeles', value: 'America/Los_Angeles' },
]

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
    <div class="admin-page-header">
      <div>
        <h1>Theme Builder</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Contenido <span>/</span> Theme Builder</div>
      </div>
    </div>

    <!-- Temas -->
    <h2 class="section-title">Temas</h2>

    <div v-if="loadingThemes" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <template v-else>
      <p v-if="themes.length === 0" style="text-align:center; padding:2rem; color:var(--admin-text-muted);">
        No hay temas registrados.
      </p>

      <Card v-for="theme in themes" :key="theme.id" style="margin-bottom:1rem;">
        <template #title>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span>{{ theme.name }}</span>
            <Tag :value="theme.is_active ? 'Activo' : 'Inactivo'" :severity="theme.is_active ? 'success' : 'secondary'" />
          </div>
        </template>
        <template #content>
          <div v-if="settingsForms[theme.id]" class="settings-grid">
            <div class="settings-field">
              <label>Moneda</label>
              <Select
                v-model="settingsForms[theme.id].currency"
                :options="currencyOptions"
                optionLabel="label"
                optionValue="value"
                fluid
                @change="onCurrencyChange(theme.id)"
              />
            </div>
            <div class="settings-field">
              <label>Símbolo</label>
              <InputText v-model="settingsForms[theme.id].currency_symbol" fluid />
            </div>
            <div class="settings-field">
              <label>Idioma / Locale</label>
              <Select
                v-model="settingsForms[theme.id].locale"
                :options="localeOptions"
                optionLabel="label"
                optionValue="value"
                fluid
              />
            </div>
            <div class="settings-field">
              <label>País</label>
              <Select
                v-model="settingsForms[theme.id].country"
                :options="countryOptions"
                optionLabel="label"
                optionValue="value"
                fluid
              />
            </div>
            <div class="settings-field">
              <label>Impuesto (%)</label>
              <InputNumber v-model="settingsForms[theme.id].tax_rate" :min="0" :max="100" fluid />
            </div>
            <div class="settings-field">
              <label>Nombre del impuesto</label>
              <InputText v-model="settingsForms[theme.id].tax_name" placeholder="IVA" fluid />
            </div>
            <div class="settings-field">
              <label>Zona horaria</label>
              <Select
                v-model="settingsForms[theme.id].timezone"
                :options="timezoneOptions"
                optionLabel="label"
                optionValue="value"
                fluid
              />
            </div>
          </div>

          <div style="display:flex; gap:0.5rem; margin-top:1.25rem; flex-wrap:wrap;">
            <Button
              :label="savingTheme[theme.id] ? 'Guardando...' : 'Guardar configuración'"
              :loading="savingTheme[theme.id]"
              size="small"
              @click="saveSettings(theme)"
            />
            <Button
              v-if="!theme.is_active"
              label="Activar"
              severity="success"
              size="small"
              :loading="savingTheme[theme.id]"
              @click="activateTheme(theme.id)"
            />
          </div>
        </template>
      </Card>
    </template>

    <!-- Banners -->
    <h2 class="section-title">Banners</h2>

    <Card>
      <template #title>Gestión de Banners</template>
      <template #content>
        <div v-if="loadingBanners" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>

        <DataTable v-else :value="banners" class="p-datatable-sm">
          <template #empty>
            <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">No hay banners registrados.</div>
          </template>

          <Column header="Ubicación" field="placement" style="width:130px;" />

          <Column header="Título" field="title" />

          <Column header="URL">
            <template #body="{ data }">
              <a v-if="data.url" :href="data.url" target="_blank" rel="noopener noreferrer" style="color:var(--admin-primary); font-size:0.82rem;">
                {{ data.url }}
              </a>
              <span v-else style="color:var(--admin-text-muted);">—</span>
            </template>
          </Column>

          <Column header="Imagen" style="width:100px;">
            <template #body="{ data }">
              <img v-if="data.image" :src="data.image" alt="Banner" class="banner-thumb" />
              <span v-else style="color:var(--admin-text-muted);">Sin imagen</span>
            </template>
          </Column>

          <Column header="Estado" style="width:100px;">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Activo' : 'Inactivo'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column header="Acciones" style="width:90px;">
            <template #body>
              <Button icon="pi pi-pencil" size="small" severity="info" text rounded title="Editar" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 1.5rem 0 1rem;
  color: var(--admin-text);
}
.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}
.settings-field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}
.settings-field label {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
}
.banner-thumb {
  width: 60px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
}
</style>
