<script setup lang="ts">
import { ref } from 'vue'

import { authService } from '../services/authService'

const email = ref('')
const message = ref<string | null>(null)
const loading = ref(false)

async function submit(): Promise<void> {
  loading.value = true
  try {
    const response = await authService.forgotPassword(email.value)
    message.value = response.message
  } catch {
    message.value = 'If the email exists, a reset link has been sent.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form class="auth-form" @submit.prevent="submit">
    <h2>Forgot password</h2>
    <p>Enter your email and we'll send you a reset link.</p>
    <label>
      Email
      <input v-model="email" type="email" required />
    </label>
    <p v-if="message" class="auth-form__message">{{ message }}</p>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Sending…' : 'Send reset link' }}
    </button>
    <RouterLink to="/login">Back to sign in</RouterLink>
  </form>
</template>
