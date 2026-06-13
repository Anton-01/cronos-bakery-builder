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

// --- Content Versioning & Workflows ----------------------------------------

export type ContentStatus = 'draft' | 'pending_review' | 'published' | 'scheduled' | 'archived'

export interface ContentVersion {
  id: string
  versionable_type: string
  versionable_id: string
  version_number: number
  payload_before: Record<string, unknown> | null
  payload_after: Record<string, unknown>
  status_before: string | null
  status_after: string
  change_summary: string | null
  author_id: number
  author_name?: string
  created_at: string
}

export interface ContentWorkflow {
  id: string
  from_status: ContentStatus
  to_status: ContentStatus
  requested_by: number
  approved_by: number | null
  comment: string | null
  scheduled_at: string | null
  created_at: string
  requester_name?: string
  approver_name?: string
}

// --- Media Library ---------------------------------------------------------

export interface MediaAsset {
  id: string
  original_name: string
  disk: string
  path: string
  mime_type: string
  size: number
  transformations: Record<string, unknown> | null
  processing_status: 'pending' | 'processing' | 'completed' | 'failed'
  storage_provider_id: string | null
  uploaded_by: number
  url?: string
  created_at: string
}

export interface StorageProvider {
  id: string
  name: string
  driver: 's3' | 'gcs' | 'azure'
  bucket: string
  region: string | null
  is_active: boolean
  is_default: boolean
}

// --- Cache -----------------------------------------------------------------

export interface CacheSetting {
  id: number
  tag: string
  ttl_seconds: number
  last_flushed_at: string | null
}

// --- Pagination ------------------------------------------------------------

export interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
  per_page: number
}

export interface ValidationErrors {
  [field: string]: string[]
}

