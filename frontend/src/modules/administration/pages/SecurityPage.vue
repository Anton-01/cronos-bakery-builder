<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Card from 'primevue/card'
import Message from 'primevue/message'

import { adminPanelService, type TwoFactorSetup } from '../services/adminPanelService'

const setup = ref<TwoFactorSetup | null>(null)
const code = ref('')
const message = ref<string | null>(null)
const errorMsg = ref<string | null>(null)
const busy = ref(false)

async function enable(): Promise<void> {
  busy.value = true
  errorMsg.value = null
  try {
    setup.value = await adminPanelService.enableTwoFactor()
  } finally {
    busy.value = false
  }
}

async function confirmCode(): Promise<void> {
  errorMsg.value = null
  try {
    const res = await adminPanelService.confirmTwoFactor(code.value)
    message.value = res.message
    setup.value = null
    code.value = ''
  } catch {
    errorMsg.value = 'Código inválido. Intenta de nuevo.'
  }
}

async function disable(): Promise<void> {
  const res = await adminPanelService.disableTwoFactor()
  message.value = res.message
  setup.value = null
}
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Seguridad</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Seguridad</div>
      </div>
    </div>

    <Card style="max-width:500px;">
      <template #title>Autenticación en dos pasos (2FA)</template>
      <template #subtitle>Protege tu cuenta con un código TOTP (Google Authenticator, Authy…)</template>
      <template #content>
        <Message v-if="message" severity="success" :closable="false" style="margin-bottom:1rem;">{{ message }}</Message>

        <template v-if="!setup">
          <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <Button label="Activar 2FA" :loading="busy" @click="enable" />
            <Button label="Desactivar 2FA" severity="danger" outlined @click="disable" />
          </div>
        </template>

        <template v-else>
          <p style="margin-bottom:0.75rem; font-size:0.875rem;">
            Escanea este secreto en tu app autenticadora y confirma con un código:
          </p>
          <code style="display:block; background:var(--admin-bg); padding:0.5rem 0.75rem; border-radius:6px; margin-bottom:0.75rem; font-size:0.9rem; word-break:break-all;">
            {{ setup.secret }}
          </code>
          <p style="font-size:0.75rem; color:var(--admin-text-muted); word-break:break-all; margin-bottom:1rem;">
            {{ setup.otpauth_url }}
          </p>

          <div class="tfa-field">
            <label>Código</label>
            <InputText v-model="code" inputmode="numeric" maxlength="6" placeholder="123456" fluid />
          </div>

          <Message v-if="errorMsg" severity="error" :closable="false" style="margin-bottom:0.75rem;">{{ errorMsg }}</Message>

          <Button label="Confirmar" @click="confirmCode" />
        </template>
      </template>
    </Card>
  </div>
</template>

<style scoped>
.tfa-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.tfa-field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
</style>
