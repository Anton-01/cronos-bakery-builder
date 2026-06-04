<script setup lang="ts">
import { computed } from 'vue'

export type ConfirmAction = 'delete' | 'activate' | 'deactivate' | 'warning' | 'info'

const props = withDefaults(defineProps<{
  visible: boolean
  title?: string
  message?: string
  action?: ConfirmAction
  confirmText?: string
  cancelText?: string
}>(), {
  title: '¿Estás seguro?',
  message: '',
  action: 'warning',
  confirmText: 'Confirmar',
  cancelText: 'Cancelar',
})

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()

const iconColor = computed(() => {
  const map: Record<ConfirmAction, string> = {
    delete: 'var(--admin-error, #fa896b)',
    activate: 'var(--admin-success, #13deb9)',
    deactivate: 'var(--admin-warning, #ffae1f)',
    warning: 'var(--admin-warning, #ffae1f)',
    info: 'var(--admin-info, #539bff)',
  }
  return map[props.action]
})

const iconBg = computed(() => {
  const map: Record<ConfirmAction, string> = {
    delete: 'var(--admin-error-light, #fdede8)',
    activate: 'var(--admin-success-light, #e6fffa)',
    deactivate: 'var(--admin-warning-light, #fef5e5)',
    warning: 'var(--admin-warning-light, #fef5e5)',
    info: 'var(--admin-info-light, #ebf3fe)',
  }
  return map[props.action]
})

const confirmBtnClass = computed(() => {
  if (props.action === 'delete') return 'confirm-dialog__btn--danger'
  if (props.action === 'activate') return 'confirm-dialog__btn--success'
  return 'confirm-dialog__btn--primary'
})
</script>

<template>
  <Teleport to="body">
    <Transition name="confirm-fade">
      <div v-if="visible" class="confirm-dialog__overlay" @click.self="emit('cancel')">
        <div class="confirm-dialog__card">
          <div class="confirm-dialog__icon" :style="{ background: iconBg }">
            <!-- Delete / trash -->
            <svg v-if="action === 'delete'" :style="{ color: iconColor }" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
            </svg>
            <!-- Activate / check-circle -->
            <svg v-else-if="action === 'activate'" :style="{ color: iconColor }" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" />
            </svg>
            <!-- Deactivate / x-circle -->
            <svg v-else-if="action === 'deactivate'" :style="{ color: iconColor }" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" /><line x1="15" y1="9" x2="9" y2="15" /><line x1="9" y1="9" x2="15" y2="15" />
            </svg>
            <!-- Warning / alert-triangle -->
            <svg v-else-if="action === 'warning'" :style="{ color: iconColor }" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
            <!-- Info -->
            <svg v-else :style="{ color: iconColor }" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" />
            </svg>
          </div>

          <h3 class="confirm-dialog__title">{{ title }}</h3>
          <p v-if="message" class="confirm-dialog__message">{{ message }}</p>

          <div class="confirm-dialog__actions">
            <button class="confirm-dialog__btn confirm-dialog__btn--cancel" @click="emit('cancel')">
              {{ cancelText }}
            </button>
            <button class="confirm-dialog__btn" :class="confirmBtnClass" @click="emit('confirm')">
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.confirm-dialog__overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.confirm-dialog__card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  padding: 2rem 2.5rem;
  max-width: 400px;
  width: 90vw;
  text-align: center;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}

.confirm-dialog__icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
}

.confirm-dialog__title {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--admin-text, #2a3547);
  margin: 0 0 0.5rem;
}

.confirm-dialog__message {
  font-size: 0.9rem;
  color: var(--admin-text-secondary, #5a6a85);
  margin: 0 0 1.5rem;
  line-height: 1.5;
}

.confirm-dialog__actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
}

.confirm-dialog__btn {
  padding: 0.6rem 1.5rem;
  border-radius: 8px;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.15s ease;
}

.confirm-dialog__btn--cancel {
  background: #f0f2f5;
  color: var(--admin-text-secondary, #5a6a85);
}

.confirm-dialog__btn--cancel:hover {
  background: #e3e6ea;
}

.confirm-dialog__btn--danger {
  background: var(--admin-error, #fa896b);
  color: #fff;
}

.confirm-dialog__btn--danger:hover {
  background: #e8705a;
}

.confirm-dialog__btn--success {
  background: var(--admin-success, #13deb9);
  color: #fff;
}

.confirm-dialog__btn--success:hover {
  background: #0ec4a5;
}

.confirm-dialog__btn--primary {
  background: var(--admin-primary, #5d87ff);
  color: #fff;
}

.confirm-dialog__btn--primary:hover {
  background: #4a74e8;
}

/* Transitions */
.confirm-fade-enter-active,
.confirm-fade-leave-active {
  transition: opacity 0.2s ease;
}

.confirm-fade-enter-active .confirm-dialog__card,
.confirm-fade-leave-active .confirm-dialog__card {
  transition: transform 0.2s ease;
}

.confirm-fade-enter-from,
.confirm-fade-leave-to {
  opacity: 0;
}

.confirm-fade-enter-from .confirm-dialog__card {
  transform: scale(0.95);
}

.confirm-fade-leave-to .confirm-dialog__card {
  transform: scale(0.95);
}
</style>
