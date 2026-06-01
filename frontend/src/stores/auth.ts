import { defineStore } from 'pinia'

import { authService } from '@/modules/authentication/services/authService'
import type {
  AuthSession,
  AuthUser,
  LoginCredentials,
  RegisterPayload,
} from '@/modules/authentication/types'

interface AuthState {
  user: AuthUser | null
  token: string | null
}

/**
 * Global authentication store for customers. Session/token concerns live here
 * so the router guard and HTTP layer share a single source of truth.
 */
export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: localStorage.getItem('auth_token'),
  }),

  getters: {
    isAuthenticated: (state): boolean => Boolean(state.token),
    isVerified: (state): boolean => state.user?.email_verified ?? false,
  },

  actions: {
    setSession({ user, token }: AuthSession): void {
      this.user = user
      this.token = token
      localStorage.setItem('auth_token', token)
    },

    async register(payload: RegisterPayload): Promise<void> {
      this.setSession(await authService.register(payload))
    },

    async login(credentials: LoginCredentials): Promise<void> {
      this.setSession(await authService.login(credentials))
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
