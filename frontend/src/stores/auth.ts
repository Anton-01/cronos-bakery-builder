import { defineStore } from 'pinia'

import { authService } from '@/modules/authentication/services/authService'
import type { AuthUser, LoginCredentials } from '@/modules/authentication/types'

interface AuthState {
  user: AuthUser | null
  token: string | null
}

/**
 * Global authentication store. Module-level stores may compose this one, but
 * session/token concerns live here so guards and the HTTP layer share a source
 * of truth.
 */
export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: localStorage.getItem('auth_token'),
  }),

  getters: {
    isAuthenticated: (state): boolean => Boolean(state.token),
  },

  actions: {
    async login(credentials: LoginCredentials): Promise<void> {
      const { user, token } = await authService.login(credentials)
      this.user = user
      this.token = token
      localStorage.setItem('auth_token', token)
    },

    async fetchCurrentUser(): Promise<void> {
      this.user = await authService.me()
    },

    async logout(): Promise<void> {
      await authService.logout()
      this.user = null
      this.token = null
      localStorage.removeItem('auth_token')
    },
  },
})
