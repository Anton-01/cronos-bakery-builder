export type BlockType =
  | 'hero'
  | 'banner'
  | 'gallery'
  | 'cards'
  | 'text'
  | 'video'
  | 'cta'
  | 'faq'
  | 'testimonials'

/** A rendered page-builder block. `config` is a free-form, type-specific payload. */
export interface PageBlock {
  id: string
  type: BlockType
  config: Record<string, unknown>
  position: number
  is_active: boolean
  section_id: string | null
}

export interface PageSeo {
  meta_title: string
  meta_description: string | null
}

export interface CmsPage {
  id: string
  title: string
  slug: string
  type: string
  status: string
  content: string | null
  seo: PageSeo
  published_at: string | null
  sections: PageBlock[]
}
