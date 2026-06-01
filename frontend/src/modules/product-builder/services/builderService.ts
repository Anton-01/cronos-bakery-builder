import { request } from '@/services/http'
import type { ConfigurableProduct, Quote, Selections } from '../types'

interface Wrapped<T> {
  data: T
}

/**
 * Transport for the public Product Builder configurator.
 */
export const builderService = {
  products(): Promise<ConfigurableProduct[]> {
    return request<Wrapped<ConfigurableProduct[]>>({
      url: '/product-builder/products',
      method: 'GET',
    }).then((r) => r.data)
  },

  product(slug: string): Promise<ConfigurableProduct> {
    return request<Wrapped<ConfigurableProduct>>({
      url: `/product-builder/products/${slug}`,
      method: 'GET',
    }).then((r) => r.data)
  },

  /** Authoritative server-side price + visibility for a set of selections. */
  quote(slug: string, selections: Selections): Promise<Quote> {
    return request<Wrapped<Quote>>({
      url: `/product-builder/products/${slug}/quote`,
      method: 'POST',
      data: { selections },
    }).then((r) => r.data)
  },
}
