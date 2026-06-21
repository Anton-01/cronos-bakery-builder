<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ProgressSpinner from 'primevue/progressspinner'

import { adminPanelService, type EmailTemplate, type ReminderRule } from '../services/adminPanelService'
import { useToast } from '@/composables/useToast'

const { success, error } = useToast()

const templates = ref<EmailTemplate[]>([])
const reminderRules = ref<ReminderRule[]>([])
const loading = ref(true)

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
    if (idx !== -1) templates.value[idx] = updated
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
    <div class="admin-page-header">
      <div>
        <h1>Correos</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Comunicaciones <span>/</span> Correos</div>
      </div>
    </div>

    <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <template v-else>
      <h2 class="section-title">Plantillas de Correo</h2>

      <p v-if="templates.length === 0" style="color:var(--admin-text-muted);">No hay plantillas registradas.</p>

      <Card v-for="template in templates" :key="template.id" style="margin-bottom:1rem;">
        <template #title>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span>{{ template.key }}</span>
            <Tag :value="template.is_active ? 'Activo' : 'Inactivo'" :severity="template.is_active ? 'success' : 'danger'" />
          </div>
        </template>
        <template #content>
          <!-- View mode -->
          <template v-if="!getEditState(template).active">
            <p style="font-weight:500; margin-bottom:0.5rem;">{{ template.subject }}</p>
            <p style="font-size:0.85rem; color:var(--admin-text-muted); white-space:pre-wrap;">
              {{ template.body.slice(0, 200) }}{{ template.body.length > 200 ? '…' : '' }}
            </p>
            <div style="margin-top:0.75rem;">
              <Button label="Editar" size="small" severity="info" @click="startEdit(template)" />
            </div>
          </template>

          <!-- Edit mode -->
          <template v-else>
            <div class="email-field">
              <label>Asunto</label>
              <InputText v-model="getEditState(template).subject" fluid placeholder="Asunto del correo" />
            </div>
            <div class="email-field">
              <label>Cuerpo</label>
              <Textarea v-model="getEditState(template).body" rows="6" fluid placeholder="Cuerpo del correo" style="resize:vertical;" />
            </div>
            <div style="display:flex; gap:0.5rem;">
              <Button
                :label="getEditState(template).saving ? 'Guardando...' : 'Guardar'"
                :loading="getEditState(template).saving"
                size="small"
                @click="saveTemplate(template)"
              />
              <Button
                label="Cancelar"
                severity="secondary"
                outlined
                size="small"
                :disabled="getEditState(template).saving"
                @click="cancelEdit(template)"
              />
            </div>
          </template>
        </template>
      </Card>

      <h2 class="section-title">Reglas de Recordatorio</h2>

      <Card>
        <template #title>Reglas configuradas</template>
        <template #content>
          <p v-if="reminderRules.length === 0" style="color:var(--admin-text-muted);">No hay reglas de recordatorio registradas.</p>

          <DataTable v-else :value="reminderRules" class="p-datatable-sm">
            <Column header="Evento" field="event" />
            <Column header="Delay (minutos)" field="delay_minutes" style="width:160px." />
            <Column header="Plantilla" style="width:180px.">
              <template #body="{ data }">{{ templateKeyForId(data.template_id) }}</template>
            </Column>
            <Column header="Estado" style="width:100px.">
              <template #body="{ data }">
                <Tag :value="data.is_active ? 'Activo' : 'Inactivo'" :severity="data.is_active ? 'success' : 'danger'" />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </template>
  </div>
</template>

<style scoped>
.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 1.5rem 0 1rem;
  color: var(--admin-text);
}
.email-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.email-field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
</style>
