<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'

import { adminPanelService, type EmailTemplate, type ReminderRule } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'

const { success, error } = useToast()

const templates = ref<EmailTemplate[]>([])
const reminderRules = ref<ReminderRule[]>([])
const loading = ref(true)

// Per-template edit state: { [id]: { active: boolean, subject: string, body: string, saving: boolean } }
interface EditState {
  active: boolean
  subject: string
  body: string
  saving: boolean
}
const editStates = reactive<Record<string, EditState>>({})

function getEditState(template: EmailTemplate): EditState {
  if (!editStates[template.id]) {
    editStates[template.id] = { active: false, subject: template.subject, body: template.body, saving: false }
  }
  return editStates[template.id]
}

function startEdit(template: EmailTemplate): void {
  const state = getEditState(template)
  state.subject = template.subject
  state.body = template.body
  state.active = true
}

function cancelEdit(template: EmailTemplate): void {
  const state = getEditState(template)
  state.active = false
  state.subject = template.subject
  state.body = template.body
}

async function saveTemplate(template: EmailTemplate): Promise<void> {
  const state = getEditState(template)
  state.saving = true
  try {
    const updated = await adminPanelService.updateTemplate(template.id, {
      subject: state.subject,
      body: state.body,
    })
    const idx = templates.value.findIndex((t) => t.id === template.id)
    if (idx !== -1) {
      templates.value[idx] = updated
    }
    state.active = false
    success('Plantilla actualizada exitosamente')
  } catch {
    error('Error al guardar la plantilla')
  } finally {
    state.saving = false
  }
}

function templateKeyForId(id: string): string {
  const t = templates.value.find((t) => t.id === id)
  return t ? t.key : id
}

async function load(): Promise<void> {
  loading.value = true
  try {
    const [tpls, rules] = await Promise.all([
      adminPanelService.emailTemplates(),
      adminPanelService.reminderRules(),
    ])
    templates.value = tpls
    reminderRules.value = rules
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Correos</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Comunicaciones <span>/</span> Correos
        </div>
      </div>
    </div>

    <p v-if="loading" style="text-align: center; padding: 2rem; color: var(--admin-text-muted);">
      Cargando...
    </p>

    <template v-else>
      <!-- Section 1: Email Templates -->
      <h2 class="admin-section-title">Plantillas de Correo</h2>

      <p v-if="templates.length === 0" style="color: var(--admin-text-muted);">
        No hay plantillas registradas.
      </p>

      <div
        v-for="template in templates"
        :key="template.id"
        class="admin-content-card admin-template-card"
      >
        <div class="admin-content-card__header">
          <div class="admin-template-header">
            <h3 class="admin-content-card__title">{{ template.key }}</h3>
            <span
              class="admin-badge"
              :class="template.is_active ? 'admin-badge--success' : 'admin-badge--error'"
            >
              {{ template.is_active ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
        </div>

        <div class="admin-content-card__body">
          <!-- View mode -->
          <template v-if="!getEditState(template).active">
            <p class="admin-template-subject">{{ template.subject }}</p>
            <p class="admin-template-body">{{ template.body.slice(0, 200) }}{{ template.body.length > 200 ? '…' : '' }}</p>
            <div style="margin-top: 0.75rem;">
              <button class="admin-btn admin-btn--sm admin-btn--primary" @click="startEdit(template)">
                Editar
              </button>
            </div>
          </template>

          <!-- Edit mode -->
          <template v-else>
            <label style="display: block; font-size: 0.875rem; margin-bottom: 0.25rem; font-weight: 500;">Asunto</label>
            <input
              v-model="getEditState(template).subject"
              type="text"
              class="admin-edit-field"
              placeholder="Asunto del correo"
            />

            <label style="display: block; font-size: 0.875rem; margin-bottom: 0.25rem; font-weight: 500;">Cuerpo</label>
            <textarea
              v-model="getEditState(template).body"
              class="admin-edit-field admin-edit-field--textarea"
              placeholder="Cuerpo del correo"
            />

            <div style="display: flex; gap: 0.5rem; margin-top: 0.25rem;">
              <button
                class="admin-btn admin-btn--sm admin-btn--primary"
                :disabled="getEditState(template).saving"
                @click="saveTemplate(template)"
              >
                {{ getEditState(template).saving ? 'Guardando...' : 'Guardar' }}
              </button>
              <button
                class="admin-btn admin-btn--sm"
                :disabled="getEditState(template).saving"
                @click="cancelEdit(template)"
              >
                Cancelar
              </button>
            </div>
          </template>
        </div>
      </div>

      <!-- Section 2: Reminder Rules -->
      <h2 class="admin-section-title">Reglas de Recordatorio</h2>

      <div class="admin-content-card">
        <div class="admin-content-card__header">
          <h3 class="admin-content-card__title">Reglas configuradas</h3>
        </div>
        <div class="admin-content-card__body">
          <p v-if="reminderRules.length === 0" style="color: var(--admin-text-muted);">
            No hay reglas de recordatorio registradas.
          </p>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Evento</th>
                <th>Delay (minutos)</th>
                <th>Plantilla</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="rule in reminderRules" :key="rule.id">
                <td>{{ rule.event }}</td>
                <td>{{ rule.delay_minutes }}</td>
                <td>{{ templateKeyForId(rule.template_id) }}</td>
                <td>
                  <span
                    class="admin-badge"
                    :class="rule.is_active ? 'admin-badge--success' : 'admin-badge--error'"
                  >
                    {{ rule.is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.admin-section-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 1.5rem 0 1rem;
  color: var(--admin-text);
}

.admin-template-card {
  margin-bottom: 1rem;
}

.admin-template-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.admin-template-subject {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.admin-template-body {
  font-size: 0.85rem;
  color: var(--admin-text-muted);
  white-space: pre-wrap;
}

.admin-edit-field {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--admin-border);
  border-radius: 6px;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.admin-edit-field--textarea {
  min-height: 120px;
  resize: vertical;
  font-family: inherit;
}
</style>
