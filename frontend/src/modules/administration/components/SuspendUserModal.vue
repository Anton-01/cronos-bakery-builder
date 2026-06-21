<script setup lang="ts">
import { ref, watch } from 'vue'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import Message from 'primevue/message'
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
  <Dialog
    :visible="open"
    header="Suspender Usuario"
    :style="{ width: '440px' }"
    modal
    @update:visible="emit('close')"
  >
    <form @submit.prevent="submit">
      <Message v-if="user" severity="error" :closable="false" style="margin-bottom: 1rem;">
        Se suspenderá la cuenta de <strong>{{ user.name }}</strong>. El usuario no podrá iniciar sesión y todas sus sesiones activas serán cerradas.
      </Message>

      <div class="form-field">
        <label>Motivo de la suspensión *</label>
        <Textarea v-model="reason" required rows="3" fluid placeholder="Describe el motivo de la suspensión..." />
      </div>

      <div class="form-field">
        <label>Suspender hasta (opcional)</label>
        <input v-model="suspendedUntil" type="datetime-local" class="p-inputtext p-component" style="width:100%;" />
        <small style="color: var(--admin-text-muted);">Dejar vacío para suspensión indefinida.</small>
      </div>

      <div class="form-footer">
        <Button label="Cancelar" severity="secondary" outlined type="button" @click="emit('close')" />
        <Button label="Suspender" severity="danger" type="submit" />
      </div>
    </form>
  </Dialog>
</template>

<style scoped>
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
</style>
