import { request } from '@/services/http'
import type { Admin, AdminCredentials, AdminSession } from '../types'

/**
 * Transport layer for the administration panel's independent auth system.
 */
export const adminAuthService = {
  login(credentials: AdminCredentials): Promise<AdminSession> {
    return request<AdminSession>({ url: '/admin/login', method: 'POST', data: credentials })
  },

  me(): Promise<Admin> {
    return request<{ data: Admin }>({ url: '/admin/me', method: 'GET' }).then((r) => r.data)
  },

  logout(): Promise<void> {
    return request<void>({ url: '/admin/logout', method: 'POST' })
  },

  forgotPassword(email: string): Promise<{ message: string }> {
    return request<{ message: string }>({
      url: '/admin/password/forgot',
      method: 'POST',
      data: { email },
    })
  },
}
