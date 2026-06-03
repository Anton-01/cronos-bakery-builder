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

interface Wrapped<T> {
  data: T
}

interface Paginated<T> {
  data: T[]
  meta?: { current_page: number; last_page: number; total: number }
}

export interface TwoFactorSetup {
  secret: string
  otpauth_url: string
}

export const adminPanelService = {
  dashboard(): Promise<DashboardMetrics> {
    return request<Wrapped<DashboardMetrics>>({ url: '/admin/dashboard', method: 'GET' }).then((r) => r.data)
  },

  metrics(): Promise<Record<string, unknown>> {
    return request<Wrapped<Record<string, unknown>>>({ url: '/admin/metrics', method: 'GET' }).then(
      (r) => r.data,
    )
  },

  enableTwoFactor(): Promise<TwoFactorSetup> {
    return request<Wrapped<TwoFactorSetup>>({ url: '/admin/2fa/enable', method: 'POST' }).then((r) => r.data)
  },

  confirmTwoFactor(code: string): Promise<{ message: string }> {
    return request<{ message: string }>({ url: '/admin/2fa/confirm', method: 'POST', data: { code } })
  },

  disableTwoFactor(): Promise<{ message: string }> {
    return request<{ message: string }>({ url: '/admin/2fa/disable', method: 'POST' })
  },

  auditLogs(): Promise<Paginated<AuditLog>> {
    return request<Paginated<AuditLog>>({ url: '/admin/audit-logs', method: 'GET' })
  },

  users(search = ''): Promise<Paginated<AdminUser>> {
    return request<Paginated<AdminUser>>({ url: '/admin/users', method: 'GET', params: { search } })
  },

  roles(): Promise<RoleDefinition[]> {
    return request<Wrapped<RoleDefinition[]>>({ url: '/admin/roles', method: 'GET' }).then((r) => r.data)
  },
}
