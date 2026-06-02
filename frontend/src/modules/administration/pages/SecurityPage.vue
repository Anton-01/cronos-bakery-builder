<script setup lang="ts">
import { ref } from 'vue'

import { adminPanelService, type TwoFactorSetup } from '../services/adminPanelService'

const setup = ref<TwoFactorSetup | null>(null)
const code = ref('')
const message = ref<string | null>(null)
const error = ref<string | null>(null)
const busy = ref(false)

async function enable(): Promise<void> {
  busy.value = true
  error.value = null
  try {
    setup.value = await adminPanelService.enableTwoFactor()
  } finally {
    busy.value = false
  }
}

async function confirm(): Promise<void> {
  error.value = null
  try {
    const res = await adminPanelService.confirmTwoFactor(code.value)
    message.value = res.message
    setup.value = null
    code.value = ''
  } catch {
    error.value = 'Código inválido. Intenta de nuevo.'
  }
}

async function disable(): Promise<void> {
  const res = await adminPanelService.disableTwoFactor()
  message.value = res.message
  setup.value = null
}
</script>

<template>
  <section class="admin-page">
    <h1>Seguridad · Autenticación en dos pasos (2FA)</h1>
    <p>Protege tu cuenta de administrador con un código TOTP (Google Authenticator, Authy…).</p>

    <p v-if="message" class="auth-form__message">{{ message }}</p>

    <div class="admin-card" style="max-width: 460px">
      <template v-if="!setup">
        <button type="button" class="configurator__cta" :disabled="busy" @click="enable">
          {{ busy ? 'Generando…' : 'Activar 2FA' }}
        </button>
        <button type="button" class="admin-logout" style="margin-left: 0.5rem" @click="disable">
          Desactivar 2FA
        </button>
      </template>

      <template v-else>
        <p>Escanea este secreto en tu app autenticadora y confirma con un código:</p>
        <p><code>{{ setup.secret }}</code></p>
        <p class="payment__note" style="word-break: break-all">{{ setup.otpauth_url }}</p>

        <label>Código
          <input v-model="code" inputmode="numeric" maxlength="6" placeholder="123456" />
        </label>
        <p v-if="error" class="auth-form__error">{{ error }}</p>
        <button type="button" class="configurator__cta" @click="confirm">Confirmar</button>
      </template>
    </div>
  </section>
</template>
