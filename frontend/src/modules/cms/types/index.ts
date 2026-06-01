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

// --- Theme Builder ---------------------------------------------------------

export interface ThemeColors {
  primary: string
  secondary: string
  accent: string
  success: string
  warning: string
  danger: string
}

export interface ThemeFonts {
  heading: string
  body: string
  stylesheet?: string
}

export interface FooterColumn {
  title: string
  links: Array<{ label: string; url: string }>
}

export interface ThemeFooter {
  columns: FooterColumn[]
  copyright?: string
}

export interface Theme {
  id: string
  name: string
  logo: string | null
  favicon: string | null
  colors: ThemeColors
  fonts: ThemeFonts
  footer: ThemeFooter | null
  is_active: boolean
}

export interface MenuItemNode {
  id: string
  label: string
  url: string | null
  target: string
  position: number
  is_active: boolean
  parent_id: string | null
  children: MenuItemNode[]
}

export interface Menu {
  id: string
  name: string
  location: string
  is_active: boolean
  items: MenuItemNode[]
}

export interface Banner {
  id: string
  title: string
  image: string
  link: string | null
  placement: string
  sort_order: number
}

