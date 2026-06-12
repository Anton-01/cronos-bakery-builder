<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { AdminUser } from '../services/adminPanelService'

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
  } else if (val) {
    form.value = { first_name: '', last_name: '', email: '', phone: '', password: '', role: 'customer' }
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
  <Teleport to="body">
    <div v-if="open" class="user-modal-overlay" @click.self="emit('close')">
      <div class="user-modal">
        <div class="user-modal__header">
          <h2>{{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}</h2>
          <button class="user-modal__close" @click="emit('close')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="user-modal__body" @submit.prevent="submit">
          <div class="user-modal__row">
            <div class="user-modal__field">
              <label>Nombre</label>
              <input v-model="form.first_name" type="text" required placeholder="Nombre" />
            </div>
            <div class="user-modal__field">
              <label>Apellido</label>
              <input v-model="form.last_name" type="text" required placeholder="Apellido" />
            </div>
          </div>
          <div class="user-modal__field">
            <label>Email</label>
            <input v-model="form.email" type="email" required placeholder="correo@ejemplo.com" />
          </div>
          <div class="user-modal__row">
            <div class="user-modal__field">
              <label>Telefono</label>
              <input v-model="form.phone" type="tel" placeholder="Opcional" />
            </div>
            <div class="user-modal__field">
              <label>Rol</label>
              <select v-model="form.role">
                <option value="customer">Customer</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div v-if="!isEditing" class="user-modal__field">
            <label>Contrasena</label>
            <input v-model="form.password" type="password" required placeholder="Minimo 8 caracteres" />
          </div>
          <div class="user-modal__footer">
            <button type="button" class="user-modal__btn user-modal__btn--secondary" @click="emit('close')">Cancelar</button>
            <button type="submit" class="user-modal__btn user-modal__btn--primary">{{ isEditing ? 'Guardar' : 'Crear Usuario' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.user-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
}

.user-modal {
  background: #fff;
  border-radius: 12px;
  width: 100%;
  max-width: 520px;
  box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
  overflow: hidden;
}

.user-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.user-modal__header h2 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  font-family: inherit;
}

.user-modal__close {
  border: none;
  background: none;
  cursor: pointer;
  color: #6b7280;
  padding: 0.25rem;
  border-radius: 6px;
}

.user-modal__close:hover {
  background: #f3f4f6;
  color: #111827;
}

.user-modal__body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.user-modal__row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.user-modal__field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.user-modal__field label {
  font-size: 0.8125rem;
  font-weight: 500;
  color: #374151;
}

.user-modal__field input,
.user-modal__field select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;
  color: #111827;
  transition: border-color 0.15s;
}

.user-modal__field input:focus,
.user-modal__field select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.user-modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 0.5rem;
}

.user-modal__btn {
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
  border: none;
}

.user-modal__btn--secondary {
  background: #f3f4f6;
  color: #374151;
}

.user-modal__btn--secondary:hover {
  background: #e5e7eb;
}

.user-modal__btn--primary {
  background: #6366f1;
  color: #fff;
}

.user-modal__btn--primary:hover {
  background: #4f46e5;
}
</style>
