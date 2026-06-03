import { defineStore } from 'pinia'

import { adminAuthService } from '../services/adminAuthService'
import type { Admin, AdminCredentials } from '../types'

interface AdminAuthState {
  admin: Admin | null
  token: string | null
}

const TOKEN_KEY = 'admin_token'

/**
 * Independent store for the administration panel. Kept separate from the
 * customer auth store so the two sessions never collide.
 */
export const useAdminAuthStore = defineStore('adminAuth', {
  state: (): AdminAuthState => ({
    admin: null,
    token: localStorage.getItem(TOKEN_KEY),
  }),

  getters: {
    isAuthenticated: (state): boolean => Boolean(state.token),
    can:
      (state) =>
      (permission: string): boolean =>
        state.admin?.permissions.includes(permission) ?? false,
    hasRole:
      (state) =>
      (role: string): boolean =>
        state.admin?.roles.includes(role) ?? false,
  },

  actions: {
    async login(credentials: AdminCredentials): Promise<void> {
      const { admin, token } = await adminAuthService.login(credentials)
      this.admin = admin
      this.token = token
      localStorage.setItem(TOKEN_KEY, token)
    },

    async fetchCurrentAdmin(): Promise<void> {
      this.admin = await adminAuthService.me()
    },

    async logout(): Promise<void> {
      await adminAuthService.logout()
      this.admin = null
      this.token = null
      localStorage.removeItem(TOKEN_KEY)
    },
  },
})
