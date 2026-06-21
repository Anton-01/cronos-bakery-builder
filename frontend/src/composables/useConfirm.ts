import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm'

export type ConfirmAction = 'delete' | 'activate' | 'deactivate' | 'warning' | 'info'

export function useConfirm() {
  const prime = usePrimeConfirm()

  function confirm(opts: {
    title?: string
    message?: string
    action?: ConfirmAction
    confirmText?: string
    cancelText?: string
  }): Promise<boolean> {
    const action = opts.action ?? 'warning'
    const severity = action === 'delete' ? 'danger' : action === 'activate' ? 'success' : 'warn'

    return new Promise((resolve) => {
      prime.require({
        header: opts.title ?? '¿Estás seguro?',
        message: opts.message ?? '',
        rejectProps: {
          label: opts.cancelText ?? 'Cancelar',
          severity: 'secondary',
          outlined: true,
        },
        acceptProps: {
          label: opts.confirmText ?? 'Confirmar',
          severity,
        },
        accept: () => resolve(true),
        reject: () => resolve(false),
      })
    })
  }

  return { confirm }
}
