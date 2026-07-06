<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import { adminPanelService, type AdminSession, type AdminUser } from '../services/adminPanelService'

const props = defineProps<{
  open: boolean
  user: AdminUser | null
}>()
const emit = defineEmits<{
  close: []
  save: [data: Record<string, string>]
}>()

const isEditing = computed(() => !!props.user)

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  role: 'customer',
})

const roleOptions = [
  { label: 'Customer', value: 'customer' },
  { label: 'Staff', value: 'staff' },
  { label: 'Admin', value: 'admin' },
]

// --- Auditoría: sesiones recientes del usuario (solo al editar) ---
const sessions = ref<AdminSession[]>([])
const loadingSessions = ref(false)

async function loadSessions(userId: number): Promise<void> {
  loadingSessions.value = true
  sessions.value = []
  try {
    sessions.value = await adminPanelService.userSessions(userId)
  } catch {
    // La auditoría es informativa: un fallo no bloquea la edición.
  } finally {
    loadingSessions.value = false
  }
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateStr))
}

watch(() => props.open, (val) => {
  if (val && props.user) {
    form.value = {
      first_name: props.user.first_name ?? '',
      last_name: props.user.last_name ?? '',
      email: props.user.email,
      phone: props.user.phone ?? '',
      password: '',
      role: props.user.roles[0] ?? 'customer',
    }
    loadSessions(props.user.id)
  } else if (val) {
    form.value = { first_name: '', last_name: '', email: '', phone: '', password: '', role: 'customer' }
    sessions.value = []
  }
})

function submit() {
  const data: Record<string, string> = {
    first_name: form.value.first_name,
    last_name: form.value.last_name,
    email: form.value.email,
    role: form.value.role,
  }
  if (form.value.phone) data.phone = form.value.phone
  if (!isEditing.value && form.value.password) data.password = form.value.password
  emit('save', data)
}
</script>

<template>
  <Dialog
    :visible="open"
    :header="isEditing ? 'Editar Usuario' : 'Nuevo Usuario'"
    :style="{ width: '520px' }"
    modal
    @update:visible="emit('close')"
  >
    <form @submit.prevent="submit">
      <div class="form-row">
        <div class="form-field">
          <label>Nombre</label>
          <InputText v-model="form.first_name" fluid required placeholder="Nombre" />
        </div>
        <div class="form-field">
          <label>Apellido</label>
          <InputText v-model="form.last_name" fluid required placeholder="Apellido" />
        </div>
      </div>
      <div class="form-field">
        <label>Email</label>
        <InputText v-model="form.email" type="email" fluid required placeholder="correo@ejemplo.com" />
      </div>
      <div class="form-row">
        <div class="form-field">
          <label>Telefono</label>
          <InputText v-model="form.phone" fluid placeholder="Opcional" />
        </div>
        <div class="form-field">
          <label>Rol</label>
          <Select v-model="form.role" :options="roleOptions" optionLabel="label" optionValue="value" fluid />
        </div>
      </div>
      <div v-if="!isEditing" class="form-field">
        <label>Contraseña</label>
        <InputText v-model="form.password" type="password" fluid required placeholder="Minimo 8 caracteres" />
      </div>

      <!-- Auditoría: sesiones recientes (Sanctum) -->
      <div v-if="isEditing" class="sessions-audit">
        <div class="sessions-audit__title">
          <i class="pi pi-history" style="font-size:0.8rem;" />
          Sesiones recientes
        </div>
        <p v-if="loadingSessions" class="sessions-audit__empty">Cargando sesiones...</p>
        <p v-else-if="!sessions.length" class="sessions-audit__empty">Sin sesiones registradas.</p>
        <ul v-else class="sessions-audit__list">
          <li v-for="session in sessions.slice(0, 6)" :key="session.id">
            <span class="sessions-audit__device">{{ session.device_name }}</span>
            <Tag v-if="session.name === 'impersonation'" value="Impersonación" severity="warn" style="font-size:0.55rem;" />
            <code class="sessions-audit__ip">{{ session.ip_address ?? '—' }}</code>
            <span class="sessions-audit__date">{{ formatDate(session.last_used_at ?? session.created_at) }}</span>
          </li>
        </ul>
      </div>

      <div class="form-footer">
        <Button label="Cancelar" severity="secondary" outlined type="button" @click="emit('close')" />
        <Button :label="isEditing ? 'Guardar' : 'Crear Usuario'" type="submit" />
      </div>
    </form>
  </Dialog>
</template>

<style scoped>
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}
.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.form-field label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--admin-text);
}
.form-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 0.5rem;
}
.sessions-audit {
  margin: 0.25rem 0 1rem;
  padding: 0.75rem;
  background: var(--admin-bg);
  border: 1px solid var(--admin-border);
  border-radius: 8px;
}
.sessions-audit__title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--admin-text-muted);
  margin-bottom: 0.5rem;
}
.sessions-audit__empty {
  font-size: 0.8rem;
  color: var(--admin-text-muted);
  margin: 0;
}
.sessions-audit__list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.sessions-audit__list li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.3rem 0;
  font-size: 0.78rem;
  border-bottom: 1px dashed var(--admin-border);
}
.sessions-audit__list li:last-child {
  border-bottom: none;
}
.sessions-audit__device {
  font-weight: 600;
  min-width: 9rem;
}
.sessions-audit__ip {
  background: var(--admin-surface, #fff);
  padding: 0.05rem 0.35rem;
  border-radius: 4px;
  font-size: 0.72rem;
}
.sessions-audit__date {
  margin-left: auto;
  color: var(--admin-text-muted);
  font-size: 0.72rem;
}
</style>
