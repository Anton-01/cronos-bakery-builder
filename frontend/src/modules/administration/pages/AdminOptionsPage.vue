<script setup lang="ts">
import { onMounted, reactive, ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import {
  adminPanelService,
  type OptionTemplate,
  type OptionTemplateValue,
  type PbOptionType,
} from '../services/adminPanelService'

const { success, error } = useToast()
const { confirm } = useConfirm()

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

const optionTypeSelectOptions = optionTypes.map((t) => ({ label: `${t.label} — ${t.desc}`, value: t.value }))
const boolOptions = [{ label: 'No — Opcional', value: false }, { label: 'Sí — Obligatorio', value: true }]
const priceModifierOptions = [
  { label: 'Ninguno', value: 'none' },
  { label: 'Sumar', value: 'add' },
  { label: 'Restar', value: 'subtract' },
  { label: 'Fijar precio', value: 'set' },
]
const defaultOptions = [{ label: 'No', value: false }, { label: 'Sí', value: true }]

const hasValues = (type: PbOptionType) => ['select', 'radio', 'checkbox', 'color', 'image'].includes(type)

// Template form
const showTemplateForm = ref(false)
const editingTemplateId = ref<string | null>(null)
const tplForm = reactive({ key: '', label: '', type: 'select' as PbOptionType, is_required: false })

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

function openNewTemplate() {
  editingTemplateId.value = null
  tplForm.key = ''; tplForm.label = ''; tplForm.type = 'select'; tplForm.is_required = false
  showTemplateForm.value = true
}

function openEditTemplate(t: OptionTemplate) {
  editingTemplateId.value = t.id
  tplForm.key = t.key; tplForm.label = t.label; tplForm.type = t.type; tplForm.is_required = t.is_required
  showTemplateForm.value = true
}

function onTplLabelInput() { if (!editingTemplateId.value) tplForm.key = genKey(tplForm.label) }

async function submitTemplate() {
  try {
    const payload = { key: tplForm.key, label: tplForm.label, type: tplForm.type, is_required: tplForm.is_required }
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
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Catalogo <span>/</span> Opciones</div>
      </div>
      <Button label="Nueva Opción" icon="pi pi-plus" @click="openNewTemplate" />
    </div>

    <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <p v-else-if="!templates.length" style="text-align:center; padding:3rem; color:var(--admin-text-muted);">
      No hay opciones registradas en el sistema. Crea la primera opción para comenzar.
    </p>

    <template v-else>
      <Card v-for="tpl in templates" :key="tpl.id" style="margin-bottom:1rem;">
        <template #content>
          <!-- Header row -->
          <div class="opt-card__header" @click="toggle(tpl.id)">
            <div class="opt-card__info">
              <span style="font-weight:600; font-size:0.95rem;">{{ tpl.label }}</span>
              <code class="opt-card__key">{{ tpl.key }}</code>
              <Tag :value="getTypeInfo(tpl.type)?.label ?? tpl.type" severity="info" style="font-size:0.7rem;" />
              <Tag v-if="tpl.is_required" value="Requerido" severity="danger" style="font-size:0.65rem;" />
              <Tag :value="`${tpl.values.length} valores`" severity="secondary" style="font-size:0.65rem;" />
            </div>
            <div style="display:flex; align-items:center; gap:0.25rem;">
              <Button icon="pi pi-pencil" size="small" severity="info" text rounded title="Editar" @click.stop="openEditTemplate(tpl)" />
              <Button icon="pi pi-trash" size="small" severity="danger" text rounded title="Eliminar" @click.stop="deleteTemplate(tpl)" />
              <i class="pi" :class="expandedId === tpl.id ? 'pi-chevron-up' : 'pi-chevron-down'" style="color:var(--admin-text-muted); font-size:0.85rem; margin-left:0.25rem;"></i>
            </div>
          </div>

          <!-- Expanded body -->
          <div v-if="expandedId === tpl.id" style="margin-top:1rem;">
            <p v-if="tpl.help_text" class="opt-help">{{ tpl.help_text }}</p>

            <!-- Preview -->
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
                <span v-else style="font-size:0.8rem; color:var(--admin-text-muted);">Agrega valores para ver la vista previa.</span>
              </div>
            </div>

            <!-- Values section -->
            <div v-if="hasValues(tpl.type)" class="opt-values-section">
              <div class="opt-values-header">
                <span class="opt-values-title">Valores configurados</span>
                <Button label="Agregar valor" icon="pi pi-plus" size="small" severity="secondary" outlined @click="openNewValue(tpl.id, tpl.type)" />
              </div>

              <p v-if="!tpl.values.length" style="font-size:0.8rem; color:var(--admin-text-muted); text-align:center; padding:1rem;">
                Sin valores configurados.
              </p>

              <DataTable v-else :value="tpl.values" class="p-datatable-sm">
                <Column v-if="tpl.type === 'color'" style="width:36px;">
                  <template #body="{ data }">
                    <span class="opt-color-dot" :style="{ background: (data.metadata?.hex as string) || '#ccc' }"></span>
                  </template>
                </Column>
                <Column header="Etiqueta" field="label">
                  <template #body="{ data }"><strong>{{ data.label }}</strong></template>
                </Column>
                <Column header="Valor" field="value">
                  <template #body="{ data }"><code>{{ data.value }}</code></template>
                </Column>
                <Column header="Modificador">
                  <template #body="{ data }">
                    <span v-if="data.price_modifier_type !== 'none'" style="font-weight:500; color:var(--admin-primary);">
                      {{ modifierLabel(data.price_modifier_type) }}{{ data.price_modifier_amount }}
                    </span>
                    <span v-else style="color:var(--admin-text-muted);">—</span>
                  </template>
                </Column>
                <Column header="Default" style="width:80px; text-align:center;">
                  <template #body="{ data }">
                    <Tag v-if="data.is_default" value="Sí" severity="success" style="font-size:0.65rem;" />
                  </template>
                </Column>
                <Column header="Acciones" style="width:90px;">
                  <template #body="{ data }">
                    <div style="display:flex; gap:0.2rem;">
                      <Button icon="pi pi-pencil" size="small" severity="info" text rounded @click="openEditValue(tpl.id, tpl.type, data)" />
                      <Button icon="pi pi-trash" size="small" severity="danger" text rounded @click="deleteValue(tpl.id, data)" />
                    </div>
                  </template>
                </Column>
              </DataTable>
            </div>
          </div>
        </template>
      </Card>
    </template>

    <!-- Template form dialog -->
    <Dialog v-model:visible="showTemplateForm" modal :header="editingTemplateId ? 'Editar Opción' : 'Nueva Opción'" :style="{ width: '480px' }">
      <form @submit.prevent="submitTemplate">
        <div class="opt-field">
          <label>Nombre</label>
          <InputText v-model="tplForm.label" fluid required placeholder="Ej: Color, Tamaño, Forma" @input="onTplLabelInput" />
        </div>
        <div class="opt-field">
          <label>Clave única</label>
          <InputText v-model="tplForm.key" fluid required placeholder="color" style="font-family:monospace; color:var(--admin-primary);" />
        </div>
        <div class="opt-field">
          <label>Tipo de elemento</label>
          <Select v-model="tplForm.type" :options="optionTypeSelectOptions" optionLabel="label" optionValue="value" fluid required />
        </div>
        <div class="opt-field">
          <label>Requerido al comprar</label>
          <Select v-model="tplForm.is_required" :options="boolOptions" optionLabel="label" optionValue="value" fluid />
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined @click="showTemplateForm = false" />
        <Button :label="editingTemplateId ? 'Actualizar' : 'Crear'" @click="submitTemplate" />
      </template>
    </Dialog>

    <!-- Value form dialog -->
    <Dialog v-model:visible="showValueForm" modal :header="editingValueId ? 'Editar Valor' : 'Nuevo Valor'" :style="{ width: '480px' }">
      <form @submit.prevent="submitValue">
        <div class="opt-field">
          <label>Etiqueta</label>
          <InputText v-model="valForm.label" fluid required placeholder="Ej: Rojo, Grande" />
        </div>
        <div class="opt-field">
          <label>Valor (clave interna)</label>
          <InputText v-model="valForm.value" fluid required placeholder="rojo" style="font-family:monospace;" />
        </div>
        <div v-if="valueTemplateType === 'color'" class="opt-field">
          <label>Color</label>
          <div style="display:flex; align-items:center; gap:0.75rem;">
            <input v-model="valForm.colorHex" type="color" style="width:40px; height:36px; border:1px solid var(--admin-border); border-radius:6px; cursor:pointer;" @input="onColorChange" />
            <InputText v-model="valForm.colorHex" placeholder="#FF0000" style="width:120px; font-family:monospace;" @input="onColorChange" />
            <span class="opt-color-dot" :style="{ background: valForm.colorHex, width: '28px', height: '28px' }"></span>
          </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
          <div class="opt-field">
            <label>Modificador de precio</label>
            <Select v-model="valForm.price_modifier_type" :options="priceModifierOptions" optionLabel="label" optionValue="value" fluid />
          </div>
          <div v-if="valForm.price_modifier_type !== 'none'" class="opt-field">
            <label>Monto</label>
            <InputNumber v-model="valForm.price_modifier_amount" fluid :min="0" />
          </div>
        </div>
        <div class="opt-field">
          <label>Valor por defecto</label>
          <Select v-model="valForm.is_default" :options="defaultOptions" optionLabel="label" optionValue="value" fluid />
        </div>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" outlined @click="showValueForm = false" />
        <Button :label="editingValueId ? 'Actualizar' : 'Agregar'" @click="submitValue" />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.opt-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
}
.opt-card__info {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
  flex: 1;
}
.opt-card__key {
  font-size: 0.75rem;
  background: var(--admin-primary-light);
  color: var(--admin-primary);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
}
.opt-help {
  font-size: 0.8rem;
  color: var(--admin-text-muted);
  margin: 0 0 1rem;
}
.opt-preview {
  background: var(--admin-bg);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1.25rem;
  border: 1px solid var(--admin-border);
}
.opt-preview__label {
  display: block;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--admin-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 0.5rem;
}
.opt-preview__render { padding: 0.5rem 0; }
.opt-preview__select { width: 100%; max-width: 280px; padding: 0.5rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.85rem; background: var(--admin-surface); color: var(--admin-text); }
.opt-preview__radio-group { display: flex; gap: 1rem; flex-wrap: wrap; }
.opt-preview__radio { display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; color: var(--admin-text); cursor: default; }
.opt-preview__colors { display: flex; gap: 0.75rem; flex-wrap: wrap; }
.opt-preview__color-item { display: flex; align-items: center; gap: 0.4rem; }
.opt-preview__color-swatch { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--admin-border); display: inline-block; }
.opt-preview__color-name { font-size: 0.82rem; color: var(--admin-text-secondary); }
.opt-preview__text-input { width: 100%; max-width: 300px; padding: 0.45rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.85rem; background: var(--admin-surface); }
.opt-preview__textarea { width: 100%; max-width: 400px; padding: 0.45rem 0.75rem; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 0.85rem; background: var(--admin-surface); resize: none; }
.opt-values-section { border-top: 1px solid var(--admin-border); padding-top: 1rem; }
.opt-values-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.opt-values-title { font-size: 0.85rem; font-weight: 600; color: var(--admin-text); }
.opt-color-dot { width: 20px; height: 20px; border-radius: 50%; border: 2px solid var(--admin-border); display: inline-block; flex-shrink: 0; }
.opt-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.opt-field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
</style>
