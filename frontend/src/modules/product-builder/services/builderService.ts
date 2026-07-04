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

  /**
   * Vista previa tokenizada: configuración completa del producto (aunque esté
   * en borrador). El token temporal emitido desde el admin es la credencial.
   */
  preview(token: string): Promise<ConfigurableProduct> {
    return request<Wrapped<ConfigurableProduct>>({
      url: `/product-builder/preview/${token}`,
      method: 'GET',
    }).then((r) => r.data)
  },

  /** Authoritative server-side price + visibility for a set of selections. */
  quote(slug: string, selections: Selections, previewToken?: string): Promise<Quote> {
    return request<Wrapped<Quote>>({
      url: `/product-builder/products/${slug}/quote`,
      method: 'POST',
      data: { selections, ...(previewToken ? { preview_token: previewToken } : {}) },
    }).then((r) => r.data)
  },
}
