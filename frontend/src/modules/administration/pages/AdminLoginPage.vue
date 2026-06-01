<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAdminAuthStore } from '../stores/adminAuth'

const adminAuth = useAdminAuthStore()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const error = ref<string | null>(null)
const loading = ref(false)

async function submit(): Promise<void> {
  error.value = null
  loading.value = true
  try {
    await adminAuth.login(form)
    await router.push({ name: 'admin.dashboard' })
  } catch {
    error.value = 'Invalid administrator credentials.'
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
      <input v-model="form.email" type="email" required />
    </label>
    <label>
      Password
      <input v-model="form.password" type="password" required />
    </label>
    <p v-if="error" class="auth-form__error">{{ error }}</p>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Signing in…' : 'Sign in' }}
    </button>
  </form>
</template>
