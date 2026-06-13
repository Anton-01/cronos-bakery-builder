<script setup lang="ts">
import { useSudo } from '@/composables/useSudo'
import { setSudoPassword, clearSudoPassword } from '@/services/http'

const {
  sudoRequired,
  sudoPassword,
  sudoLoading,
  sudoError,
  confirmSudo,
  cancelSudo,
} = useSudo()

async function handleConfirm() {
  setSudoPassword(sudoPassword.value)
  await confirmSudo()
  clearSudoPassword()
}

function handleCancel() {
  clearSudoPassword()
  cancelSudo()
}
</script>

<template>
  <Transition name="sudo-overlay">
    <div v-if="sudoRequired" class="sudo-overlay" @click.self="handleCancel">
      <Transition name="sudo-dialog" appear>
        <div class="sudo-dialog" role="dialog" aria-modal="true" aria-labelledby="sudo-title">
          <div class="sudo-dialog__icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="32" height="32">
              <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>

          <h2 id="sudo-title" class="sudo-dialog__title">Confirmar Identidad</h2>
          <p class="sudo-dialog__desc">
            Esta acción requiere verificación adicional. Ingresa tu contraseña para continuar.
          </p>

          <form @submit.prevent="handleConfirm" class="sudo-dialog__form">
            <div class="sudo-dialog__field">
              <label for="sudo-password" class="sudo-dialog__label">Contraseña</label>
              <input
                id="sudo-password"
                v-model="sudoPassword"
                type="password"
                autocomplete="current-password"
                class="sudo-dialog__input"
                :class="{ 'sudo-dialog__input--error': sudoError }"
                placeholder="Ingresa tu contraseña"
                autofocus
              />
              <Transition name="sudo-error">
                <p v-if="sudoError" class="sudo-dialog__error">{{ sudoError }}</p>
              </Transition>
            </div>

            <div class="sudo-dialog__actions">
              <button
                type="button"
                class="sudo-dialog__btn sudo-dialog__btn--cancel"
                :disabled="sudoLoading"
                @click="handleCancel"
              >
                Cancelar
              </button>
              <button
                type="submit"
                class="sudo-dialog__btn sudo-dialog__btn--confirm"
                :disabled="sudoLoading || !sudoPassword"
              >
                <span v-if="sudoLoading" class="sudo-dialog__spinner"></span>
                <span v-else>Confirmar</span>
              </button>
            </div>
          </form>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
.sudo-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}
.sudo-dialog {
  background: #fff;
  border-radius: 16px;
  padding: 2rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  text-align: center;
}
.sudo-dialog__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #fef3c7;
  color: #d97706;
  margin-bottom: 1rem;
}
.sudo-dialog__title {
  margin: 0 0 0.5rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
}
.sudo-dialog__desc {
  margin: 0 0 1.5rem;
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.5;
}
.sudo-dialog__form {
  text-align: left;
}
.sudo-dialog__field {
  margin-bottom: 1.25rem;
}
.sudo-dialog__label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.375rem;
}
.sudo-dialog__input {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: border-color 0.15s, box-shadow 0.15s;
  outline: none;
  box-sizing: border-box;
}
.sudo-dialog__input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}
.sudo-dialog__input--error {
  border-color: #ef4444;
}
.sudo-dialog__input--error:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}
.sudo-dialog__error {
  margin: 0.375rem 0 0;
  font-size: 0.8125rem;
  color: #ef4444;
}
.sudo-dialog__actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}
.sudo-dialog__btn {
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.sudo-dialog__btn--cancel {
  background: #f3f4f6;
  color: #374151;
}
.sudo-dialog__btn--cancel:hover {
  background: #e5e7eb;
}
.sudo-dialog__btn--confirm {
  background: #6366f1;
  color: #fff;
}
.sudo-dialog__btn--confirm:hover:not(:disabled) {
  background: #4f46e5;
}
.sudo-dialog__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.sudo-dialog__spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Transitions */
.sudo-overlay-enter-active,
.sudo-overlay-leave-active {
  transition: opacity 0.2s ease;
}
.sudo-overlay-enter-from,
.sudo-overlay-leave-to {
  opacity: 0;
}
.sudo-dialog-enter-active {
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.sudo-dialog-leave-active {
  transition: all 0.15s ease-in;
}
.sudo-dialog-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}
.sudo-dialog-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
.sudo-error-enter-active {
  transition: all 0.2s ease;
}
.sudo-error-leave-active {
  transition: all 0.15s ease;
}
.sudo-error-enter-from,
.sudo-error-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
