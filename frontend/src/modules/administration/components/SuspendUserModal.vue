<script setup lang="ts">
import { ref, watch } from 'vue'
import type { AdminUser } from '../services/adminPanelService'

const props = defineProps<{
  open: boolean
  user: AdminUser | null
}>()

const emit = defineEmits<{
  close: []
  confirm: [data: { reason: string; suspended_until?: string }]
}>()

const reason = ref('')
const suspendedUntil = ref('')

watch(() => props.open, (val) => {
  if (val) {
    reason.value = ''
    suspendedUntil.value = ''
  }
})

function submit() {
  const data: { reason: string; suspended_until?: string } = { reason: reason.value }
  if (suspendedUntil.value) data.suspended_until = suspendedUntil.value
  emit('confirm', data)
}
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="user-modal-overlay" @click.self="emit('close')">
      <div class="user-modal" style="max-width: 440px">
        <div class="user-modal__header">
          <h2>Suspender Usuario</h2>
          <button class="user-modal__close" @click="emit('close')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><path d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="user-modal__body" @submit.prevent="submit">
          <div v-if="user" class="suspend-modal__warning">
            Se suspendera la cuenta de <strong>{{ user.name }}</strong>. El usuario no podra iniciar sesion y todas sus sesiones activas seran cerradas.
          </div>
          <div class="user-modal__field">
            <label>Motivo de la suspension *</label>
            <textarea v-model="reason" required rows="3" placeholder="Describe el motivo de la suspension..." class="suspend-modal__textarea"></textarea>
          </div>
          <div class="user-modal__field">
            <label>Suspender hasta (opcional)</label>
            <input v-model="suspendedUntil" type="datetime-local" class="suspend-modal__input" />
            <span class="suspend-modal__hint">Dejar vacio para suspension indefinida.</span>
          </div>
          <div class="user-modal__footer">
            <button type="button" class="user-modal__btn user-modal__btn--secondary" @click="emit('close')">Cancelar</button>
            <button type="submit" class="user-modal__btn user-modal__btn--danger">Suspender</button>
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
}

.user-modal__body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
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
  border: none;
}

.user-modal__btn--secondary {
  background: #f3f4f6;
  color: #374151;
}

.user-modal__btn--secondary:hover {
  background: #e5e7eb;
}

.user-modal__btn--danger {
  background: #dc2626;
  color: #fff;
}

.user-modal__btn--danger:hover {
  background: #b91c1c;
}

.suspend-modal__warning {
  padding: 0.75rem;
  background: #fef2f2;
  border-radius: 8px;
  font-size: 0.8125rem;
  color: #991b1b;
}

.suspend-modal__textarea {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;
  resize: vertical;
}

.suspend-modal__textarea:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.suspend-modal__input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;
}

.suspend-modal__input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.suspend-modal__hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
