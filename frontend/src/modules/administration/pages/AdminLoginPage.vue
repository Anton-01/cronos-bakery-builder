<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useToast } from '@/composables/useToast'
import { useAdminAuthStore } from '../stores/adminAuth'

const adminAuth = useAdminAuthStore()
const router = useRouter()
const { success, warning } = useToast()

const form = reactive({ email: '', password: '', code: '' })
const error = ref<string | null>(null)
const loading = ref(false)
const needsCode = ref(false)

async function submit(): Promise<void> {
  error.value = null
  loading.value = true
  try {
    await adminAuth.login({ ...form, code: form.code || undefined })
    success('Bienvenido al panel de administracion')
    await router.push({ name: 'admin.dashboard' })
  } catch (e: unknown) {
    const status = (e as { response?: { status?: number } }).response?.status
    if (status === 423) {
      needsCode.value = true
      error.value = 'Ingresa tu codigo de autenticacion en dos pasos.'
      warning('Se requiere verificacion en dos pasos')
    } else {
      error.value = needsCode.value
        ? 'Codigo invalido. Intenta de nuevo.'
        : 'Credenciales de administrador invalidas.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="admin-login">
    <div class="admin-login__card">
      <div class="admin-login__brand">
        <div class="admin-login__brand-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        </div>
        <span class="admin-login__brand-name">Cronos Admin</span>
      </div>

      <h2>Bienvenido</h2>
      <p>Ingresa tus credenciales para acceder al panel de administracion</p>

      <form @submit.prevent="submit">
        <p v-if="error" class="admin-login__error">{{ error }}</p>

        <label>
          Correo electronico
          <input v-model="form.email" type="email" required :disabled="needsCode" placeholder="admin@cronos.com" />
        </label>
        <label>
          Contrasena
          <input v-model="form.password" type="password" required :disabled="needsCode" placeholder="Tu contrasena" />
        </label>
        <label v-if="needsCode">
          Codigo 2FA
          <input v-model="form.code" inputmode="numeric" maxlength="6" placeholder="123456" autofocus />
        </label>
        <button type="submit" :disabled="loading">
          {{ loading ? 'Verificando...' : needsCode ? 'Verificar Codigo' : 'Iniciar Sesion' }}
        </button>
      </form>
    </div>
  </div>
</template>
