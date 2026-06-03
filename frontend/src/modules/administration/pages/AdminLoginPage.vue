<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAdminAuthStore } from '../stores/adminAuth'

const adminAuth = useAdminAuthStore()
const router = useRouter()

const form = reactive({ email: '', password: '', code: '' })
const error = ref<string | null>(null)
const loading = ref(false)
const needsCode = ref(false)

async function submit(): Promise<void> {
  error.value = null
  loading.value = true
  try {
    await adminAuth.login({ ...form, code: form.code || undefined })
    await router.push({ name: 'admin.dashboard' })
  } catch (e: unknown) {
    const status = (e as { response?: { status?: number } }).response?.status
    if (status === 423) {
      // Two-factor required — reveal the code field.
      needsCode.value = true
      error.value = 'Ingresa tu código de autenticación en dos pasos.'
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
  <form class="auth-form" @submit.prevent="submit">
    <h2>Administration</h2>
    <p>Sign in to the Cronos admin panel.</p>
    <label>
      Email
      <input v-model="form.email" type="email" required :disabled="needsCode" />
    </label>
    <label>
      Password
      <input v-model="form.password" type="password" required :disabled="needsCode" />
    </label>
    <label v-if="needsCode">
      Código 2FA
      <input v-model="form.code" inputmode="numeric" maxlength="6" placeholder="123456" autofocus />
    </label>
    <p v-if="error" class="auth-form__error">{{ error }}</p>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Signing in…' : needsCode ? 'Verificar' : 'Sign in' }}
    </button>
  </form>
</template>
