<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
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

const roleOptions = [
  { label: 'Customer', value: 'customer' },
  { label: 'Staff', value: 'staff' },
  { label: 'Admin', value: 'admin' },
]

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
</style>
