import { ref } from 'vue'
import type { ValidationErrors } from '@/modules/cms/types'

const sudoRequired = ref(false)
const sudoPassword = ref('')
const sudoLoading = ref(false)
const sudoError = ref('')

let pendingAction: (() => Promise<unknown>) | null = null
let resolveAction: ((value: unknown) => void) | null = null
let rejectAction: ((reason?: unknown) => void) | null = null

export function useSudo() {
  function withSudo<T>(action: () => Promise<T>): Promise<T> {
    return new Promise<T>((resolve, reject) => {
      pendingAction = action as () => Promise<unknown>
      resolveAction = resolve as (value: unknown) => void
      rejectAction = reject
      sudoRequired.value = true
      sudoPassword.value = ''
      sudoError.value = ''
    })
  }

  async function confirmSudo() {
    if (!pendingAction || !resolveAction || !rejectAction) return

    sudoLoading.value = true
    sudoError.value = ''

    try {
      const action = pendingAction
      const resolve = resolveAction
      pendingAction = null
      resolveAction = null
      rejectAction = null
      sudoRequired.value = false

      const result = await action()
      resolve(result)
    } catch (err: unknown) {
      const axiosErr = err as { response?: { status?: number; data?: { message?: string } } }
      if (axiosErr?.response?.status === 403 && axiosErr?.response?.data?.message) {
        sudoError.value = axiosErr.response.data.message
        sudoRequired.value = true
        pendingAction = pendingAction
        resolveAction = resolveAction
        rejectAction = rejectAction
      } else {
        rejectAction?.(err)
      }
    } finally {
      sudoLoading.value = false
    }
  }

  function cancelSudo() {
    sudoRequired.value = false
    sudoPassword.value = ''
    sudoError.value = ''
    rejectAction?.(new Error('Sudo cancelled'))
    pendingAction = null
    resolveAction = null
    rejectAction = null
  }

  return {
    sudoRequired,
    sudoPassword,
    sudoLoading,
    sudoError,
    withSudo,
    confirmSudo,
    cancelSudo,
  }
}
