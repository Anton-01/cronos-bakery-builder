import { useToast as usePrimeToast } from 'primevue/usetoast'

export function useToast() {
  const toast = usePrimeToast()

  return {
    success(message: string) {
      toast.add({ severity: 'success', summary: 'Éxito', detail: message, life: 3000 })
    },
    error(message: string) {
      toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
    },
    info(message: string) {
      toast.add({ severity: 'info', summary: 'Info', detail: message, life: 3000 })
    },
    warning(message: string) {
      toast.add({ severity: 'warn', summary: 'Aviso', detail: message, life: 4000 })
    },
  }
}
