import { request } from '@/services/http'
import type { AuthSession, AuthUser, LoginCredentials } from '../types'

/**
 * Thin transport layer for the Authentication module. Components and stores
 * depend on this contract rather than on Axios directly.
 */
export const authService = {
  login(credentials: LoginCredentials): Promise<AuthSession> {
    return request<AuthSession>({ url: '/auth/login', method: 'POST', data: credentials })
  },

  me(): Promise<AuthUser> {
    return request<AuthUser>({ url: '/auth/me', method: 'GET' })
  },

  logout(): Promise<void> {
    return request<void>({ url: '/auth/logout', method: 'POST' })
  },
}
