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
  | 'products'

export type PageStatus = 'draft' | 'published' | 'archived'

export type PageType = 'home' | 'about' | 'contact' | 'faq' | 'policies' | 'blog' | 'landing'

/** A brand (tenant) owning its own CMS content. */
export interface Brand {
  id: number
  name: string
  slug: string
  domain: string | null
  is_active: boolean
}

// --- Typed block payloads ----------------------------------------------------
// Mirror of the backend's BlockRules: the JSONB `data` stored per block type.

export interface HeroBlockConfig {
  heading: string
  subheading?: string | null
  image?: string | null
  cta_label?: string | null
  cta_url?: string | null
}

export interface BannerBlockConfig {
  image: string
  link?: string | null
  alt?: string | null
}

export interface GalleryImage {
  url: string
  caption?: string | null
}

export interface GalleryBlockConfig {
  title?: string | null
  images: GalleryImage[]
}

export interface CardItem {
  title: string
  text?: string | null
  image?: string | null
}

export interface CardsBlockConfig {
  title?: string | null
  items: CardItem[]
}

export interface TextBlockConfig {
  body: string
}

export interface VideoBlockConfig {
  url: string
  title?: string | null
  autoplay?: boolean
}

export interface CtaBlockConfig {
  heading: string
  text?: string | null
  cta_label: string
  cta_url: string
}

export interface FaqItem {
  question: string
  answer: string
}

export interface FaqBlockConfig {
  title?: string | null
  items: FaqItem[]
}

export interface TestimonialItem {
  author: string
  quote: string
}

export interface TestimonialsBlockConfig {
  title?: string | null
  items: TestimonialItem[]
}

export type ProductsSource = 'latest' | 'featured' | 'category' | 'manual'

/** Dynamic bakery-products block: renders live catalog products. */
export interface ProductsBlockConfig {
  title?: string | null
  source: ProductsSource
  category_slug?: string | null
  product_ids?: string[]
  limit?: number
  show_price?: boolean
}

export interface BlockConfigMap {
  hero: HeroBlockConfig
  banner: BannerBlockConfig
  gallery: GalleryBlockConfig
  cards: CardsBlockConfig
  text: TextBlockConfig
  video: VideoBlockConfig
  cta: CtaBlockConfig
  faq: FaqBlockConfig
  testimonials: TestimonialsBlockConfig
  products: ProductsBlockConfig
}

export type BlockConfig = BlockConfigMap[BlockType]

/** A rendered page-builder block. `config` is the type-specific payload. */
export interface PageBlock {
  id: number
  type: BlockType
  config: Record<string, unknown>
  position: number
  is_active: boolean
  section_id: number | null
}

export interface PageSeo {
  meta_title: string
  meta_description: string | null
}

export interface CmsPage {
  id: number
  brand_id: number
  brand?: Brand
  title: string
  slug: string
  type: PageType
  status: PageStatus
  content: string | null
  settings: Record<string, unknown> | null
  seo: PageSeo
  published_at: string | null
  blocks: PageBlock[]
  updated_at: string | null
}

/** Payload for creating/updating a page from the admin. */
export interface PagePayload {
  brand_id?: number
  title: string
  slug?: string | null
  type: PageType
  meta_title?: string | null
  meta_description?: string | null
  content?: string | null
  settings?: Record<string, unknown> | null
  status: PageStatus
  blocks?: PageBlockPayload[]
}

/** One block inside a bulk save: `id` present = update, absent = create. */
export interface PageBlockPayload {
  id?: number | null
  section_id?: number | null
  type?: BlockType | null
  data?: Record<string, unknown> | null
  is_active?: boolean
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
  id: number
  name: string
  logo: string | null
  favicon: string | null
  colors: ThemeColors
  fonts: ThemeFonts
  footer: ThemeFooter | null
  is_active: boolean
}

export interface MenuItemNode {
  id: number
  label: string
  url: string | null
  target: string
  position: number
  is_active: boolean
  parent_id: number | null
  children: MenuItemNode[]
}

export interface Menu {
  id: number
  name: string
  location: string
  is_active: boolean
  items: MenuItemNode[]
}

export interface Banner {
  id: number
  title: string
  image: string
  link: string | null
  placement: string
  sort_order: number
}

// --- Content Versioning & Workflows ----------------------------------------

export type ContentStatus = 'draft' | 'pending_review' | 'published' | 'scheduled' | 'archived'

export interface ContentVersion {
  id: number
  versionable_type: string
  versionable_id: number
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
  id: number
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
  id: number
  original_name: string
  disk: string
  path: string
  mime_type: string
  size: number
  transformations: Record<string, unknown> | null
  processing_status: 'pending' | 'processing' | 'completed' | 'failed'
  storage_provider_id: number | null
  uploaded_by: number
  url?: string
  created_at: string
}

export interface StorageProvider {
  id: number
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

