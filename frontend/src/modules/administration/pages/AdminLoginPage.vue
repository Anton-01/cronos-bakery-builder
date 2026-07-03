<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'

import { useToast } from '@/composables/useToast'
import { useAdminAuthStore } from '../stores/adminAuth'

const adminAuth = useAdminAuthStore()
const router = useRouter()
const { success, warning } = useToast()

const form = reactive({ email: '', password: '', code: '' })
const error = ref<string | null>(null)
const loading = ref(false)
const needsCode = ref(false)
const rememberDevice = ref(false)

async function submit(): Promise<void> {
  error.value = null
  loading.value = true
  try {
    await adminAuth.login({ ...form, code: form.code || undefined })
    success('Bienvenido al panel de administración')
    await router.push({ name: 'admin.dashboard' })
  } catch (e: unknown) {
    const status = (e as { response?: { status?: number } }).response?.status
    if (status === 423) {
      needsCode.value = true
      error.value = 'Ingresa tu código de autenticación en dos pasos.'
      warning('Se requiere verificación en dos pasos')
    } else {
      error.value = needsCode.value
        ? 'Código inválido. Intenta de nuevo.'
        : 'Credenciales de administrador inválidas.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="admin-login">
    <Card class="admin-login__card">
      <template #content>
        <div class="admin-login__brand">
          <div class="admin-login__brand-icon">
            <i class="pi pi-th-large" style="color:#fff; font-size:1.1rem;"></i>
          </div>
          <span class="admin-login__brand-name">Cronos Builder</span>
        </div>

        <h2 style="text-align:center; margin: 0 0 0.5rem;">Iniciar Sesión</h2>
        <p style="text-align:center; color:var(--admin-text-secondary); margin:0 0 1.5rem; font-size:0.9rem;">
          Panel de Administración
        </p>

        <Message v-if="error" severity="error" :closable="false" style="margin-bottom:1rem;">{{ error }}</Message>

        <form @submit.prevent="submit">
          <div class="login-field">
            <label>Email</label>
            <InputText v-model="form.email" type="email" fluid required :disabled="needsCode" placeholder="admin@cronos.com" />
          </div>

          <div class="login-field">
            <label>Contraseña</label>
            <Password v-model="form.password" fluid required :disabled="needsCode" placeholder="••••••••" :feedback="false" toggleMask />
          </div>

          <div v-if="needsCode" class="login-field">
            <label>Código 2FA</label>
            <InputText v-model="form.code" inputmode="numeric" maxlength="6" fluid placeholder="123456" autofocus />
          </div>

          <div class="login-options">
            <div style="display:flex; align-items:center; gap:0.5rem;">
              <Checkbox v-model="rememberDevice" inputId="remember" binary />
              <label for="remember" style="font-size:0.875rem; cursor:pointer;">Recordar dispositivo</label>
            </div>
            <a href="#" class="login-forgot" @click.prevent>¿Olvidaste tu contraseña?</a>
          </div>

          <Button
            :label="loading ? 'Verificando...' : needsCode ? 'Verificar Código' : 'Iniciar Sesión'"
            type="submit"
            :loading="loading"
            fluid
          />
        </form>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.admin-login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--admin-primary-light, #ecf2ff);
  font-family: var(--admin-font);
}
.admin-login__card {
  width: min(460px, 92vw);
}
.admin-login__brand {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  justify-content: center;
  margin-bottom: 1.75rem;
}
.admin-login__brand-icon {
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, var(--admin-primary), #49beff);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.admin-login__brand-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--admin-text);
}
.login-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: 1.25rem;
}
.login-field label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--admin-text);
}
.login-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}
.login-forgot {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--admin-primary);
  text-decoration: none;
}
</style>
