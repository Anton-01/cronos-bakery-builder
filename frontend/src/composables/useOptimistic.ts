import { useToast } from './useToast'

export function useOptimistic() {
  const { error } = useToast()

  async function optimisticUpdate<T>(
    list: { value: T[] },
    identify: (item: T) => boolean,
    patch: Partial<T>,
    apiCall: () => Promise<T>,
  ): Promise<T | null> {
    const idx = list.value.findIndex(identify)
    if (idx === -1) return null

    const snapshot = { ...list.value[idx] }
    list.value[idx] = { ...list.value[idx], ...patch }

    try {
      const updated = await apiCall()
      list.value[idx] = updated
      return updated
    } catch (err) {
      list.value[idx] = snapshot
      error('La operación falló. Los cambios fueron revertidos.')
      throw err
    }
  }

  async function optimisticRemove<T>(
    list: { value: T[] },
    identify: (item: T) => boolean,
    apiCall: () => Promise<void>,
  ): Promise<void> {
    const idx = list.value.findIndex(identify)
    if (idx === -1) return

    const snapshot = list.value[idx]
    const position = idx
    list.value.splice(idx, 1)

    try {
      await apiCall()
    } catch (err) {
      list.value.splice(position, 0, snapshot)
      error('No se pudo eliminar. El cambio fue revertido.')
      throw err
    }
  }

  return { optimisticUpdate, optimisticRemove }
}
