export interface Money {
  amount: number
  currency: string
}

export interface Seo {
  meta_title: string
  meta_description: string | null
}

export interface ProductAttribute {
  attribute_code: string
  attribute_name: string
  label: string
  value: string
}

export interface CatalogProduct {
  id: string
  name: string
  slug: string
  url: string
  description: string | null
  image: string | null
  price: Money
  seo: Seo
  categories?: Array<{ id: string; name: string; slug: string }>
  collections?: Array<{ id: string; name: string; slug: string }>
  attributes?: ProductAttribute[]
  tags?: string[]
}

export interface Category {
  id: string
  name: string
  slug: string
  url: string
  description: string | null
  image: string | null
  seo: Seo
  children?: Category[]
}

export interface AttributeValueFacet {
  id: string
  label: string
  value: string
  metadata: Record<string, unknown> | null
}

export interface AttributeFacet {
  id: string
  name: string
  code: string
  type: 'select' | 'color'
  is_filterable: boolean
  values: AttributeValueFacet[]
}

export interface Facets {
  categories: Category[]
  collections: Array<{ id: string; name: string; slug: string }>
  attributes: AttributeFacet[]
  price: { min: number; max: number }
}

export interface Breadcrumb {
  label: string
  slug: string
  type: 'catalog' | 'category' | 'product'
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number; per_page: number }
  links: { next: string | null; prev: string | null }
}

/** Active filter state for catalog browsing. */
export interface FilterState {
  category?: string
  collection?: string
  price_min?: number
  price_max?: number
  attributes: Record<string, string[]>
  sort: string
  search?: string
  page: number
}
