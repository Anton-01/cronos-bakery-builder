import { request } from '@/services/http'
import type { Address, AddressLabel, Branch, Cart, CheckoutPayload, Order } from '../types'

interface Wrapped<T> {
  data: T
}

interface Paginated<T> {
  data: T[]
}

export interface AddressPayload {
  label: AddressLabel
  recipient_name: string
  phone?: string
  line1: string
  line2?: string
  city: string
  state?: string
  country?: string
  notes?: string
  is_default?: boolean
}

/**
 * Transport for cart, addresses, branches, checkout and order history. Every
 * endpoint (except branches) requires an authenticated customer.
 */
export const orderService = {
  // --- Cart ----------------------------------------------------------------
  cart(): Promise<Cart> {
    return request<Wrapped<Cart>>({ url: '/cart', method: 'GET' }).then((r) => r.data)
  },

  addItem(productSlug: string, selections: Record<string, unknown>, quantity = 1): Promise<Cart> {
    return request<Wrapped<Cart>>({
      url: '/cart/items',
      method: 'POST',
      data: { product_slug: productSlug, selections, quantity },
    }).then((r) => r.data)
  },

  updateItem(itemId: string, quantity: number): Promise<Cart> {
    return request<Wrapped<Cart>>({
      url: `/cart/items/${itemId}`,
      method: 'PUT',
      data: { quantity },
    }).then((r) => r.data)
  },

  removeItem(itemId: string): Promise<Cart> {
    return request<Wrapped<Cart>>({ url: `/cart/items/${itemId}`, method: 'DELETE' }).then((r) => r.data)
  },

  // --- Addresses -----------------------------------------------------------
  addresses(): Promise<Address[]> {
    return request<Paginated<Address>>({ url: '/addresses', method: 'GET' }).then((r) => r.data)
  },

  createAddress(payload: AddressPayload): Promise<Address> {
    return request<Wrapped<Address>>({ url: '/addresses', method: 'POST', data: payload }).then((r) => r.data)
  },

  // --- Branches ------------------------------------------------------------
  branches(): Promise<Branch[]> {
    return request<Paginated<Branch>>({ url: '/branches', method: 'GET' }).then((r) => r.data)
  },

  // --- Checkout + orders ---------------------------------------------------
  checkout(payload: CheckoutPayload): Promise<Order> {
    return request<Wrapped<Order>>({ url: '/checkout', method: 'POST', data: payload }).then((r) => r.data)
  },

  orders(): Promise<Order[]> {
    return request<Paginated<Order>>({ url: '/orders', method: 'GET' }).then((r) => r.data)
  },

  order(id: string): Promise<Order> {
    return request<Wrapped<Order>>({ url: `/orders/${id}`, method: 'GET' }).then((r) => r.data)
  },
}
