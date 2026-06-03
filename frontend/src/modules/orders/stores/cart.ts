import { defineStore } from 'pinia'

import { orderService } from '../services/orderService'
import type { Cart } from '../types'

interface CartState {
  cart: Cart | null
  loading: boolean
}

/**
 * Persistent cart store. The cart lives server-side (per authenticated
 * customer); this store mirrors it for the UI and badge count.
 */
export const useCartStore = defineStore('cart', {
  state: (): CartState => ({ cart: null, loading: false }),

  getters: {
    itemCount: (state): number => state.cart?.item_count ?? 0,
    items: (state) => state.cart?.items ?? [],
    subtotal: (state) => state.cart?.summary.subtotal ?? null,
  },

  actions: {
    async load(): Promise<void> {
      this.loading = true
      try {
        this.cart = await orderService.cart()
      } finally {
        this.loading = false
      }
    },

    async add(productSlug: string, selections: Record<string, unknown>, quantity = 1): Promise<void> {
      this.cart = await orderService.addItem(productSlug, selections, quantity)
    },

    async updateQuantity(itemId: string, quantity: number): Promise<void> {
      this.cart = await orderService.updateItem(itemId, quantity)
    },

    async remove(itemId: string): Promise<void> {
      this.cart = await orderService.removeItem(itemId)
    },

    reset(): void {
      this.cart = null
    },
  },
})
