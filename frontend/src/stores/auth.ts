import { defineStore } from 'pinia'

import router from '@/router/storefront'
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

const TOKEN_KEY = 'auth_token'

/**
 * Global authentication store for customers. Session/token concerns live here
 * so the router guard and HTTP layer share a single source of truth.
 */
export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: localStorage.getItem(TOKEN_KEY),
  }),

  getters: {
    isAuthenticated: (state): boolean => Boolean(state.token),
    isVerified: (state): boolean => state.user?.email_verified ?? false,
  },

  actions: {
    setSession({ user, token }: AuthSession): void {
      this.user = user
      this.token = token
      localStorage.setItem(TOKEN_KEY, token)
    },

    /** Limpieza 100% local y síncrona del estado de sesión. */
    clearSession(): void {
      this.user = null
      this.token = null
      localStorage.removeItem(TOKEN_KEY)
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

    /**
     * Cierre de sesión voluntario. La revocación del token en el backend es
     * best-effort: aunque el API esté caído o devuelva error, el estado local
     * SIEMPRE queda limpio (nunca más sesiones fantasma por logout fallido).
     */
    async logout(): Promise<void> {
      try {
        await authService.logout()
      } catch {
        // La revocación remota falló (red/401); el token expirará solo.
      } finally {
        this.clearSession()
      }
    },

    /**
     * Cierre FORZADO invocado por el interceptor de Axios (401/419/red caída).
     * Síncrono a nivel local: limpia Pinia + localStorage inmediatamente y
     * redirige a login SIN tocar el backend. Idempotente y sin bucles: si ya
     * estamos en login no vuelve a navegar.
     */
    forceLogout(): void {
      this.clearSession()

      const current = router.currentRoute.value
      if (current.name !== 'auth.login') {
        void router.push({ name: 'auth.login', query: { redirect: current.fullPath } })
      }
    },
  },
})
