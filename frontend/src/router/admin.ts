import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

import { administrationRoutes } from '@/modules/administration/routes'

/**
 * Router EXCLUSIVO del panel Admin (entry `src/entries/admin.ts`).
 * Solo compone las rutas del módulo administration; cualquier URL fuera de
 * `/admin/*` pertenece al Storefront y se sirve desde el otro entry point
 * (recarga completa de página, nunca navegación SPA entre interfaces).
 */
const routes: RouteRecordRaw[] = [
  ...administrationRoutes,
  {
    // Dentro del entry admin, cualquier ruta desconocida vuelve al dashboard.
    path: '/:pathMatch(.*)*',
    redirect: '/admin',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

/**
 * Guard del admin — sesión independiente de la del cliente (token propio).
 *
 * SÍNCRONO por diseño (anti-FOUC): la comprobación lee localStorage (misma
 * fuente que hidrata el store de Pinia) y devuelve la redirección ANTES de
 * que el router confirme la navegación — jamás se resuelve ni renderiza el
 * componente protegido. Nada de awaits ni llamadas al API aquí: la validez
 * real del token la vigila el interceptor de Axios (§28).
 */
router.beforeEach((to) => {
  const hasSession = localStorage.getItem('admin_token') !== null

  // Ruta protegida sin sesión → login, recordando el destino original.
  if (to.meta.requiresAdmin && !hasSession) {
    return { name: 'admin.login', query: { redirect: to.fullPath } }
  }

  // Con sesión activa, el login no se visita: directo al dashboard
  // (o al destino que traía ?redirect).
  if (to.name === 'admin.login' && hasSession) {
    const redirect = typeof to.query.redirect === 'string' ? to.query.redirect : null
    return redirect ?? { name: 'admin.dashboard' }
  }
})

export default router
