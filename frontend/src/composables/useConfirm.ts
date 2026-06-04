import { ref } from 'vue'
import type { ConfirmAction } from '@/components/ConfirmDialog.vue'

const visible = ref(false)
const title = ref('')
const message = ref('')
const action = ref<ConfirmAction>('warning')
const confirmText = ref('Confirmar')
const cancelText = ref('Cancelar')

let resolvePromise: ((value: boolean) => void) | null = null

export function useConfirm() {
  function confirm(opts: {
    title?: string
    message?: string
    action?: ConfirmAction
    confirmText?: string
    cancelText?: string
  }): Promise<boolean> {
    title.value = opts.title ?? '¿Estás seguro?'
    message.value = opts.message ?? ''
    action.value = opts.action ?? 'warning'
    confirmText.value = opts.confirmText ?? 'Confirmar'
    cancelText.value = opts.cancelText ?? 'Cancelar'
    visible.value = true

    return new Promise((resolve) => {
      resolvePromise = resolve
    })
  }

  function handleConfirm() {
    visible.value = false
    resolvePromise?.(true)
    resolvePromise = null
  }

  function handleCancel() {
    visible.value = false
    resolvePromise?.(false)
    resolvePromise = null
  }

  return {
    visible,
    title,
    message,
    action,
    confirmText,
    cancelText,
    confirm,
    handleConfirm,
    handleCancel,
  }
}
