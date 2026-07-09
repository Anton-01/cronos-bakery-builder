<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import Button from 'primevue/button'
import Card from 'primevue/card'
import ColorPicker from 'primevue/colorpicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import ProgressSpinner from 'primevue/progressspinner'
import Select from 'primevue/select'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'

import Column from 'primevue/column'
import DataTable from 'primevue/datatable'

import MediaLibrary from '@/components/MediaLibrary.vue'
import type { MediaAsset } from '@/services/mediaLibrary'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { adminPanelService, type CmsBanner, type Theme } from '../services/adminPanelService'

/**
 * Theme Builder PRO — personalización completa del storefront por pestañas:
 * Branding (paleta + logos vía Media Library), Tipografía, Layout, Código
 * (scripts GA/Pixels) y Tienda. Cada dominio persiste en su columna JSONB.
 */
const { success, error } = useToast()
const { confirm } = useConfirm()

const themes = ref<Theme[]>([])
const banners = ref<CmsBanner[]>([])
const loading = ref(true)
const saving = ref(false)
const selectedThemeId = ref<number | null>(null)

const selectedTheme = computed(() =>
  themes.value.find((t) => t.id === selectedThemeId.value) ?? null,
)

// --- Formularios por pestaña (se hidratan al seleccionar tema) -------------
const branding = reactive({
  name: '',
  logo: null as string | null,
  favicon: null as string | null,
  primary: '#b8693d',
  secondary: '#2c2420',
  accent: '#e0a458',
  background: '#ffffff',
  surface: '#fdf5f0',
  text: '#4a4a4a',
})

const typography = reactive({
  heading_font: 'Playfair Display',
  body_font: 'Inter',
  heading_weight: '600',
  body_weight: '400',
  base_font_size: 14,
})

const layout = reactive({
  header_sticky: true,
  footer_expanded: true,
  container_width: 'boxed' as 'boxed' | 'wide' | 'full',
  show_breadcrumbs: true,
  product_grid_columns: 3,
})

const scripts = reactive({
  head: '',
  body_start: '',
  body_end: '',
})

const store = reactive({
  currency: 'MXN',
  currency_symbol: '$',
  locale: 'es-MX',
  country: 'MX',
  tax_rate: 16,
  tax_name: 'IVA',
  timezone: 'America/Mexico_City',
})

// --- Catálogos --------------------------------------------------------------
const fontOptions = [
  'Playfair Display', 'Cormorant Garamond', 'Lora', 'Merriweather', 'DM Serif Display',
  'Inter', 'Josefin Sans', 'Poppins', 'Nunito', 'Montserrat', 'Roboto', 'Open Sans',
  'Plus Jakarta Sans', 'Dancing Script', 'Pacifico',
].map((f) => ({ label: f, value: f }))

const weightOptions = ['300', '400', '500', '600', '700', '800'].map((w) => ({ label: w, value: w }))

const containerOptions = [
  { label: 'Contenido centrado (boxed)', value: 'boxed' },
  { label: 'Amplio (wide)', value: 'wide' },
  { label: 'Ancho completo (full)', value: 'full' },
]

const gridColumnsOptions = [2, 3, 4, 5].map((n) => ({ label: `${n} columnas`, value: n }))

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
  'America/Mexico_City', 'America/Cancun', 'America/Tijuana',
  'America/Costa_Rica', 'America/New_York', 'America/Los_Angeles',
].map((tz) => ({ label: tz, value: tz }))

// --- Media Library (selector de logo/favicon) -------------------------------
const mediaDialog = ref(false)
const mediaTarget = ref<'logo' | 'favicon'>('logo')

function openMediaFor(target: 'logo' | 'favicon') {
  mediaTarget.value = target
  mediaDialog.value = true
}

function onMediaSelect(asset: MediaAsset) {
  if (!asset.url) {
    error('El archivo seleccionado no tiene URL pública')
    return
  }
  branding[mediaTarget.value] = asset.url
  mediaDialog.value = false
}

// --- ColorPicker helpers (PrimeVue devuelve hex SIN "#") --------------------
type BrandingColorKey = 'primary' | 'secondary' | 'accent' | 'background' | 'surface' | 'text'
const colorFields: { key: BrandingColorKey; label: string }[] = [
  { key: 'primary', label: 'Primary' },
  { key: 'secondary', label: 'Secondary' },
  { key: 'accent', label: 'Accent' },
  { key: 'background', label: 'Background' },
  { key: 'surface', label: 'Surface' },
  { key: 'text', label: 'Texto' },
]

function pickerValue(key: BrandingColorKey): string {
  return branding[key].replace(/^#/, '')
}

function onPickerChange(key: BrandingColorKey, value: string) {
  branding[key] = `#${String(value).replace(/^#/, '')}`
}

// --- Hidratación -------------------------------------------------------------
function hydrateForms(theme: Theme) {
  branding.name = theme.name
  branding.logo = theme.logo ?? null
  branding.favicon = theme.favicon ?? null
  const palette = theme.color_palette ?? {}
  branding.primary = palette.primary ?? theme.colors?.primary ?? '#b8693d'
  branding.secondary = palette.secondary ?? theme.colors?.secondary ?? '#2c2420'
  branding.accent = palette.accent ?? theme.colors?.accent ?? '#e0a458'
  branding.background = palette.background ?? '#ffffff'
  branding.surface = palette.surface ?? '#fdf5f0'
  branding.text = palette.text ?? '#4a4a4a'

  const typo = theme.typography_settings ?? {}
  typography.heading_font = typo.heading_font ?? theme.fonts?.heading ?? 'Playfair Display'
  typography.body_font = typo.body_font ?? theme.fonts?.body ?? 'Inter'
  typography.heading_weight = typo.heading_weight ?? '600'
  typography.body_weight = typo.body_weight ?? '400'
  typography.base_font_size = typo.base_font_size ?? 14

  const lay = theme.layout_config ?? {}
  layout.header_sticky = lay.header_sticky ?? true
  layout.footer_expanded = lay.footer_expanded ?? true
  layout.container_width = lay.container_width ?? 'boxed'
  layout.show_breadcrumbs = lay.show_breadcrumbs ?? true
  layout.product_grid_columns = lay.product_grid_columns ?? 3

  const sc = theme.custom_scripts ?? {}
  scripts.head = sc.head ?? ''
  scripts.body_start = sc.body_start ?? ''
  scripts.body_end = sc.body_end ?? ''

  const st = (theme.settings ?? {}) as Partial<typeof store>
  store.currency = st.currency ?? 'MXN'
  store.currency_symbol = st.currency_symbol ?? '$'
  store.locale = st.locale ?? 'es-MX'
  store.country = st.country ?? 'MX'
  store.tax_rate = st.tax_rate ?? 16
  store.tax_name = st.tax_name ?? 'IVA'
  store.timezone = st.timezone ?? 'America/Mexico_City'
}

watch(selectedThemeId, () => {
  if (selectedTheme.value) hydrateForms(selectedTheme.value)
})

// --- Acciones ----------------------------------------------------------------
async function loadThemes() {
  loading.value = true
  try {
    themes.value = await adminPanelService.themes()
    if (!selectedThemeId.value && themes.value.length) {
      selectedThemeId.value = (themes.value.find((t) => t.is_active) ?? themes.value[0]).id
      if (selectedTheme.value) hydrateForms(selectedTheme.value)
    }
  } catch {
    error('Error al cargar los temas')
  } finally {
    loading.value = false
  }
}

async function saveTheme() {
  const theme = selectedTheme.value
  if (!theme) return
  saving.value = true
  try {
    const updated = await adminPanelService.updateTheme(theme.id, {
      name: branding.name,
      logo: branding.logo,
      favicon: branding.favicon,
      color_palette: {
        primary: branding.primary,
        secondary: branding.secondary,
        accent: branding.accent,
        background: branding.background,
        surface: branding.surface,
        text: branding.text,
      },
      typography_settings: {
        heading_font: typography.heading_font,
        body_font: typography.body_font,
        heading_weight: typography.heading_weight,
        body_weight: typography.body_weight,
        base_font_size: typography.base_font_size,
      },
      layout_config: {
        header_sticky: layout.header_sticky,
        footer_expanded: layout.footer_expanded,
        container_width: layout.container_width,
        show_breadcrumbs: layout.show_breadcrumbs,
        product_grid_columns: layout.product_grid_columns,
      },
      custom_scripts: {
        head: scripts.head,
        body_start: scripts.body_start,
        body_end: scripts.body_end,
      },
      // Compatibilidad con el storefront actual (colors/fonts legados).
      fonts: {
        heading: typography.heading_font,
        body: typography.body_font,
      },
      settings: { ...(theme.settings ?? {}), ...store },
    } as Partial<Theme>)
    const idx = themes.value.findIndex((t) => t.id === updated.id)
    if (idx !== -1) themes.value[idx] = updated
    success('Tema guardado correctamente')
  } catch {
    error('Error al guardar el tema')
  } finally {
    saving.value = false
  }
}

async function activateSelected() {
  const theme = selectedTheme.value
  if (!theme || theme.is_active) return
  const ok = await confirm({
    title: '¿Activar este tema?',
    message: `"${theme.name}" se aplicará a toda la tienda.`,
    action: 'activate',
    confirmText: 'Activar',
  })
  if (!ok) return
  try {
    await adminPanelService.activateTheme(theme.id)
    await loadThemes()
    success('Tema activado exitosamente')
  } catch {
    error('Error al activar el tema')
  }
}

async function loadBanners() {
  try {
    banners.value = await adminPanelService.banners()
  } catch {
    banners.value = []
  }
}

onMounted(() => {
  void loadThemes()
  void loadBanners()
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

    <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <template v-else>
      <!-- Selector de tema + acciones globales -->
      <Card style="margin-bottom:1rem;">
        <template #content>
          <div class="theme-toolbar">
            <Select
              v-model="selectedThemeId"
              :options="themes.map(t => ({ label: t.name, value: t.id }))"
              optionLabel="label"
              optionValue="value"
              placeholder="Selecciona un tema"
              style="min-width:240px;"
            />
            <Tag
              v-if="selectedTheme"
              :value="selectedTheme.is_active ? 'Activo' : 'Inactivo'"
              :severity="selectedTheme.is_active ? 'success' : 'secondary'"
            />
            <div style="flex:1;" />
            <Button
              v-if="selectedTheme && !selectedTheme.is_active"
              v-tooltip.top="'Aplicar este tema a toda la tienda'"
              icon="pi pi-check-circle"
              size="small"
              severity="warn"
              text
              rounded
              aria-label="Activar tema"
              @click="activateSelected"
            />
            <Button
              label="Guardar cambios"
              icon="pi pi-save"
              size="small"
              :loading="saving"
              :disabled="!selectedTheme"
              @click="saveTheme"
            />
          </div>
        </template>
      </Card>

      <Card v-if="selectedTheme">
        <template #content>
          <Tabs value="branding">
            <TabList>
              <Tab value="branding"><i class="pi pi-palette" style="margin-right:0.4rem;" />Branding</Tab>
              <Tab value="typography"><i class="pi pi-language" style="margin-right:0.4rem;" />Tipografía</Tab>
              <Tab value="layout"><i class="pi pi-table" style="margin-right:0.4rem;" />Layout</Tab>
              <Tab value="code"><i class="pi pi-code" style="margin-right:0.4rem;" />Código</Tab>
              <Tab value="store"><i class="pi pi-shopping-cart" style="margin-right:0.4rem;" />Tienda</Tab>
              <Tab value="banners"><i class="pi pi-images" style="margin-right:0.4rem;" />Banners</Tab>
            </TabList>
            <TabPanels>
              <!-- ===================== BRANDING ===================== -->
              <TabPanel value="branding">
                <div class="form-field" style="max-width:420px;">
                  <label>Nombre del tema</label>
                  <InputText v-model="branding.name" fluid />
                </div>

                <h3 class="subsection-title">Paleta de colores</h3>
                <div class="color-grid">
                  <div v-for="field in colorFields" :key="field.key" class="color-field">
                    <label>{{ field.label }}</label>
                    <div class="color-field__row">
                      <ColorPicker
                        :modelValue="pickerValue(field.key)"
                        format="hex"
                        @update:modelValue="(v: string) => onPickerChange(field.key, v)"
                      />
                      <InputText
                        :modelValue="branding[field.key]"
                        style="width:110px; font-family:monospace;"
                        @update:modelValue="(v?: string) => onPickerChange(field.key, v ?? '')"
                      />
                      <span class="color-swatch" :style="{ background: branding[field.key] }" />
                    </div>
                  </div>
                </div>

                <h3 class="subsection-title">Identidad</h3>
                <div class="asset-grid">
                  <div class="asset-field">
                    <label>Logo</label>
                    <div class="asset-field__preview">
                      <img v-if="branding.logo" :src="branding.logo" alt="Logo actual">
                      <span v-else>Sin logo</span>
                    </div>
                    <div class="asset-field__actions">
                      <Button
                        v-tooltip.top="'Elegir de la Biblioteca de Medios'"
                        icon="pi pi-images"
                        size="small"
                        severity="info"
                        text
                        rounded
                        aria-label="Elegir logo de la Biblioteca de Medios"
                        @click="openMediaFor('logo')"
                      />
                      <Button
                        v-if="branding.logo"
                        v-tooltip.top="'Quitar logo'"
                        icon="pi pi-times"
                        size="small"
                        severity="danger"
                        text
                        rounded
                        aria-label="Quitar logo"
                        @click="branding.logo = null"
                      />
                    </div>
                  </div>
                  <div class="asset-field">
                    <label>Favicon</label>
                    <div class="asset-field__preview asset-field__preview--small">
                      <img v-if="branding.favicon" :src="branding.favicon" alt="Favicon actual">
                      <span v-else>Sin favicon</span>
                    </div>
                    <div class="asset-field__actions">
                      <Button
                        v-tooltip.top="'Elegir de la Biblioteca de Medios'"
                        icon="pi pi-images"
                        size="small"
                        severity="info"
                        text
                        rounded
                        aria-label="Elegir favicon de la Biblioteca de Medios"
                        @click="openMediaFor('favicon')"
                      />
                      <Button
                        v-if="branding.favicon"
                        v-tooltip.top="'Quitar favicon'"
                        icon="pi pi-times"
                        size="small"
                        severity="danger"
                        text
                        rounded
                        aria-label="Quitar favicon"
                        @click="branding.favicon = null"
                      />
                    </div>
                  </div>
                </div>
              </TabPanel>

              <!-- ===================== TIPOGRAFÍA ===================== -->
              <TabPanel value="typography">
                <div class="settings-grid">
                  <div class="form-field">
                    <label>Fuente de títulos</label>
                    <Select v-model="typography.heading_font" :options="fontOptions" optionLabel="label" optionValue="value" fluid />
                  </div>
                  <div class="form-field">
                    <label>Peso de títulos</label>
                    <Select v-model="typography.heading_weight" :options="weightOptions" optionLabel="label" optionValue="value" fluid />
                  </div>
                  <div class="form-field">
                    <label>Fuente de cuerpo</label>
                    <Select v-model="typography.body_font" :options="fontOptions" optionLabel="label" optionValue="value" fluid />
                  </div>
                  <div class="form-field">
                    <label>Peso de cuerpo</label>
                    <Select v-model="typography.body_weight" :options="weightOptions" optionLabel="label" optionValue="value" fluid />
                  </div>
                  <div class="form-field">
                    <label>Tamaño base (px)</label>
                    <InputNumber v-model="typography.base_font_size" :min="12" :max="24" fluid />
                  </div>
                </div>

                <h3 class="subsection-title">Vista previa</h3>
                <div class="typo-preview">
                  <p :style="{ fontFamily: `'${typography.heading_font}', serif`, fontWeight: typography.heading_weight, fontSize: '1.6rem', margin: 0 }">
                    Pastelería artesanal Cronos
                  </p>
                  <p :style="{ fontFamily: `'${typography.body_font}', sans-serif`, fontWeight: typography.body_weight, fontSize: `${typography.base_font_size}px`, margin: '0.5rem 0 0' }">
                    Cada pastel se hornea el mismo día de la entrega con ingredientes de temporada.
                  </p>
                </div>
              </TabPanel>

              <!-- ===================== LAYOUT ===================== -->
              <TabPanel value="layout">
                <div class="layout-options">
                  <div class="layout-option">
                    <div>
                      <span class="layout-option__title">Header fijo (sticky)</span>
                      <span class="layout-option__desc">La barra de navegación permanece visible al hacer scroll.</span>
                    </div>
                    <ToggleSwitch v-model="layout.header_sticky" v-tooltip.left="'Fijar el header al hacer scroll'" aria-label="Header fijo" />
                  </div>
                  <div class="layout-option">
                    <div>
                      <span class="layout-option__title">Footer expandido</span>
                      <span class="layout-option__desc">Muestra columnas de enlaces y newsletter en el pie de página.</span>
                    </div>
                    <ToggleSwitch v-model="layout.footer_expanded" v-tooltip.left="'Mostrar footer con columnas'" aria-label="Footer expandido" />
                  </div>
                  <div class="layout-option">
                    <div>
                      <span class="layout-option__title">Migas de pan (breadcrumbs)</span>
                      <span class="layout-option__desc">Ruta de navegación visible en páginas interiores.</span>
                    </div>
                    <ToggleSwitch v-model="layout.show_breadcrumbs" v-tooltip.left="'Mostrar breadcrumbs'" aria-label="Mostrar breadcrumbs" />
                  </div>
                  <div class="layout-option">
                    <div>
                      <span class="layout-option__title">Ancho del contenedor</span>
                      <span class="layout-option__desc">Cómo se distribuye el contenido en pantallas grandes.</span>
                    </div>
                    <Select v-model="layout.container_width" :options="containerOptions" optionLabel="label" optionValue="value" style="min-width:230px;" />
                  </div>
                  <div class="layout-option">
                    <div>
                      <span class="layout-option__title">Columnas del catálogo</span>
                      <span class="layout-option__desc">Productos por fila en el grid del catálogo.</span>
                    </div>
                    <Select v-model="layout.product_grid_columns" :options="gridColumnsOptions" optionLabel="label" optionValue="value" style="min-width:230px;" />
                  </div>
                </div>
              </TabPanel>

              <!-- ===================== CÓDIGO ===================== -->
              <TabPanel value="code">
                <p style="font-size:0.82rem; color:var(--admin-text-secondary); margin-top:0;">
                  Inyecta snippets de terceros (Google Analytics, Meta Pixel, chats).
                  Los scripts se insertan tal cual en el storefront — verifica su origen antes de guardar.
                </p>
                <div class="form-field">
                  <label>&lt;head&gt; — etiquetas meta y analytics</label>
                  <Textarea v-model="scripts.head" rows="6" fluid class="code-editor" placeholder="<script async src=&quot;https://www.googletagmanager.com/gtag/js?id=G-XXXX&quot;></script>" />
                </div>
                <div class="form-field">
                  <label>Inicio de &lt;body&gt; — pixels/noscript</label>
                  <Textarea v-model="scripts.body_start" rows="5" fluid class="code-editor" />
                </div>
                <div class="form-field">
                  <label>Fin de &lt;body&gt; — widgets diferidos</label>
                  <Textarea v-model="scripts.body_end" rows="5" fluid class="code-editor" />
                </div>
              </TabPanel>

              <!-- ===================== TIENDA ===================== -->
              <TabPanel value="store">
                <div class="settings-grid">
                  <div class="form-field">
                    <label>Moneda</label>
                    <Select v-model="store.currency" :options="currencyOptions" optionLabel="label" optionValue="value" fluid
                      @change="store.currency_symbol = store.currency === 'USD' ? 'US$' : '$'" />
                  </div>
                  <div class="form-field">
                    <label>Símbolo</label>
                    <InputText v-model="store.currency_symbol" fluid />
                  </div>
                  <div class="form-field">
                    <label>Idioma / Locale</label>
                    <Select v-model="store.locale" :options="localeOptions" optionLabel="label" optionValue="value" fluid />
                  </div>
                  <div class="form-field">
                    <label>País</label>
                    <Select v-model="store.country" :options="countryOptions" optionLabel="label" optionValue="value" fluid />
                  </div>
                  <div class="form-field">
                    <label>Impuesto (%)</label>
                    <InputNumber v-model="store.tax_rate" :min="0" :max="100" fluid />
                  </div>
                  <div class="form-field">
                    <label>Nombre del impuesto</label>
                    <InputText v-model="store.tax_name" placeholder="IVA" fluid />
                  </div>
                  <div class="form-field">
                    <label>Zona horaria</label>
                    <Select v-model="store.timezone" :options="timezoneOptions" optionLabel="label" optionValue="value" fluid />
                  </div>
                </div>
              </TabPanel>
              <!-- ===================== BANNERS ===================== -->
              <TabPanel value="banners">
                <DataTable :value="banners" size="small">
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
                </DataTable>
              </TabPanel>
            </TabPanels>
          </Tabs>
        </template>
      </Card>
    </template>

    <!-- Media Library modal (logo/favicon) -->
    <Dialog
      v-model:visible="mediaDialog"
      :header="mediaTarget === 'logo' ? 'Seleccionar logo' : 'Seleccionar favicon'"
      modal
      :style="{ width: '820px', maxWidth: '95vw' }"
    >
      <MediaLibrary selectable accept="image/" @select="onMediaSelect" />
    </Dialog>
  </div>
</template>

<style scoped>
.theme-toolbar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.subsection-title {
  font-size: 0.95rem;
  font-weight: 600;
  margin: 1.5rem 0 0.75rem;
  color: var(--admin-text);
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  margin-bottom: 0.9rem;
}
.form-field label {
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1rem;
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  gap: 1rem;
}
.color-field label {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
  margin-bottom: 0.3rem;
}
.color-field__row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.color-swatch {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  border: 1px solid var(--admin-border);
  display: inline-block;
}

.asset-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1rem;
}
.asset-field label {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
  margin-bottom: 0.3rem;
}
.asset-field__preview {
  height: 84px;
  border: 1px dashed var(--admin-border);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--admin-bg);
  color: var(--admin-text-muted);
  font-size: 0.8rem;
  overflow: hidden;
}
.asset-field__preview img { max-height: 100%; max-width: 100%; object-fit: contain; }
.asset-field__preview--small img { max-height: 40px; }
.asset-field__actions { display: flex; gap: 0.25rem; margin-top: 0.35rem; }

.typo-preview {
  border: 1px solid var(--admin-border);
  border-radius: 10px;
  padding: 1rem 1.25rem;
  background: #fff;
}

.layout-options { display: flex; flex-direction: column; }
.layout-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.8rem 0;
  border-bottom: 1px solid var(--admin-border);
}
.layout-option:last-child { border-bottom: none; }
.layout-option__title { display: block; font-size: 0.88rem; font-weight: 600; }
.layout-option__desc { display: block; font-size: 0.75rem; color: var(--admin-text-muted); }

.code-editor {
  font-family: 'JetBrains Mono', 'Fira Code', Consolas, Menlo, monospace;
  font-size: 0.8rem;
  line-height: 1.5;
}

.banner-thumb {
  width: 60px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
}
</style>
