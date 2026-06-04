<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import {
  adminPanelService,
  type OptionTemplate,
  type OptionTemplateValue,
  type PbOptionType,
} from '../services/adminPanelService'

const { success, error } = useToast()
const {
  visible: confirmVisible, title: confirmTitle, message: confirmMessage,
  action: confirmAction, confirmText, cancelText,
  confirm, handleConfirm, handleCancel,
} = useConfirm()

const templates = ref<OptionTemplate[]>([])
const loading = ref(true)
const expandedId = ref<string | null>(null)

const optionTypes: { value: PbOptionType; label: string; desc: string }[] = [
  { value: 'select', label: 'Selector', desc: 'Desplegable con opciones' },
  { value: 'radio', label: 'Radio', desc: 'Botones de opción única' },
  { value: 'checkbox', label: 'Checkbox', desc: 'Selección múltiple' },
  { value: 'color', label: 'Color', desc: 'Muestra de color con nombre' },
  { value: 'image', label: 'Imagen', desc: 'Selección visual con imágenes' },
  { value: 'text', label: 'Texto', desc: 'Campo de texto libre' },
  { value: 'textarea', label: 'Área de texto', desc: 'Campo de texto extenso' },
]

const hasValues = (type: PbOptionType) => ['select', 'radio', 'checkbox', 'color', 'image'].includes(type)

// Template form
const showTemplateForm = ref(false)
const editingTemplateId = ref<string | null>(null)
const tplForm = reactive({ key: '', label: '', type: 'select' as PbOptionType, help_text: '', is_required: false })

// Value form
const showValueForm = ref(false)
const editingValueId = ref<string | null>(null)
const valueTemplateId = ref('')
const valueTemplateType = ref<PbOptionType>('select')
const valForm = reactive({
  label: '', value: '',
  price_modifier_type: 'none' as 'none' | 'add' | 'subtract' | 'set',
  price_modifier_amount: 0, is_default: false,
  colorHex: '#000000',
  metadata: null as Record<string, unknown> | null,
})

function genKey(label: string): string {
  return label.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/(^_|_$)/g, '')
}

async function load() {
  loading.value = true
  try { templates.value = await adminPanelService.optionTemplates() } finally { loading.value = false }
}

function toggle(id: string) { expandedId.value = expandedId.value === id ? null : id }

// Template CRUD
function openNewTemplate() {
  editingTemplateId.value = null
  tplForm.key = ''; tplForm.label = ''; tplForm.type = 'select'; tplForm.help_text = ''; tplForm.is_required = false
  showTemplateForm.value = true
}

function openEditTemplate(t: OptionTemplate) {
  editingTemplateId.value = t.id
  tplForm.key = t.key; tplForm.label = t.label; tplForm.type = t.type; tplForm.help_text = t.help_text ?? ''; tplForm.is_required = t.is_required
  showTemplateForm.value = true
}

function onTplLabelInput() { if (!editingTemplateId.value) tplForm.key = genKey(tplForm.label) }

async function submitTemplate() {
  try {
    const payload = { key: tplForm.key, label: tplForm.label, type: tplForm.type, help_text: tplForm.help_text || null, is_required: tplForm.is_required }
    if (editingTemplateId.value) {
      await adminPanelService.updateOptionTemplate(editingTemplateId.value, payload)
      success('Opción actualizada')
    } else {
      await adminPanelService.createOptionTemplate(payload)
      success('Opción creada')
    }
    showTemplateForm.value = false
    await load()
  } catch { error('Error al guardar la opción') }
}

async function deleteTemplate(t: OptionTemplate) {
  const ok = await confirm({
    title: 'Eliminar opción',
    message: `Se eliminará "${t.label}" y todos sus valores. Si algún producto la tiene vinculada, dejará de estar disponible para el usuario.`,
    action: 'delete', confirmText: 'Eliminar',
  })
  if (!ok) return
  try { await adminPanelService.deleteOptionTemplate(t.id); success('Opción eliminada'); await load() }
  catch { error('Error al eliminar la opción') }
}

// Value CRUD
function openNewValue(tplId: string, tplType: PbOptionType) {
  editingValueId.value = null; valueTemplateId.value = tplId; valueTemplateType.value = tplType
  valForm.label = ''; valForm.value = ''; valForm.price_modifier_type = 'none'; valForm.price_modifier_amount = 0
  valForm.is_default = false; valForm.colorHex = '#000000'; valForm.metadata = tplType === 'color' ? { hex: '#000000' } : null
  showValueForm.value = true
}

function openEditValue(tplId: string, tplType: PbOptionType, v: OptionTemplateValue) {
  editingValueId.value = v.id; valueTemplateId.value = tplId; valueTemplateType.value = tplType
  valForm.label = v.label; valForm.value = v.value
  valForm.price_modifier_type = v.price_modifier_type; valForm.price_modifier_amount = v.price_modifier_amount
  valForm.is_default = v.is_default; valForm.metadata = v.metadata ? { ...v.metadata } : null
  valForm.colorHex = (v.metadata?.hex as string) ?? '#000000'
  showValueForm.value = true
}

function onColorChange() { valForm.metadata = { hex: valForm.colorHex } }

async function submitValue() {
  // Duplicate check
  const tpl = templates.value.find(t => t.id === valueTemplateId.value)
  if (tpl && !editingValueId.value) {
    const exists = tpl.values.some(v => v.value === valForm.value)
    if (exists) { error(`El valor "${valForm.value}" ya existe en esta opción.`); return }
  }
  try {
    const payload: Partial<OptionTemplateValue> = {
      label: valForm.label, value: valForm.value,
      price_modifier_type: valForm.price_modifier_type, price_modifier_amount: valForm.price_modifier_amount,
      is_default: valForm.is_default, metadata: valForm.metadata,
    }
    if (editingValueId.value) {
      await adminPanelService.updateTemplateValue(valueTemplateId.value, editingValueId.value, payload)
      success('Valor actualizado')
    } else {
      await adminPanelService.createTemplateValue(valueTemplateId.value, payload)
      success('Valor agregado')
    }
    showValueForm.value = false; await load()
  } catch { error('Error al guardar el valor') }
}

async function deleteValue(tplId: string, v: OptionTemplateValue) {
  const ok = await confirm({
    title: 'Eliminar valor',
    message: `Se eliminará "${v.label}". Si algún producto tiene seleccionado este valor, ya no será visible para el usuario.`,
    action: 'delete', confirmText: 'Eliminar',
  })
  if (!ok) return
  try { await adminPanelService.deleteTemplateValue(tplId, v.id); success('Valor eliminado'); await load() }
  catch { error('Error al eliminar el valor') }
}

function getTypeInfo(type: PbOptionType) { return optionTypes.find(t => t.value === type) }

function modifierLabel(type: string): string {
  return { none: '', add: '+', subtract: '-', set: '=' }[type] ?? ''
}

onMounted(load)
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Opciones del Sistema</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Catalogo <span>/</span> Opciones
        </div>
      </div>
      <div>
        <button class="admin-btn admin-btn--primary" @click="openNewTemplate">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
          Nueva Opción
        </button>
      </div>
    </div>

    <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">Cargando opciones...</p>

    <p v-else-if="!templates.length" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
      No hay opciones registradas en el sistema. Crea la primera opción para comenzar.
    </p>

    <template v-else>
      <div v-for="tpl in templates" :key="tpl.id" class="admin-content-card opt-card">
        <!-- Header -->
        <div class="admin-content-card__header opt-card__header" @click="toggle(tpl.id)">
          <div class="opt-card__info">
            <h3 class="admin-content-card__title">{{ tpl.label }}</h3>
            <code class="opt-card__key">{{ tpl.key }}</code>
            <span class="admin-badge" :class="tpl.type === 'color' ? 'admin-badge--warning' : 'admin-badge--info'" style="font-size: 0.7rem;">
              {{ getTypeInfo(tpl.type)?.label }}
            </span>
            <span v-if="tpl.is_required" class="admin-badge admin-badge--error" style="font-size: 0.65rem;">Requerido</span>
            <span class="admin-badge admin-badge--default" style="font-size: 0.65rem;">{{ tpl.values.length }} valores</span>
          </div>
          <div class="opt-card__actions">
            <button class="admin-action-btn admin-action-btn--edit" title="Editar" @click.stop="openEditTemplate(tpl)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" /></svg>
            </button>
            <button class="admin-action-btn admin-action-btn--delete" title="Eliminar" @click.stop="deleteTemplate(tpl)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /></svg>
            </button>
            <span class="opt-chevron" :class="{ 'opt-chevron--open': expandedId === tpl.id }">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
            </span>
          </div>
        </div>

        <!-- Body -->
        <div v-if="expandedId === tpl.id" class="admin-content-card__body">
          <p v-if="tpl.help_text" class="opt-help">{{ tpl.help_text }}</p>

          <!-- Rendered preview -->
          <div class="opt-preview">
            <span class="opt-preview__label">Vista previa del renderizado:</span>
            <div class="opt-preview__render">
              <template v-if="tpl.type === 'select' && tpl.values.length">
                <select class="opt-preview__select">
                  <option value="">{{ tpl.label }}...</option>
                  <option v-for="v in tpl.values" :key="v.id">{{ v.label }}</option>
                </select>
              </template>
              <template v-else-if="tpl.type === 'radio' && tpl.values.length">
                <div class="opt-preview__radio-group">
                  <label v-for="v in tpl.values" :key="v.id" class="opt-preview__radio">
                    <input type="radio" :name="`preview-${tpl.id}`" :checked="v.is_default" disabled />
                    <span>{{ v.label }}</span>
                  </label>
                </div>
              </template>
              <template v-else-if="tpl.type === 'checkbox' && tpl.values.length">
                <div class="opt-preview__radio-group">
                  <label v-for="v in tpl.values" :key="v.id" class="opt-preview__radio">
                    <input type="checkbox" :checked="v.is_default" disabled />
                    <span>{{ v.label }}</span>
                  </label>
                </div>
              </template>
              <template v-else-if="tpl.type === 'color' && tpl.values.length">
                <div class="opt-preview__colors">
                  <div v-for="v in tpl.values" :key="v.id" class="opt-preview__color-item">
                    <span class="opt-preview__color-swatch" :style="{ background: (v.metadata?.hex as string) || '#ccc' }"></span>
                    <span class="opt-preview__color-name">{{ v.label }}</span>
                  </div>
                </div>
              </template>
              <template v-else-if="tpl.type === 'text'">
                <input type="text" class="opt-preview__text-input" :placeholder="tpl.label" disabled />
              </template>
              <template v-else-if="tpl.type === 'textarea'">
                <textarea class="opt-preview__textarea" :placeholder="tpl.label" rows="2" disabled></textarea>
              </template>
              <span v-else style="font-size: 0.8rem; color: var(--admin-text-muted);">Agrega valores para ver la vista previa.</span>
            </div>
          </div>

          <!-- Values section -->
          <div v-if="hasValues(tpl.type)" class="opt-values-section">
            <div class="opt-values-header">
              <span class="opt-values-title">Valores configurados</span>
              <button class="admin-btn admin-btn--sm admin-btn--outline" @click="openNewValue(tpl.id, tpl.type)">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                Agregar valor
              </button>
            </div>

            <p v-if="!tpl.values.length" style="font-size: 0.8rem; color: var(--admin-text-muted); text-align: center; padding: 1rem;">
              Sin valores configurados.
            </p>

            <table v-else class="admin-table" style="font-size: 0.82rem;">
              <thead>
                <tr>
                  <th v-if="tpl.type === 'color'" style="width: 36px;"></th>
                  <th>Etiqueta</th>
                  <th>Valor</th>
                  <th>Modificador</th>
                  <th style="width: 60px;">Default</th>
                  <th style="width: 80px;">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="v in tpl.values" :key="v.id">
                  <td v-if="tpl.type === 'color'">
                    <span class="opt-color-dot" :style="{ background: (v.metadata?.hex as string) || '#ccc' }"></span>
                  </td>
                  <td style="font-weight: 500;">{{ v.label }}</td>
                  <td><code>{{ v.value }}</code></td>
                  <td>
                    <span v-if="v.price_modifier_type !== 'none'" style="font-weight: 500; color: var(--admin-primary);">
                      {{ modifierLabel(v.price_modifier_type) }}{{ v.price_modifier_amount }}
                    </span>
                    <span v-else style="color: var(--admin-text-muted);">—</span>
                  </td>
                  <td style="text-align: center;">
                    <span v-if="v.is_default" class="admin-badge admin-badge--success" style="font-size: 0.6rem;">Si</span>
                  </td>
                  <td>
                    <div style="display: flex; gap: 0.2rem;">
                      <button class="admin-action-btn admin-action-btn--edit" @click="openEditValue(tpl.id, tpl.type, v)" style="width: 28px; height: 28px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" /></svg>
                      </button>
                      <button class="admin-action-btn admin-action-btn--delete" @click="deleteValue(tpl.id, v)" style="width: 28px; height: 28px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /></svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>

    <!-- Template form modal -->
    <Teleport to="body">
      <Transition name="confirm-fade">
        <div v-if="showTemplateForm" class="opt-modal-overlay" @click.self="showTemplateForm = false">
          <div class="opt-modal">
            <h3 class="opt-modal__title">{{ editingTemplateId ? 'Editar Opción' : 'Nueva Opción' }}</h3>
            <form @submit.prevent="submitTemplate">
              <div class="opt-modal__field">
                <label>Nombre</label>
                <input v-model="tplForm.label" type="text" required placeholder="Ej: Color, Tamaño, Forma" @input="onTplLabelInput" />
              </div>
              <div class="opt-modal__field">
                <label>Clave única</label>
                <input v-model="tplForm.key" type="text" required placeholder="color" style="font-family: monospace; color: var(--admin-primary);" />
              </div>
              <div class="opt-modal__field">
                <label>Tipo de elemento</label>
                <select v-model="tplForm.type" required>
                  <option v-for="t in optionTypes" :key="t.value" :value="t.value">{{ t.label }} — {{ t.desc }}</option>
                </select>
              </div>
              <div class="opt-modal__field">
                <label>Texto de ayuda (opcional)</label>
                <input v-model="tplForm.help_text" type="text" placeholder="Ej: Selecciona la forma deseada" />
              </div>
              <div class="opt-modal__field">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                  <input v-model="tplForm.is_required" type="checkbox" /> Requerido
                </label>
              </div>
              <div class="opt-modal__actions">
                <button type="button" class="admin-btn admin-btn--outline" @click="showTemplateForm = false">Cancelar</button>
                <button type="submit" class="admin-btn admin-btn--primary">{{ editingTemplateId ? 'Actualizar' : 'Crear' }}</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Value form modal -->
    <Teleport to="body">
      <Transition name="confirm-fade">
        <div v-if="showValueForm" class="opt-modal-overlay" @click.self="showValueForm = false">
          <div class="opt-modal">
            <h3 class="opt-modal__title">{{ editingValueId ? 'Editar Valor' : 'Nuevo Valor' }}</h3>
            <form @submit.prevent="submitValue">
              <div class="opt-modal__field">
                <label>Etiqueta</label>
                <input v-model="valForm.label" type="text" required placeholder="Ej: Rojo, Grande" />
              </div>
              <div class="opt-modal__field">
                <label>Valor (clave interna)</label>
                <input v-model="valForm.value" type="text" required placeholder="rojo" style="font-family: monospace;" />
              </div>
              <div v-if="valueTemplateType === 'color'" class="opt-modal__field">
                <label>Color</label>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <input v-model="valForm.colorHex" type="color" style="width: 40px; height: 36px; border: 1px solid var(--admin-border); border-radius: 6px; cursor: pointer;" @input="onColorChange" />
                  <input v-model="valForm.colorHex" type="text" style="width: 100px; font-family: monospace;" placeholder="#FF0000" @input="onColorChange" />
                  <span class="opt-color-dot" :style="{ background: valForm.colorHex, width: '28px', height: '28px' }"></span>
                </div>
              </div>
              <div class="opt-modal__row">
                <div class="opt-modal__field" style="flex: 1;">
                  <label>Modificador de precio</label>
                  <select v-model="valForm.price_modifier_type">
                    <option value="none">Ninguno</option>
                    <option value="add">Sumar</option>
                    <option value="subtract">Restar</option>
                    <option value="set">Fijar precio</option>
                  </select>
                </div>
                <div v-if="valForm.price_modifier_type !== 'none'" class="opt-modal__field" style="flex: 1;">
                  <label>Monto</label>
                  <input v-model.number="valForm.price_modifier_amount" type="number" min="0" />
                </div>
              </div>
              <div class="opt-modal__field">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                  <input v-model="valForm.is_default" type="checkbox" /> Valor por defecto
                </label>
              </div>
              <div class="opt-modal__actions">
                <button type="button" class="admin-btn admin-btn--outline" @click="showValueForm = false">Cancelar</button>
                <button type="submit" class="admin-btn admin-btn--primary">{{ editingValueId ? 'Actualizar' : 'Agregar' }}</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <ConfirmDialog :visible="confirmVisible" :title="confirmTitle" :message="confirmMessage" :action="confirmAction" :confirm-text="confirmText" :cancel-text="cancelText" @confirm="handleConfirm" @cancel="handleCancel" />
  </div>
</template>

<style scoped>
.opt-card { margin-bottom: 1rem; }
.opt-card__header { cursor: pointer; }
.opt-card__info { display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; flex: 1; }
.opt-card__key { font-size: 0.75rem; background: var(--admin-primary-light); color: var(--admin-primary); padding: 0.1rem 0.4rem; border-radius: 4px; }
.opt-card__actions { display: flex; align-items: center; gap: 0.25rem; }
.opt-chevron { display: inline-flex; transition: transform 0.2s ease; color: var(--admin-text-muted); }
.opt-chevron--open { transform: rotate(180deg); }
.opt-chevron svg { display: block; stroke: currentColor; fill: none; }
.opt-help { font-size: 0.8rem; color: var(--admin-text-muted); margin: 0 0 1rem; }

/* Preview */
.opt-preview { background: var(--admin-bg); border-radius: 8px; padding: 1rem; margin-bottom: 1.25rem; border: 1px solid var(--admin-border); }
.opt-preview__label { display: block; font-size: 0.72rem; font-weight: 600; color: var(--admin-text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem; }
.opt-preview__render { padding: 0.5rem 0; }
.opt-preview__select { width: 100%; max-width: 280px; padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 8px; font-family: var(--admin-font); font-size: 0.85rem; background: var(--admin-surface); color: var(--admin-text); }
.opt-preview__radio-group { display: flex; gap: 1rem; flex-wrap: wrap; }
.opt-preview__radio { display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; color: var(--admin-text); cursor: default; }
.opt-preview__colors { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.opt-preview__color-item { display: flex; align-items: center; gap: 0.4rem; }
.opt-preview__color-swatch { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--admin-border); display: inline-block; }
.opt-preview__color-name { font-size: 0.82rem; color: var(--admin-text-secondary); }
.opt-preview__text-input { width: 100%; max-width: 300px; padding: 0.45rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.85rem; background: var(--admin-surface); color: var(--admin-text-muted); }
.opt-preview__textarea { width: 100%; max-width: 400px; padding: 0.45rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.85rem; background: var(--admin-surface); color: var(--admin-text-muted); resize: none; font-family: var(--admin-font); }

/* Values */
.opt-values-section { border-top: 1px solid var(--admin-border); padding-top: 1rem; }
.opt-values-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.opt-values-title { font-size: 0.85rem; font-weight: 600; color: var(--admin-text); }
.opt-color-dot { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--admin-border); display: inline-block; flex-shrink: 0; }

/* Modals */
.opt-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.35); backdrop-filter: blur(2px); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem; }
.opt-modal { background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); padding: 2rem; max-width: 480px; width: 100%; font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif); }
.opt-modal__title { font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif); font-size: 1.1rem; font-weight: 600; margin: 0 0 1.25rem; color: var(--admin-text); }
.opt-modal__field { margin-bottom: 1rem; }
.opt-modal__field label { display: block; font-size: 0.8rem; font-weight: 500; color: var(--admin-text-secondary); margin-bottom: 0.3rem; }
.opt-modal__field input[type="text"], .opt-modal__field input[type="number"], .opt-modal__field select {
  width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 8px;
  font-family: var(--admin-font); font-size: 0.85rem; color: var(--admin-text); background: var(--admin-surface);
}
.opt-modal__field input:focus, .opt-modal__field select:focus { outline: none; border-color: var(--admin-primary); box-shadow: 0 0 0 3px var(--admin-primary-light); }
.opt-modal__row { display: flex; gap: 1rem; }
.opt-modal__actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }
</style>
