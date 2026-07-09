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
  first_name: string
  last_name: string
  email: string
  phone: string | null
  avatar: string | null
  brand_id?: number | null
  notification_settings?: Record<string, boolean>
  roles: string[]
  is_suspended: boolean
  suspended_at: string | null
  suspended_until: string | null
  suspension_reason: string | null
  email_verified: boolean
  created_at: string
}

export interface RoleDefinition {
  name: string
  permissions: string[]
}

export interface TwoFactorSetup {
  secret: string
  otpauth_url: string
}

// --- Perfil y sesiones (Sanctum avanzado) ---
export interface AdminProfile {
  id: number
  name: string
  email: string
  phone: string | null
  avatar: string | null
  notification_settings: Record<string, boolean>
  two_factor_enabled: boolean
  is_active: boolean
  roles: string[]
  permissions: string[]
}

/** Un token Sanctum presentado como sesión/dispositivo revocable. */
export interface AdminSession {
  id: number
  name: string
  device_name: string
  ip_address: string | null
  user_agent: string | null
  last_used_at: string | null
  created_at: string | null
  is_current: boolean
}

// --- CMS types ---
// Page/block types live in `@/modules/cms/types` (brand-aware page builder).

// --- Theme types ---
export interface ThemeColorPalette {
  primary?: string; secondary?: string; accent?: string
  background?: string; surface?: string; text?: string
}
export interface ThemeTypography {
  heading_font?: string; body_font?: string
  heading_weight?: string; body_weight?: string
  base_font_size?: number
}
export interface ThemeLayoutConfig {
  header_sticky?: boolean; footer_expanded?: boolean
  container_width?: 'boxed' | 'wide' | 'full'
  show_breadcrumbs?: boolean; product_grid_columns?: number
}
export interface ThemeCustomScripts { head?: string; body_start?: string; body_end?: string }
export interface Theme {
  id: number
  name: string
  is_active: boolean
  logo?: string | null
  favicon?: string | null
  colors?: Record<string, string>
  fonts?: Record<string, string>
  color_palette?: ThemeColorPalette | null
  typography_settings?: ThemeTypography | null
  layout_config?: ThemeLayoutConfig | null
  custom_scripts?: ThemeCustomScripts | null
  footer?: Record<string, unknown> | null
  settings: Record<string, unknown> | null
}
export interface CmsMenu { id: number; name: string; location: string; is_active?: boolean; items: CmsMenuItem[] }
export interface CmsMenuItem {
  id: number
  label: string
  url: string | null
  target?: '_self' | '_blank' | null
  position: number
  is_active?: boolean
  parent_id?: number | null
  children: CmsMenuItem[]
}
export interface CmsMenuItemPayload {
  label: string
  url?: string | null
  target?: '_self' | '_blank' | null
  parent_id?: number | null
  position?: number
  is_active?: boolean
}
export interface CmsBanner { id: number; placement: string; title: string; image: string | null; url: string | null; is_active: boolean }

// --- Catalog types ---
export interface AdminCategory { id: number; name: string; slug: string; position: number; parent_id: number | null; products_count?: number }
export interface AdminCollection { id: number; name: string; slug: string }
export interface AdminAttribute { id: number; name: string; code: string; type: string; values: AdminAttributeValue[] }
export interface AdminAttributeValue { id: number; label: string; value: string; position: number; metadata: Record<string, unknown> | null }

// --- Product Builder types ---
export interface ProductImage {
  id: number
  path: string
  name: string | null
  alt_text: string | null
  position: number
}

export interface AdminProduct {
  id: number
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
  id: number
  label: string
  value: string
  price_modifier_type: 'none' | 'add' | 'subtract' | 'set'
  price_modifier_amount: number
  metadata: Record<string, unknown> | null
  is_default: boolean
  position: number
}

export interface PbOption {
  id: number
  product_id: number
  key: string
  label: string
  type: PbOptionType
  help_text: string | null
  is_required: boolean
  position: number
  config: Record<string, unknown> | null
  values: PbOptionValue[]
}

// --- Option Templates (global, independent of products) ---
export interface OptionTemplateValue {
  id: number
  template_id: number
  label: string
  value: string
  price_modifier_type: 'none' | 'add' | 'subtract' | 'set'
  price_modifier_amount: number
  metadata: Record<string, unknown> | null
  is_default: boolean
  position: number
}

export interface OptionTemplate {
  id: number
  key: string
  label: string
  type: PbOptionType
  help_text: string | null
  is_required: boolean
  position: number
  config: Record<string, unknown> | null
  values: OptionTemplateValue[]
}

// --- Product-Option Links ---
export interface ProductOptionLink {
  id: number
  product_id: number
  template_id: number
  legend: string | null
  /** IDs de valores de la plantilla EXCLUIDOS para este producto (null/[] = hereda todos). */
  excluded_value_ids: number[] | null
  position: number
  template?: OptionTemplate
  /** Valores efectivos (con exclusiones aplicadas) — lo que ve el storefront. */
  values?: OptionTemplateValue[]
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
export type GatewayEnvironment = 'sandbox' | 'production'
export type TransactionStatus = 'pending' | 'processing' | 'paid' | 'failed' | 'refunded' | 'cancelled'

export interface GatewayDriverField { key: string; label: string; secret: boolean }
export interface GatewayDriver { driver_name: string; label: string; fields: GatewayDriverField[] }

export interface PaymentGateway {
  id: number
  brand_id: number | null
  driver_name: string
  driver_label: string
  name: string
  environment: GatewayEnvironment
  is_active: boolean
  /** Solo hints enmascarados ("••••••••1234") — el API jamás devuelve secretos en claro. */
  credentials: Record<string, string | null>
  has_webhook_secret: boolean
  created_at?: string
  updated_at?: string
}

export interface PaymentGatewayPayload {
  brand_id?: number | null
  driver_name?: string
  name?: string
  environment?: GatewayEnvironment
  is_active?: boolean
  /** Solo campos re-escritos por el usuario; null elimina la clave. */
  credentials?: Record<string, string | null>
}

export interface TransactionEvent { id: number; type: string; status: string | null; signature_valid: boolean | null; at: string | null }

export interface Transaction {
  id: number
  brand_id: number | null
  order_id: string
  order_number?: string
  payment_gateway_id: number
  gateway_name?: string
  driver_name?: string
  environment?: GatewayEnvironment
  provider_transaction_id: string | null
  status: TransactionStatus
  status_label: string
  amount: number
  currency: string
  attempts: number
  paid_at: string | null
  created_at: string
  events?: TransactionEvent[]
}

export interface TransactionFilters {
  status?: TransactionStatus | ''
  gateway_id?: number | null
  date_from?: string
  date_to?: string
  brand_id?: number | null
  page?: number
}

// --- Calendar types ---
export interface CalendarSchedule { id: number; day_of_week: number; opens_at: string; closes_at: string; is_active: boolean }
export interface DeliverySlot { id: number; label: string; starts_at: string; ends_at: string; max_orders: number }
export interface Holiday { id: number; date: string; name: string }
export interface Blackout { id: number; from: string; to: string; reason: string }

// --- Notifications types ---
export interface EmailTemplate { id: string; key: string; subject: string; body: string; is_active: boolean }
export interface ReminderRule { id: string; event: string; delay_minutes: number; template_id: string; is_active: boolean }
export interface NotificationLog { id: string; channel: string; recipient: string; subject: string; status: string; sent_at: string }

interface Wrapped<T> { data: T }
export interface Paginated<T> { data: T[]; meta?: { current_page: number; last_page: number; total: number } }

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

  // --- Mi Perfil (self-service del admin autenticado) ---
  updateAdminProfile(data: { name?: string; email?: string; phone?: string | null }): Promise<AdminProfile> {
    return request<Wrapped<AdminProfile>>({ url: '/admin/profile', method: 'PUT', data }).then((r) => r.data)
  },

  uploadAdminAvatar(file: File): Promise<AdminProfile> {
    const form = new FormData()
    form.append('avatar', file)
    return request<Wrapped<AdminProfile>>({
      url: '/admin/profile/avatar',
      method: 'POST',
      data: form,
      headers: { 'Content-Type': 'multipart/form-data' },
    }).then((r) => r.data)
  },

  deleteAdminAvatar(): Promise<AdminProfile> {
    return request<Wrapped<AdminProfile>>({ url: '/admin/profile/avatar', method: 'DELETE' }).then((r) => r.data)
  },

  changeAdminPassword(data: { current_password: string; password: string; password_confirmation: string }): Promise<{ message: string }> {
    return request<{ message: string }>({ url: '/admin/profile/password', method: 'PUT', data })
  },

  updateAdminNotifications(settings: Record<string, boolean>): Promise<AdminProfile> {
    return request<Wrapped<AdminProfile>>({ url: '/admin/profile/notifications', method: 'PUT', data: { settings } }).then((r) => r.data)
  },

  adminSessions(): Promise<AdminSession[]> {
    return request<Wrapped<AdminSession[]>>({ url: '/admin/profile/sessions', method: 'GET' }).then((r) => r.data)
  },

  revokeAdminSession(tokenId: number): Promise<{ message: string }> {
    return request<{ message: string }>({ url: `/admin/profile/sessions/${tokenId}`, method: 'DELETE' })
  },

  revokeOtherAdminSessions(): Promise<{ message: string }> {
    return request<{ message: string }>({ url: '/admin/profile/sessions/revoke-others', method: 'POST' })
  },

  // --- Users & Roles ---
  auditLogs(): Promise<Paginated<AuditLog>> {
    return request<Paginated<AuditLog>>({ url: '/admin/audit-logs', method: 'GET' })
  },

  users(params: { search?: string; status?: string; role?: string; page?: number; per_page?: number } = {}): Promise<Paginated<AdminUser>> {
    return request<Paginated<AdminUser>>({ url: '/admin/users', method: 'GET', params })
  },

  showUser(id: number): Promise<AdminUser> {
    return request<Wrapped<AdminUser>>({ url: `/admin/users/${id}`, method: 'GET' }).then(r => r.data)
  },

  createUser(data: { first_name: string; last_name: string; email: string; phone?: string; password: string; role: string }): Promise<AdminUser> {
    return request<Wrapped<AdminUser>>({ url: '/admin/users', method: 'POST', data }).then(r => r.data)
  },

  updateUser(id: number, data: { first_name?: string; last_name?: string; email?: string; phone?: string; role?: string }): Promise<AdminUser> {
    return request<Wrapped<AdminUser>>({ url: `/admin/users/${id}`, method: 'PUT', data }).then(r => r.data)
  },

  deleteUser(id: number): Promise<void> {
    return request({ url: `/admin/users/${id}`, method: 'DELETE' })
  },

  suspendUser(id: number, data: { reason: string; suspended_until?: string }): Promise<AdminUser> {
    return request<Wrapped<AdminUser>>({ url: `/admin/users/${id}/suspend`, method: 'POST', data }).then(r => r.data)
  },

  reactivateUser(id: number): Promise<AdminUser> {
    return request<Wrapped<AdminUser>>({ url: `/admin/users/${id}/reactivate`, method: 'POST' }).then(r => r.data)
  },

  impersonateUser(id: number): Promise<{ token: string; user: AdminUser }> {
    return request<{ token: string; user: AdminUser }>({ url: `/admin/users/${id}/impersonate`, method: 'POST' })
  },

  revokeUserSessions(id: number): Promise<{ message: string }> {
    return request<{ message: string }>({ url: `/admin/users/${id}/revoke-sessions`, method: 'POST' })
  },

  userSessions(id: number): Promise<AdminSession[]> {
    return request<Wrapped<AdminSession[]>>({ url: `/admin/users/${id}/sessions`, method: 'GET' }).then((r) => r.data)
  },

  sendPasswordReset(id: number): Promise<{ message: string }> {
    return request<{ message: string }>({ url: `/admin/users/${id}/send-password-reset`, method: 'POST' })
  },

  roles(): Promise<RoleDefinition[]> {
    return request<Wrapped<RoleDefinition[]>>({ url: '/admin/roles', method: 'GET' }).then((r) => r.data)
  },

  // --- CMS ---
  // Page management moved to `@/modules/cms/services/pageBuilderService`
  // (brand-scoped pages + block builder).

  // --- Themes ---
  themes(): Promise<Theme[]> {
    return request<Wrapped<Theme[]>>({ url: '/admin/themes', method: 'GET' }).then((r) => r.data)
  },

  updateTheme(id: number, data: Partial<Theme>): Promise<Theme> {
    return request<Wrapped<Theme>>({ url: `/admin/themes/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  activateTheme(id: number): Promise<Theme> {
    return request<Wrapped<Theme>>({ url: `/admin/themes/${id}/activate`, method: 'PUT' }).then((r) => r.data)
  },

  // --- Menus ---
  menus(): Promise<CmsMenu[]> {
    return request<Wrapped<CmsMenu[]>>({ url: '/admin/menus', method: 'GET' }).then((r) => r.data)
  },

  createMenu(data: { name: string; location: string }): Promise<CmsMenu> {
    return request<Wrapped<CmsMenu>>({ url: '/admin/menus', method: 'POST', data }).then((r) => r.data)
  },

  updateMenu(id: number, data: Partial<CmsMenu>): Promise<CmsMenu> {
    return request<Wrapped<CmsMenu>>({ url: `/admin/menus/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteMenu(id: number): Promise<void> {
    return request({ url: `/admin/menus/${id}`, method: 'DELETE' })
  },

  createMenuItem(menuId: number, data: CmsMenuItemPayload): Promise<CmsMenuItem> {
    return request<Wrapped<CmsMenuItem>>({ url: `/admin/menus/${menuId}/items`, method: 'POST', data }).then((r) => r.data)
  },

  updateMenuItem(menuId: number, itemId: number, data: CmsMenuItemPayload): Promise<CmsMenuItem> {
    return request<Wrapped<CmsMenuItem>>({ url: `/admin/menus/${menuId}/items/${itemId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteMenuItem(menuId: number, itemId: number): Promise<void> {
    return request({ url: `/admin/menus/${menuId}/items/${itemId}`, method: 'DELETE' })
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

  updateCategory(id: number, data: Partial<AdminCategory>): Promise<AdminCategory> {
    return request<Wrapped<AdminCategory>>({ url: `/admin/catalog/categories/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteCategory(id: number): Promise<void> {
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

  updateProduct(id: number | string, data: Partial<AdminProduct>): Promise<AdminProduct> {
    return request<Wrapped<AdminProduct>>({ url: `/admin/product-builder/products/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  showProduct(id: number | string): Promise<AdminProductDetail> {
    return request<Wrapped<AdminProductDetail>>({ url: `/admin/product-builder/products/${id}`, method: 'GET' }).then((r) => r.data)
  },

  deleteProduct(id: number | string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${id}`, method: 'DELETE' })
  },

  uploadProductImage(productId: number | string, file: File, field: 'image' | 'gallery' = 'image'): Promise<AdminProductDetail> {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('field', field)
    return request<Wrapped<AdminProductDetail>>({ url: `/admin/product-builder/products/${productId}/images`, method: 'POST', data: formData, headers: { 'Content-Type': 'multipart/form-data' } }).then((r) => r.data)
  },

  updateProductImage(productId: number | string, imageId: number | string, data: { name?: string; alt_text?: string }): Promise<ProductImage> {
    return request<Wrapped<ProductImage>>({ url: `/admin/product-builder/products/${productId}/images/${imageId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteProductImage(productId: number | string, imageId: number | string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${productId}/images/${imageId}`, method: 'DELETE' })
  },

  // --- Options (per product) ---
  createOption(productId: number | string, data: Partial<PbOption>): Promise<PbOption> {
    return request<Wrapped<PbOption>>({ url: `/admin/product-builder/products/${productId}/options`, method: 'POST', data }).then((r) => r.data)
  },

  updateOption(productId: number | string, optionId: number | string, data: Partial<PbOption>): Promise<PbOption> {
    return request<Wrapped<PbOption>>({ url: `/admin/product-builder/products/${productId}/options/${optionId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteOption(productId: number | string, optionId: number | string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${productId}/options/${optionId}`, method: 'DELETE' })
  },

  createOptionValue(productId: number | string, optionId: number | string, data: Partial<PbOptionValue>): Promise<PbOptionValue> {
    return request<Wrapped<PbOptionValue>>({ url: `/admin/product-builder/products/${productId}/options/${optionId}/values`, method: 'POST', data }).then((r) => r.data)
  },

  updateOptionValue(productId: number | string, optionId: number | string, valueId: number | string, data: Partial<PbOptionValue>): Promise<PbOptionValue> {
    return request<Wrapped<PbOptionValue>>({ url: `/admin/product-builder/products/${productId}/options/${optionId}/values/${valueId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteOptionValue(productId: number | string, optionId: number | string, valueId: number | string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${productId}/options/${optionId}/values/${valueId}`, method: 'DELETE' })
  },

  // --- Option Templates (global) ---
  optionTemplates(): Promise<OptionTemplate[]> {
    return request<Wrapped<OptionTemplate[]>>({ url: '/admin/product-builder/option-templates', method: 'GET' }).then((r) => r.data)
  },

  createOptionTemplate(data: Partial<OptionTemplate>): Promise<OptionTemplate> {
    return request<Wrapped<OptionTemplate>>({ url: '/admin/product-builder/option-templates', method: 'POST', data }).then((r) => r.data)
  },

  updateOptionTemplate(id: number | string, data: Partial<OptionTemplate>): Promise<OptionTemplate> {
    return request<Wrapped<OptionTemplate>>({ url: `/admin/product-builder/option-templates/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteOptionTemplate(id: number | string): Promise<void> {
    return request({ url: `/admin/product-builder/option-templates/${id}`, method: 'DELETE' })
  },

  createTemplateValue(templateId: number | string, data: Partial<OptionTemplateValue>): Promise<OptionTemplateValue> {
    return request<Wrapped<OptionTemplateValue>>({ url: `/admin/product-builder/option-templates/${templateId}/values`, method: 'POST', data }).then((r) => r.data)
  },

  updateTemplateValue(templateId: number | string, valueId: number | string, data: Partial<OptionTemplateValue>): Promise<OptionTemplateValue> {
    return request<Wrapped<OptionTemplateValue>>({ url: `/admin/product-builder/option-templates/${templateId}/values/${valueId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteTemplateValue(templateId: number | string, valueId: number | string): Promise<void> {
    return request({ url: `/admin/product-builder/option-templates/${templateId}/values/${valueId}`, method: 'DELETE' })
  },

  // --- Product-Option Links ---
  productOptionLinks(productId: number | string): Promise<ProductOptionLink[]> {
    return request<Wrapped<ProductOptionLink[]>>({ url: `/admin/product-builder/products/${productId}/option-links`, method: 'GET' }).then((r) => r.data)
  },

  createProductOptionLink(productId: number | string, data: { template_id: number; legend?: string; excluded_value_ids?: number[] }): Promise<ProductOptionLink> {
    return request<Wrapped<ProductOptionLink>>({ url: `/admin/product-builder/products/${productId}/option-links`, method: 'POST', data }).then((r) => r.data)
  },

  updateProductOptionLink(productId: number | string, linkId: number | string, data: Partial<Pick<ProductOptionLink, 'legend' | 'excluded_value_ids' | 'position'>>): Promise<ProductOptionLink> {
    return request<Wrapped<ProductOptionLink>>({ url: `/admin/product-builder/products/${productId}/option-links/${linkId}`, method: 'PUT', data }).then((r) => r.data)
  },

  deleteProductOptionLink(productId: number | string, linkId: number | string): Promise<void> {
    return request({ url: `/admin/product-builder/products/${productId}/option-links/${linkId}`, method: 'DELETE' })
  },

  generatePreviewToken(productId: number | string): Promise<{ token: string; expires_in_minutes: number }> {
    return request<Wrapped<{ token: string; expires_in_minutes: number }>>({ url: `/admin/product-builder/products/${productId}/preview-token`, method: 'POST' }).then((r) => r.data)
  },

  // --- Orders ---
  adminOrders(): Promise<Paginated<AdminOrder>> {
    return request<Paginated<AdminOrder>>({ url: '/admin/orders', method: 'GET' })
  },

  updateOrderStatus(id: string, status: string): Promise<AdminOrder> {
    return request<Wrapped<AdminOrder>>({ url: `/admin/orders/${id}/status`, method: 'PUT', data: { status } }).then((r) => r.data)
  },

  // --- Payments ---
  paymentDrivers(): Promise<GatewayDriver[]> {
    return request<Wrapped<GatewayDriver[]>>({ url: '/admin/payments/drivers', method: 'GET' }).then((r) => r.data)
  },

  paymentGateways(brandId?: number | null): Promise<PaymentGateway[]> {
    return request<Wrapped<PaymentGateway[]>>({
      url: '/admin/payments/gateways',
      method: 'GET',
      params: brandId != null ? { brand_id: brandId } : undefined,
    }).then((r) => r.data)
  },

  createPaymentGateway(data: PaymentGatewayPayload): Promise<PaymentGateway> {
    return request<Wrapped<PaymentGateway>>({ url: '/admin/payments/gateways', method: 'POST', data }).then((r) => r.data)
  },

  updatePaymentGateway(id: number, data: PaymentGatewayPayload): Promise<PaymentGateway> {
    return request<Wrapped<PaymentGateway>>({ url: `/admin/payments/gateways/${id}`, method: 'PUT', data }).then((r) => r.data)
  },

  deletePaymentGateway(id: number): Promise<void> {
    return request({ url: `/admin/payments/gateways/${id}`, method: 'DELETE' })
  },

  transactions(filters: TransactionFilters = {}): Promise<Paginated<Transaction>> {
    const params: Record<string, string | number> = {}
    if (filters.status) params.status = filters.status
    if (filters.gateway_id != null) params.gateway_id = filters.gateway_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    if (filters.brand_id != null) params.brand_id = filters.brand_id
    if (filters.page) params.page = filters.page
    return request<Paginated<Transaction>>({ url: '/admin/payments/transactions', method: 'GET', params })
  },

  transaction(id: number): Promise<Transaction> {
    return request<Wrapped<Transaction>>({ url: `/admin/payments/transactions/${id}`, method: 'GET' }).then((r) => r.data)
  },

  refundTransaction(id: number): Promise<Transaction> {
    return request<Wrapped<Transaction>>({ url: `/admin/payments/transactions/${id}/refund`, method: 'POST' }).then((r) => r.data)
  },

  retryTransaction(id: number): Promise<{ message: string }> {
    return request<{ message: string }>({ url: `/admin/payments/transactions/${id}/retry`, method: 'POST' })
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

  deleteSlot(id: number): Promise<void> {
    return request({ url: `/admin/calendar/slots/${id}`, method: 'DELETE' })
  },

  holidays(): Promise<Holiday[]> {
    return request<Wrapped<Holiday[]>>({ url: '/admin/calendar/holidays', method: 'GET' }).then((r) => r.data)
  },

  createHoliday(data: { date: string; name: string }): Promise<Holiday> {
    return request<Wrapped<Holiday>>({ url: '/admin/calendar/holidays', method: 'POST', data }).then((r) => r.data)
  },

  deleteHoliday(id: number): Promise<void> {
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
