import { defineStore } from 'pinia'

import router from '@/router/admin'
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

    /** Limpieza 100% local y síncrona del estado de sesión. */
    clearSession(): void {
      this.admin = null
      this.token = null
      localStorage.removeItem(TOKEN_KEY)
    },

    /**
     * Cierre de sesión voluntario. La revocación del token en el backend es
     * best-effort: aunque el API esté caído o devuelva error, el estado local
     * SIEMPRE queda limpio (nunca más sesiones fantasma por logout fallido).
     */
    async logout(): Promise<void> {
      try {
        await adminAuthService.logout()
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
      if (current.name !== 'admin.login') {
        void router.push({ name: 'admin.login', query: { redirect: current.fullPath } })
      }
    },
  },
})
