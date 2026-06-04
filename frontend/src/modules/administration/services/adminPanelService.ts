import { request } from '@/services/http'

export interface DashboardMetrics {
  range: { from: string; to: string }
  sales: { revenue: number; paid_payments: number; currency: string; average_order_value: number }
  orders: { total: number; by_status: Record<string, number> }
  production: { in_production: number; ready: number; upcoming_pickups: number }
  conversion: { carts: number; orders: number; cart_to_order_rate: number; order_to_paid_rate: number }
  customers: { total: number; new: number; with_orders: number }
}

export interface AuditLog {
  id: string
  admin_name: string | null
  method: string
  path: string
  status_code: number
  ip_address: string | null
  created_at: string
}

export interface AdminUser {
  id: number
  name: string
  email: string
  phone: string | null
  roles: string[]
}

export interface RoleDefinition {
  name: string
  permissions: string[]
}

export interface TwoFactorSetup {
  secret: string
  otpauth_url: string
}

// --- CMS types ---
export interface CmsSection { id: string; key: string; name: string; position: number }
export interface CmsPage { id: string; title: string; slug: string; is_published: boolean; section_id: string }
export interface CmsBlock { id: string; type: string; content: Record<string, unknown>; position: number }

// --- Theme types ---
export interface Theme { id: string; name: string; is_active: boolean; settings: Record<string, unknown> }
export interface CmsMenu { id: string; name: string; location: string; items: CmsMenuItem[] }
export interface CmsMenuItem { id: string; label: string; url: string; position: number; children: CmsMenuItem[] }
export interface CmsBanner { id: string; placement: string; title: string; image: string | null; url: string | null; is_active: boolean }

// --- Catalog types ---
export interface AdminCategory { id: string; name: string; slug: string; position: number; parent_id: string | null; products_count?: number }
export interface AdminCollection { id: string; name: string; slug: string }
export interface AdminAttribute { id: string; name: string; code: string; type: string; values: AdminAttributeValue[] }
export interface AdminAttributeValue { id: string; label: string; value: string; position: number; metadata: Record<string, unknown> | null }

// --- Product Builder types ---
export interface ProductImage {
  id: string
  path: string
  name: string | null
  alt_text: string | null
  position: number
}

export interface AdminProduct {
  id: string
  name: string
  slug: string
  description: string | null
  image: string | null
  is_active: boolean
  base_price: { amount: number; currency: string }
  options_count?: number
  gallery?: ProductImage[]
  categories?: string[]
  tags?: string[]
}

export interface AdminProductDetail extends AdminProduct {
  gallery: ProductImage[]
  options?: PbOption[]
}

export type PbOptionType = 'select' | 'radio' | 'checkbox' | 'color' | 'image' | 'text' | 'textarea'

export interface PbOptionValue {
  id: string
  label: string
  value: string
  price_modifier_type: 'none' | 'add' | 'subtract' | 'set'
  price_modifier_amount: number
  metadata: Record<string, unknown> | null
  is_default: boolean
  position: number
}

export interface PbOption {
  id: string
  product_id: string
  key: string
  label: string
  type: PbOptionType
  help_text: string | null
  is_required: boolean
  position: number
  config: Record<string, unknown> | null
  values: PbOptionValue[]
}

// --- Orders types ---
export interface AdminOrder {
  id: string; number: string; status: string; status_label: string
  user_name?: string; user_email?: string
  totals: { subtotal: number; total: number; currency: string }
  items: { product_name: string; quantity: number; line_total: number }[]
  fulfillment: { type: string; pickup_date?: string; pickup_time?: string }
  placed_at: string | null; updated_at?: string
}

// --- Payments types ---
export interface PaymentGateway { id: string; name: string; driver: string; is_active: boolean; settings: Record<string, unknown> }
export interface AdminPayment { id: string; order_id: string; order_number?: string; gateway: string; status: string; amount: number; currency: string; created_at: string }

// --- Calendar types ---
export interface CalendarSchedule { id: string; day_of_week: number; opens_at: string; closes_at: string; is_active: boolean }
export interface DeliverySlot { id: string; label: string; starts_at: string; ends_at: string; max_orders: number }
export interface Holiday { id: string; date: string; name: string }
export interface Blackout { id: string; from: string; to: string; reason: string }

// --- Notifications types ---
export interface EmailTemplate { id: string; key: string; subject: string; body: string; is_active: boolean }
export interface ReminderRule { id: string; event: string; delay_minutes: number; template_id: string; is_active: boolean }
export interface NotificationLog { id: string; channel: string; recipient: string; subject: string; status: string; sent_at: string }

interface Wrapped<T> { data: T }
interface Paginated<T> { data: T[]; meta?: { current_page: number; last_page: number; total: number } }

export const adminPanelService = {
  // --- Dashboard ---
  dashboard(): Promise<DashboardMetrics> {
    return request<Wrapped<DashboardMetrics>>({ url: '/admin/dashboard', method: 'GET' }).then((r) => r.data)
  },

  metrics(): Promise<Record<string, unknown>> {
    return request<Wrapped<Record<string, unknown>>>({ url: '/admin/metrics', method: 'GET' }).then((r) => r.data)
  },

  // --- 2FA ---
  enableTwoFactor(): Promise<TwoFactorSetup> {
    return request<Wrapped<TwoFactorSetup>>({ url: '/admin/2fa/enable', method: 'POST' }).then((r) => r.data)
  },

  confirmTwoFactor(code: string): Promise<{ message: string }> {
    return request<{ message: string }>({ url: '/admin/2fa/confirm', method: 'POST', data: { code } })
  },

  disableTwoFactor(): Promise<{ message: string }> {
    return request<{ message: string }>({ url: '/admin/2fa/disable', method: 'POST' })
  },

  // --- Users & Roles ---
  auditLogs(): Promise<Paginated<AuditLog>> {
    return request<Paginated<AuditLog>>({ url: '/admin/audit-logs', method: 'GET' })
  },

  users(search = ''): Promise<Paginated<AdminUser>> {
    return request<Paginated<AdminUser>>({ url: '/admin/users', method: 'GET', params: { search } })
  },

  roles(): Promise<RoleDefinition[]> {
    return request<Wrapped<RoleDefinition[]>>({ url: '/admin/roles', method: 'GET' }).then((r) => r.data)
  },

  // --- CMS ---
  cmsSections(): Promise<CmsSection[]> {
    return request<Wrapped<CmsSection[]>>({ url: '/admin/cms/sections', method: 'GET' }).then((r) => r.data)
  },

  cmsPages(sectionId?: string): Promise<CmsPage[]> {
    return request<Wrapped<CmsPage[]>>({ url: '/admin/cms/pages', method: 'GET', params: sectionId ? { section_id: sectionId } : {} }).then((r) => r.data)
  },

  cmsCreatePage(data: Partial<CmsPage>): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({ url: '/admin/cms/pages', method: 'POST', data }).then((r) => r.data)
  },

  cmsUpdatePage(id: string, data: Partial<CmsPage>): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({ url: `/admin/cms/pages/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  cmsDeletePage(id: string): Promise<void> {
    return request({ url: `/admin/cms/pages/${id}`, method: 'DELETE' })
  },

  // --- Themes ---
  themes(): Promise<Theme[]> {
    return request<Wrapped<Theme[]>>({ url: '/admin/themes', method: 'GET' }).then((r) => r.data)
  },

  updateTheme(id: string, data: Partial<Theme>): Promise<Theme> {
    return request<Wrapped<Theme>>({ url: `/admin/themes/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  // --- Menus ---
  menus(): Promise<CmsMenu[]> {
    return request<Wrapped<CmsMenu[]>>({ url: '/admin/menus', method: 'GET' }).then((r) => r.data)
  },

  createMenu(data: { name: string; location: string }): Promise<CmsMenu> {
    return request<Wrapped<CmsMenu>>({ url: '/admin/menus', method: 'POST', data }).then((r) => r.data)
  },

  updateMenu(id: string, data: Partial<CmsMenu>): Promise<CmsMenu> {
    return request<Wrapped<CmsMenu>>({ url: `/admin/menus/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteMenu(id: string): Promise<void> {
    return request({ url: `/admin/menus/${id}`, method: 'DELETE' })
  },

  // --- Banners ---
  banners(): Promise<CmsBanner[]> {
    return request<Wrapped<CmsBanner[]>>({ url: '/admin/banners', method: 'GET' }).then((r) => r.data)
  },

  // --- Catalog: Categories ---
  categories(): Promise<AdminCategory[]> {
    return request<Wrapped<AdminCategory[]>>({ url: '/admin/catalog/categories', method: 'GET' }).then((r) => r.data)
  },

  createCategory(data: Partial<AdminCategory>): Promise<AdminCategory> {
    return request<Wrapped<AdminCategory>>({ url: '/admin/catalog/categories', method: 'POST', data }).then((r) => r.data)
  },

  updateCategory(id: string, data: Partial<AdminCategory>): Promise<AdminCategory> {
    return request<Wrapped<AdminCategory>>({ url: `/admin/catalog/categories/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteCategory(id: string): Promise<void> {
    return request({ url: `/admin/catalog/categories/${id}`, method: 'DELETE' })
  },

  // --- Catalog: Collections ---
  collections(): Promise<AdminCollection[]> {
    return request<Wrapped<AdminCollection[]>>({ url: '/admin/catalog/collections', method: 'GET' }).then((r) => r.data)
  },

  // --- Catalog: Attributes ---
  attributes(): Promise<AdminAttribute[]> {
    return request<Wrapped<AdminAttribute[]>>({ url: '/admin/catalog/attributes', method: 'GET' }).then((r) => r.data)
  },

  // --- Product Builder ---
  adminProducts(): Promise<AdminProduct[]> {
    return request<Wrapped<AdminProduct[]>>({ url: '/admin/product-builder/products', method: 'GET' }).then((r) => r.data)
  },

  createProduct(data: Partial<AdminProduct>): Promise<AdminProduct> {
    return request<Wrapped<AdminProduct>>({ url: '/admin/product-builder/products', method: 'POST', data }).then((r) => r.data)
  },

  updateProduct(id: string, data: Partial<AdminProduct>): Promise<AdminProduct> {
    return request<Wrapped<AdminProduct>>({ url: `/admin/product-builder/products/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  showProduct(id: string): Promise<AdminProductDetail> {
    return request<Wrapped<AdminProductDetail>>({ url: `/admin/product-builder/products/${id}`, method: 'GET' }).then((r) => r.data)
  },

  deleteProduct(id: string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${id}`, method: 'DELETE' })
  },

  uploadProductImage(productId: string, file: File, field: 'image' | 'gallery' = 'image'): Promise<AdminProductDetail> {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('field', field)
    return request<Wrapped<AdminProductDetail>>({ url: `/admin/product-builder/products/${productId}/images`, method: 'POST', data: formData, headers: { 'Content-Type': 'multipart/form-data' } }).then((r) => r.data)
  },

  updateProductImage(productId: string, imageId: string, data: { name?: string; alt_text?: string }): Promise<ProductImage> {
    return request<Wrapped<ProductImage>>({ url: `/admin/product-builder/products/${productId}/images/${imageId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteProductImage(productId: string, imageId: string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${productId}/images/${imageId}`, method: 'DELETE' })
  },

  // --- Options (per product) ---
  createOption(productId: string, data: Partial<PbOption>): Promise<PbOption> {
    return request<Wrapped<PbOption>>({ url: `/admin/product-builder/products/${productId}/options`, method: 'POST', data }).then((r) => r.data)
  },

  updateOption(productId: string, optionId: string, data: Partial<PbOption>): Promise<PbOption> {
    return request<Wrapped<PbOption>>({ url: `/admin/product-builder/products/${productId}/options/${optionId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteOption(productId: string, optionId: string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${productId}/options/${optionId}`, method: 'DELETE' })
  },

  createOptionValue(productId: string, optionId: string, data: Partial<PbOptionValue>): Promise<PbOptionValue> {
    return request<Wrapped<PbOptionValue>>({ url: `/admin/product-builder/products/${productId}/options/${optionId}/values`, method: 'POST', data }).then((r) => r.data)
  },

  updateOptionValue(productId: string, optionId: string, valueId: string, data: Partial<PbOptionValue>): Promise<PbOptionValue> {
    return request<Wrapped<PbOptionValue>>({ url: `/admin/product-builder/products/${productId}/options/${optionId}/values/${valueId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteOptionValue(productId: string, optionId: string, valueId: string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${productId}/options/${optionId}/values/${valueId}`, method: 'DELETE' })
  },

  // --- Orders ---
  adminOrders(): Promise<Paginated<AdminOrder>> {
    return request<Paginated<AdminOrder>>({ url: '/admin/orders', method: 'GET' })
  },

  updateOrderStatus(id: string, status: string): Promise<AdminOrder> {
    return request<Wrapped<AdminOrder>>({ url: `/admin/orders/${id}/status`, method: 'PUT', data: { status } }).then((r) => r.data)
  },

  // --- Payments ---
  gateways(): Promise<PaymentGateway[]> {
    return request<Wrapped<PaymentGateway[]>>({ url: '/admin/payments/gateways', method: 'GET' }).then((r) => r.data)
  },

  updateGateway(id: string, data: Partial<PaymentGateway>): Promise<PaymentGateway> {
    return request<Wrapped<PaymentGateway>>({ url: `/admin/payments/gateways/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  payments(): Promise<Paginated<AdminPayment>> {
    return request<Paginated<AdminPayment>>({ url: '/admin/payments', method: 'GET' })
  },

  retryPayment(id: string): Promise<AdminPayment> {
    return request<Wrapped<AdminPayment>>({ url: `/admin/payments/${id}/retry`, method: 'POST' }).then((r) => r.data)
  },

  // --- Calendar ---
  schedule(): Promise<CalendarSchedule[]> {
    return request<Wrapped<CalendarSchedule[]>>({ url: '/admin/calendar/schedule', method: 'GET' }).then((r) => r.data)
  },

  updateSchedule(data: CalendarSchedule[]): Promise<CalendarSchedule[]> {
    return request<Wrapped<CalendarSchedule[]>>({ url: '/admin/calendar/schedule', method: 'PUT', data: { schedule: data } }).then((r) => r.data)
  },

  deliverySlots(): Promise<DeliverySlot[]> {
    return request<Wrapped<DeliverySlot[]>>({ url: '/admin/calendar/slots', method: 'GET' }).then((r) => r.data)
  },

  createSlot(data: Partial<DeliverySlot>): Promise<DeliverySlot> {
    return request<Wrapped<DeliverySlot>>({ url: '/admin/calendar/slots', method: 'POST', data }).then((r) => r.data)
  },

  deleteSlot(id: string): Promise<void> {
    return request({ url: `/admin/calendar/slots/${id}`, method: 'DELETE' })
  },

  holidays(): Promise<Holiday[]> {
    return request<Wrapped<Holiday[]>>({ url: '/admin/calendar/holidays', method: 'GET' }).then((r) => r.data)
  },

  createHoliday(data: { date: string; name: string }): Promise<Holiday> {
    return request<Wrapped<Holiday>>({ url: '/admin/calendar/holidays', method: 'POST', data }).then((r) => r.data)
  },

  deleteHoliday(id: string): Promise<void> {
    return request({ url: `/admin/calendar/holidays/${id}`, method: 'DELETE' })
  },

  // --- Notifications ---
  emailTemplates(): Promise<EmailTemplate[]> {
    return request<Wrapped<EmailTemplate[]>>({ url: '/admin/notifications/templates', method: 'GET' }).then((r) => r.data)
  },

  updateTemplate(id: string, data: Partial<EmailTemplate>): Promise<EmailTemplate> {
    return request<Wrapped<EmailTemplate>>({ url: `/admin/notifications/templates/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  reminderRules(): Promise<ReminderRule[]> {
    return request<Wrapped<ReminderRule[]>>({ url: '/admin/notifications/reminders', method: 'GET' }).then((r) => r.data)
  },

  notificationLogs(): Promise<Paginated<NotificationLog>> {
    return request<Paginated<NotificationLog>>({ url: '/admin/notifications/logs', method: 'GET' })
  },
}
