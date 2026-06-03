import { request } from '@/services/http'
import type { Breadcrumb, CatalogProduct, Category, Facets, FilterState, Paginated } from '../types'

interface Wrapped<T> {
  data: T
}

/** Serialise filter state into catalog query parameters. */
export function toQueryParams(filter: Partial<FilterState>): Record<string, unknown> {
  const params: Record<string, unknown> = {}
  if (filter.category) params.category = filter.category
  if (filter.collection) params.collection = filter.collection
  if (filter.search) params.search = filter.search
  if (filter.sort) params.sort = filter.sort
  if (filter.page) params.page = filter.page
  if (filter.price_min != null) params.price_min = filter.price_min
  if (filter.price_max != null) params.price_max = filter.price_max
  for (const [code, values] of Object.entries(filter.attributes ?? {})) {
    if (values.length) params[`attributes[${code}]`] = values.join(',')
  }
  return params
}

export const catalogService = {
  browse(filter: Partial<FilterState>): Promise<Paginated<CatalogProduct>> {
    return request<Paginated<CatalogProduct>>({
      url: '/catalog/browse',
      method: 'GET',
      params: toQueryParams(filter),
    })
  },

  facets(): Promise<Facets> {
    return request<Wrapped<Facets>>({ url: '/catalog/facets', method: 'GET' }).then((r) => r.data)
  },

  category(
    slug: string,
    filter: Partial<FilterState>,
  ): Promise<{ category: Category; breadcrumbs: Breadcrumb[]; products: Paginated<CatalogProduct> }> {
    return request<Wrapped<{ category: Category; breadcrumbs: Breadcrumb[]; products: Paginated<CatalogProduct> }>>({
      url: `/catalog/categories/${slug}`,
      method: 'GET',
      params: toQueryParams(filter),
    }).then((r) => r.data)
  },

  product(slug: string): Promise<{ product: CatalogProduct; breadcrumbs: Breadcrumb[] }> {
    return request<Wrapped<{ product: CatalogProduct; breadcrumbs: Breadcrumb[] }>>({
      url: `/catalog/detail/${slug}`,
      method: 'GET',
    }).then((r) => r.data)
  },
}
