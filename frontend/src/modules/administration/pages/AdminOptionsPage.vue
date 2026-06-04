<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import {
  adminPanelService,
  type AdminProductDetail,
  type PbOption,
  type PbOptionType,
  type PbOptionValue,
} from '../services/adminPanelService'

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

const products = ref<AdminProductDetail[]>([])
const loading = ref(true)
const expandedProduct = ref<string | null>(null)
const expandedOption = ref<string | null>(null)

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

// Option form
const showOptionForm = ref(false)
const editingOptionId = ref<string | null>(null)
const optionForm = reactive({
  productId: '',
  key: '',
  label: '',
  type: 'select' as PbOptionType,
  help_text: '',
  is_required: false,
})

// Value form
const showValueForm = ref(false)
const editingValueId = ref<string | null>(null)
const valueFormOptionId = ref('')
const valueFormProductId = ref('')
const valueForm = reactive({
  label: '',
  value: '',
  price_modifier_type: 'none' as 'none' | 'add' | 'subtract' | 'set',
  price_modifier_amount: 0,
  metadata: null as Record<string, unknown> | null,
  is_default: false,
  colorHex: '#000000',
})

function generateKey(label: string): string {
  return label.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/(^_|_$)/g, '')
}

async function loadProducts() {
  loading.value = true
  try {
    const list = await adminPanelService.adminProducts()
    const detailed: AdminProductDetail[] = []
    for (const p of list) {
      try {
        const d = await adminPanelService.showProduct(p.id)
        detailed.push(d)
      } catch {
        detailed.push({ ...p, gallery: [], options: [] })
      }
    }
    products.value = detailed
  } finally {
    loading.value = false
  }
}

function toggleProduct(id: string) {
  expandedProduct.value = expandedProduct.value === id ? null : id
}

function toggleOption(id: string) {
  expandedOption.value = expandedOption.value === id ? null : id
}

// Option CRUD
function openNewOption(productId: string) {
  editingOptionId.value = null
  optionForm.productId = productId
  optionForm.key = ''
  optionForm.label = ''
  optionForm.type = 'select'
  optionForm.help_text = ''
  optionForm.is_required = false
  showOptionForm.value = true
}

function openEditOption(productId: string, option: PbOption) {
  editingOptionId.value = option.id
  optionForm.productId = productId
  optionForm.key = option.key
  optionForm.label = option.label
  optionForm.type = option.type
  optionForm.help_text = option.help_text ?? ''
  optionForm.is_required = option.is_required
  showOptionForm.value = true
}

function onOptionLabelInput() {
  if (!editingOptionId.value) {
    optionForm.key = generateKey(optionForm.label)
  }
}

async function submitOption() {
  try {
    const payload = {
      key: optionForm.key,
      label: optionForm.label,
      type: optionForm.type,
      help_text: optionForm.help_text || null,
      is_required: optionForm.is_required,
    }

    if (editingOptionId.value) {
      await adminPanelService.updateOption(optionForm.productId, editingOptionId.value, payload)
      success('Opción actualizada')
    } else {
      await adminPanelService.createOption(optionForm.productId, payload)
      success('Opción creada')
    }
    showOptionForm.value = false
    await loadProducts()
  } catch {
    error('Error al guardar la opción')
  }
}

async function deleteOption(productId: string, option: PbOption) {
  const ok = await confirm({
    title: 'Eliminar opción',
    message: `Se eliminará la opción "${option.label}" y todos sus valores. Esta acción no se puede deshacer.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return

  try {
    await adminPanelService.deleteOption(productId, option.id)
    success('Opción eliminada')
    await loadProducts()
  } catch {
    error('Error al eliminar la opción')
  }
}

// Value CRUD
function openNewValue(productId: string, optionId: string, optionType: PbOptionType) {
  editingValueId.value = null
  valueFormProductId.value = productId
  valueFormOptionId.value = optionId
  valueForm.label = ''
  valueForm.value = ''
  valueForm.price_modifier_type = 'none'
  valueForm.price_modifier_amount = 0
  valueForm.is_default = false
  valueForm.metadata = null
  valueForm.colorHex = '#000000'
  if (optionType === 'color') {
    valueForm.metadata = { hex: '#000000' }
  }
  showValueForm.value = true
}

function openEditValue(productId: string, optionId: string, val: PbOptionValue, optionType: PbOptionType) {
  editingValueId.value = val.id
  valueFormProductId.value = productId
  valueFormOptionId.value = optionId
  valueForm.label = val.label
  valueForm.value = val.value
  valueForm.price_modifier_type = val.price_modifier_type
  valueForm.price_modifier_amount = val.price_modifier_amount
  valueForm.is_default = val.is_default
  valueForm.metadata = val.metadata ? { ...val.metadata } : null
  valueForm.colorHex = (val.metadata?.hex as string) ?? '#000000'
  showValueForm.value = true
}

function onColorChange() {
  valueForm.metadata = { hex: valueForm.colorHex }
}

async function submitValue() {
  try {
    const payload: Partial<PbOptionValue> = {
      label: valueForm.label,
      value: valueForm.value,
      price_modifier_type: valueForm.price_modifier_type,
      price_modifier_amount: valueForm.price_modifier_amount,
      is_default: valueForm.is_default,
      metadata: valueForm.metadata,
    }

    if (editingValueId.value) {
      await adminPanelService.updateOptionValue(valueFormProductId.value, valueFormOptionId.value, editingValueId.value, payload)
      success('Valor actualizado')
    } else {
      await adminPanelService.createOptionValue(valueFormProductId.value, valueFormOptionId.value, payload)
      success('Valor creado')
    }
    showValueForm.value = false
    await loadProducts()
  } catch {
    error('Error al guardar el valor')
  }
}

async function deleteValue(productId: string, optionId: string, val: PbOptionValue) {
  const ok = await confirm({
    title: 'Eliminar valor',
    message: `Se eliminará el valor "${val.label}". Esta acción no se puede deshacer.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return

  try {
    await adminPanelService.deleteOptionValue(productId, optionId, val.id)
    success('Valor eliminado')
    await loadProducts()
  } catch {
    error('Error al eliminar el valor')
  }
}

function getOptionTypeInfo(type: PbOptionType) {
  return optionTypes.find((t) => t.value === type)
}

onMounted(loadProducts)
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Opciones de Producto</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Catalogo <span>/</span> Opciones
        </div>
      </div>
    </div>

    <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
      Cargando opciones...
    </p>

    <template v-else>
      <p v-if="products.length === 0" style="text-align: center; padding: 3rem; color: var(--admin-text-muted);">
        No hay productos registrados. Crea un producto primero.
      </p>

      <!-- Product accordion -->
      <div v-for="product in products" :key="product.id" class="admin-content-card option-product-card">
        <div class="admin-content-card__header option-product-header" @click="toggleProduct(product.id)">
          <div style="display: flex; align-items: center; gap: 0.75rem;">
            <h3 class="admin-content-card__title">{{ product.name }}</h3>
            <span class="admin-badge admin-badge--info">{{ product.options?.length ?? 0 }} opciones</span>
          </div>
          <span class="option-chevron" :class="{ 'option-chevron--open': expandedProduct === product.id }">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
          </span>
        </div>

        <div v-if="expandedProduct === product.id" class="admin-content-card__body">
          <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
            <button class="admin-btn admin-btn--primary admin-btn--sm" @click="openNewOption(product.id)">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
              Nueva Opción
            </button>
          </div>

          <p v-if="!product.options?.length" style="text-align: center; padding: 1.5rem; color: var(--admin-text-muted);">
            Este producto no tiene opciones configuradas.
          </p>

          <!-- Options list -->
          <div v-for="option in product.options" :key="option.id" class="option-card">
            <div class="option-card__header" @click="toggleOption(option.id)">
              <div class="option-card__info">
                <span class="option-card__label">{{ option.label }}</span>
                <span class="admin-badge admin-badge--default" style="font-size: 0.7rem;">{{ option.key }}</span>
                <span class="admin-badge" :class="option.type === 'color' ? 'admin-badge--warning' : 'admin-badge--info'" style="font-size: 0.7rem;">
                  {{ getOptionTypeInfo(option.type)?.label }}
                </span>
                <span v-if="option.is_required" class="admin-badge admin-badge--error" style="font-size: 0.65rem;">Requerido</span>
              </div>
              <div class="option-card__actions">
                <button class="admin-action-btn admin-action-btn--edit" title="Editar" @click.stop="openEditOption(product.id, option)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                  </svg>
                </button>
                <button class="admin-action-btn admin-action-btn--delete" title="Eliminar" @click.stop="deleteOption(product.id, option)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" />
                  </svg>
                </button>
                <span class="option-chevron" :class="{ 'option-chevron--open': expandedOption === option.id }">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
                </span>
              </div>
            </div>

            <p v-if="option.help_text && expandedOption === option.id" class="option-card__help">{{ option.help_text }}</p>

            <!-- Values -->
            <div v-if="expandedOption === option.id && hasValues(option.type)" class="option-values">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <span style="font-size: 0.8rem; font-weight: 600; color: var(--admin-text-secondary);">Valores</span>
                <button class="admin-btn admin-btn--sm admin-btn--outline" @click="openNewValue(product.id, option.id, option.type)">
                  Agregar valor
                </button>
              </div>

              <p v-if="!option.values.length" style="font-size: 0.8rem; color: var(--admin-text-muted); text-align: center; padding: 0.75rem;">
                Sin valores configurados.
              </p>

              <div v-else class="option-values-grid">
                <div v-for="val in option.values" :key="val.id" class="option-value-chip">
                  <div v-if="option.type === 'color'" class="option-value-color" :style="{ background: (val.metadata?.hex as string) || '#ccc' }"></div>
                  <div class="option-value-chip__body">
                    <span class="option-value-chip__label">{{ val.label }}</span>
                    <span v-if="val.price_modifier_type !== 'none'" class="option-value-chip__price">
                      {{ val.price_modifier_type === 'add' ? '+' : val.price_modifier_type === 'subtract' ? '-' : '=' }}{{ val.price_modifier_amount }}
                    </span>
                    <span v-if="val.is_default" class="admin-badge admin-badge--success" style="font-size: 0.6rem; padding: 0.1rem 0.3rem;">Default</span>
                  </div>
                  <div class="option-value-chip__actions">
                    <button class="admin-action-btn admin-action-btn--edit" title="Editar" @click="openEditValue(product.id, option.id, val, option.type)" style="width: 26px; height: 26px;">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                      </svg>
                    </button>
                    <button class="admin-action-btn admin-action-btn--delete" title="Eliminar" @click="deleteValue(product.id, option.id, val)" style="width: 26px; height: 26px;">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Option Form Modal -->
    <Teleport to="body">
      <Transition name="confirm-fade">
        <div v-if="showOptionForm" class="option-modal-overlay" @click.self="showOptionForm = false">
          <div class="option-modal">
            <h3 class="option-modal__title">{{ editingOptionId ? 'Editar Opción' : 'Nueva Opción' }}</h3>
            <form @submit.prevent="submitOption">
              <div class="option-modal__field">
                <label>Nombre de la opción</label>
                <input v-model="optionForm.label" type="text" required placeholder="Ej: Forma, Color, Tamaño" @input="onOptionLabelInput" />
              </div>
              <div class="option-modal__field">
                <label>Clave</label>
                <input v-model="optionForm.key" type="text" required placeholder="forma" class="option-modal__key-input" />
              </div>
              <div class="option-modal__field">
                <label>Tipo de elemento</label>
                <select v-model="optionForm.type" required>
                  <option v-for="t in optionTypes" :key="t.value" :value="t.value">{{ t.label }} — {{ t.desc }}</option>
                </select>
              </div>
              <div class="option-modal__field">
                <label>Texto de ayuda (opcional)</label>
                <input v-model="optionForm.help_text" type="text" placeholder="Ej: Selecciona la forma deseada" />
              </div>
              <div class="option-modal__field">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                  <input v-model="optionForm.is_required" type="checkbox" />
                  Requerido
                </label>
              </div>
              <div class="option-modal__actions">
                <button type="button" class="admin-btn admin-btn--outline" @click="showOptionForm = false">Cancelar</button>
                <button type="submit" class="admin-btn admin-btn--primary">{{ editingOptionId ? 'Actualizar' : 'Crear' }}</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Value Form Modal -->
    <Teleport to="body">
      <Transition name="confirm-fade">
        <div v-if="showValueForm" class="option-modal-overlay" @click.self="showValueForm = false">
          <div class="option-modal">
            <h3 class="option-modal__title">{{ editingValueId ? 'Editar Valor' : 'Nuevo Valor' }}</h3>
            <form @submit.prevent="submitValue">
              <div class="option-modal__field">
                <label>Etiqueta</label>
                <input v-model="valueForm.label" type="text" required placeholder="Ej: Rojo, Grande, Redondo" />
              </div>
              <div class="option-modal__field">
                <label>Valor (clave interna)</label>
                <input v-model="valueForm.value" type="text" required placeholder="rojo" />
              </div>

              <!-- Color picker -->
              <div v-if="valueForm.metadata !== null && 'hex' in (valueForm.metadata ?? {})" class="option-modal__field">
                <label>Color</label>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <input v-model="valueForm.colorHex" type="color" style="width: 40px; height: 36px; border: 1px solid var(--admin-border); border-radius: 6px; cursor: pointer;" @input="onColorChange" />
                  <input v-model="valueForm.colorHex" type="text" style="width: 100px;" placeholder="#FF0000" @input="onColorChange" />
                  <div class="option-value-color" :style="{ background: valueForm.colorHex, width: '28px', height: '28px' }"></div>
                </div>
              </div>

              <div class="option-modal__row">
                <div class="option-modal__field" style="flex: 1;">
                  <label>Modificador de precio</label>
                  <select v-model="valueForm.price_modifier_type">
                    <option value="none">Ninguno</option>
                    <option value="add">Sumar</option>
                    <option value="subtract">Restar</option>
                    <option value="set">Fijar precio</option>
                  </select>
                </div>
                <div v-if="valueForm.price_modifier_type !== 'none'" class="option-modal__field" style="flex: 1;">
                  <label>Monto</label>
                  <input v-model.number="valueForm.price_modifier_amount" type="number" min="0" />
                </div>
              </div>

              <div class="option-modal__field">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                  <input v-model="valueForm.is_default" type="checkbox" />
                  Valor por defecto
                </label>
              </div>

              <div class="option-modal__actions">
                <button type="button" class="admin-btn admin-btn--outline" @click="showValueForm = false">Cancelar</button>
                <button type="submit" class="admin-btn admin-btn--primary">{{ editingValueId ? 'Actualizar' : 'Crear' }}</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

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
.option-product-card { margin-bottom: 1rem; }
.option-product-header { cursor: pointer; }

.option-chevron {
  display: inline-flex;
  transition: transform 0.2s ease;
  color: var(--admin-text-muted);
}
.option-chevron--open { transform: rotate(180deg); }
.option-chevron svg { display: block; stroke: currentColor; fill: none; }

.option-card {
  background: var(--admin-bg);
  border-radius: 8px;
  border: 1px solid var(--admin-border);
  margin-bottom: 0.75rem;
  overflow: hidden;
}

.option-card__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  cursor: pointer;
  gap: 0.5rem;
}

.option-card__info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.option-card__label {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--admin-text);
}

.option-card__actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.option-card__help {
  font-size: 0.8rem;
  color: var(--admin-text-muted);
  padding: 0 1rem 0.5rem;
  margin: 0;
}

.option-values {
  padding: 0.75rem 1rem;
  border-top: 1px solid var(--admin-border);
}

.option-values-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.option-value-chip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--admin-surface);
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  padding: 0.4rem 0.6rem;
  font-size: 0.8rem;
}

.option-value-color {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid var(--admin-border);
  flex-shrink: 0;
}

.option-value-chip__body {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.option-value-chip__label {
  font-weight: 500;
  color: var(--admin-text);
}

.option-value-chip__price {
  font-size: 0.7rem;
  color: var(--admin-primary);
  font-weight: 600;
}

.option-value-chip__actions {
  display: flex;
  gap: 0.15rem;
  margin-left: 0.25rem;
}

/* Modals */
.option-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.option-modal {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  padding: 2rem;
  max-width: 480px;
  width: 100%;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}

.option-modal__title {
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 1.25rem;
  color: var(--admin-text);
}

.option-modal__field {
  margin-bottom: 1rem;
}

.option-modal__field label {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  color: var(--admin-text-secondary);
  margin-bottom: 0.3rem;
}

.option-modal__field input[type="text"],
.option-modal__field input[type="number"],
.option-modal__field select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  font-family: var(--admin-font);
  font-size: 0.85rem;
  color: var(--admin-text);
  background: var(--admin-surface);
}

.option-modal__field input:focus,
.option-modal__field select:focus {
  outline: none;
  border-color: var(--admin-primary);
  box-shadow: 0 0 0 3px var(--admin-primary-light);
}

.option-modal__key-input {
  font-family: monospace !important;
  color: var(--admin-primary) !important;
}

.option-modal__row {
  display: flex;
  gap: 1rem;
}

.option-modal__actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}
</style>
