<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import type { RegisterPayload } from '../types'

const auth = useAuthStore()
const router = useRouter()

const form = reactive<RegisterPayload>({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

const error = ref<string | null>(null)
const loading = ref(false)

async function submit(): Promise<void> {
  error.value = null
  loading.value = true
  try {
    await auth.register(form)
    await router.push({ name: 'home' })
  } catch {
    error.value = 'We could not create your account. Please review the form and try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="auth-form" @submit.prevent="submit">
    <h2>Create your account</h2>
    <label>
      First name
      <input v-model="form.first_name" type="text" required />
    </label>
    <label>
      Last name
      <input v-model="form.last_name" type="text" required />
    </label>
    <label>
      Email
      <input v-model="form.email" type="email" required />
    </label>
    <label>
      Phone
      <input v-model="form.phone" type="tel" />
    </label>
    <label>
      Password
      <input v-model="form.password" type="password" required />
    </label>
    <label>
      Confirm password
      <input v-model="form.password_confirmation" type="password" required />
    </label>
    <p v-if="error" class="auth-form__error">{{ error }}</p>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Creating account…' : 'Sign up' }}
    </button>
    <RouterLink to="/login">Already have an account? Sign in</RouterLink>
  </form>
</template>
