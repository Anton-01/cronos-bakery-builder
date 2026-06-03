<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/auth'
import { authService } from '../services/authService'
import type { SocialProvider } from '../types'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const error = ref<string | null>(null)
const loading = ref(false)

const providers: SocialProvider[] = ['google', 'facebook', 'apple']

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

async function loginWith(provider: SocialProvider): Promise<void> {
  const url = await authService.socialRedirectUrl(provider)
  window.location.href = url
}
</script>

<template>
  <form class="auth-form" @submit.prevent="submit">
    <h2>Sign in</h2>
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

    <div class="auth-form__social">
      <button
        v-for="provider in providers"
        :key="provider"
        type="button"
        class="auth-form__social-btn"
        @click="loginWith(provider)"
      >
        Continue with {{ provider }}
      </button>
    </div>

    <div class="auth-form__links">
      <RouterLink to="/register">Create account</RouterLink>
      <RouterLink to="/forgot-password">Forgot password?</RouterLink>
    </div>
  </form>
</template>
