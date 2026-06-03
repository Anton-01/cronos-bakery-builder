import { defineStore } from 'pinia'
import { orderService } from '../services/orderService'
import type { Cart, CartItem, Money } from '../types'

export interface LocalCartItem {
  localId: string
  product_slug: string
  product_name: string
  configuration: { selections: Record<string, string | string[]>; price: { items: { label: string; delta: number }[] } }
  unit_price: Money
  quantity: number
  line_total: Money
}

interface CartState {
  cart: Cart | null
  localItems: LocalCartItem[]
  loading: boolean
  syncing: boolean
}

function loadLocal(): LocalCartItem[] {
  try {
    return JSON.parse(localStorage.getItem('local_cart') ?? '[]')
  } catch {
    return []
  }
}

function saveLocal(items: LocalCartItem[]): void {
  localStorage.setItem('local_cart', JSON.stringify(items))
}

export const useCartStore = defineStore('cart', {
  state: (): CartState => ({
    cart: null,
    localItems: loadLocal(),
    loading: false,
    syncing: false,
  }),

  getters: {
    isServerCart: (state): boolean => state.cart !== null,

    itemCount(state): number {
      if (state.cart) return state.cart.item_count
      return state.localItems.reduce((sum, i) => sum + i.quantity, 0)
    },

    items(state): (CartItem | LocalCartItem)[] {
      if (state.cart) return state.cart.items
      return state.localItems
    },

    subtotal(state): Money | null {
      if (state.cart) return state.cart.summary.subtotal
      if (state.localItems.length === 0) return null
      const total = state.localItems.reduce((sum, i) => sum + i.line_total.amount, 0)
      const currency = state.localItems[0]?.line_total.currency ?? 'CRC'
      return { amount: total, currency }
    },

    total(state): Money | null {
      if (state.cart) return state.cart.summary.total
      return this.subtotal
    },
  },

  actions: {
    async load(): Promise<void> {
      this.loading = true
      try {
        this.cart = await orderService.cart()
      } catch {
        // Not authenticated, use local cart
      } finally {
        this.loading = false
      }
    },

    addLocal(item: Omit<LocalCartItem, 'localId'>): void {
      const localId = `local_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`
      this.localItems.push({ ...item, localId })
      saveLocal(this.localItems)
    },

    async add(productSlug: string, selections: Record<string, unknown>, quantity = 1): Promise<void> {
      this.cart = await orderService.addItem(productSlug, selections, quantity)
    },

    updateLocalQuantity(localId: string, quantity: number): void {
      const item = this.localItems.find((i) => i.localId === localId)
      if (!item) return
      item.quantity = Math.max(1, quantity)
      item.line_total = {
        amount: item.unit_price.amount * item.quantity,
        currency: item.unit_price.currency,
      }
      saveLocal(this.localItems)
    },

    async updateQuantity(itemId: string, quantity: number): Promise<void> {
      if (itemId.startsWith('local_')) {
        this.updateLocalQuantity(itemId, quantity)
        return
      }
      this.cart = await orderService.updateItem(itemId, quantity)
    },

    removeLocal(localId: string): void {
      this.localItems = this.localItems.filter((i) => i.localId !== localId)
      saveLocal(this.localItems)
    },

    async remove(itemId: string): Promise<void> {
      if (itemId.startsWith('local_')) {
        this.removeLocal(itemId)
        return
      }
      this.cart = await orderService.removeItem(itemId)
    },

    async syncToServer(): Promise<void> {
      if (this.localItems.length === 0) return
      this.syncing = true
      try {
        for (const item of this.localItems) {
          await orderService.addItem(item.product_slug, item.configuration.selections, item.quantity)
        }
        this.localItems = []
        saveLocal([])
        this.cart = await orderService.cart()
      } finally {
        this.syncing = false
      }
    },

    clearLocal(): void {
      this.localItems = []
      saveLocal([])
    },

    reset(): void {
      this.cart = null
      this.localItems = []
      saveLocal([])
    },
  },
})
