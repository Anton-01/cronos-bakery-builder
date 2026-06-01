<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { authService } from '../services/authService'
import type { ResetPasswordPayload } from '../types'

const route = useRoute()
const router = useRouter()

const form = reactive<ResetPasswordPayload>({
  token: (route.query.token as string) ?? '',
  email: (route.query.email as string) ?? '',
  password: '',
  password_confirmation: '',
})

const error = ref<string | null>(null)
const loading = ref(false)

async function submit(): Promise<void> {
  error.value = null
  loading.value = true
  try {
    await authService.resetPassword(form)
    await router.push({ name: 'auth.login' })
  } catch {
    error.value = 'This reset link is invalid or has expired.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="auth-form" @submit.prevent="submit">
    <h2>Reset password</h2>
    <label>
      Email
      <input v-model="form.email" type="email" required />
    </label>
    <label>
      New password
      <input v-model="form.password" type="password" required />
    </label>
    <label>
      Confirm password
      <input v-model="form.password_confirmation" type="password" required />
    </label>
    <p v-if="error" class="auth-form__error">{{ error }}</p>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Resetting…' : 'Reset password' }}
    </button>
  </form>
</template>
