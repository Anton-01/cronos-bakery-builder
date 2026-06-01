<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const error = ref<string | null>(null)
const loading = ref(false)

async function submit(): Promise<void> {
  error.value = null
  loading.value = true
  try {
    await auth.login(form)
    await router.push({ name: 'home' })
  } catch {
    error.value = 'Invalid credentials. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="login" @submit.prevent="submit">
    <h2>Sign in</h2>
    <label>
      Email
      <input v-model="form.email" type="email" required />
    </label>
    <label>
      Password
      <input v-model="form.password" type="password" required />
    </label>
    <p v-if="error" class="login__error">{{ error }}</p>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Signing in…' : 'Sign in' }}
    </button>
  </form>
</template>
