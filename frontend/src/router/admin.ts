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

// Guard del admin — sesión independiente de la del cliente (token propio).
router.beforeEach((to) => {
  if (to.meta.requiresAdmin && !localStorage.getItem('admin_token')) {
    return { name: 'admin.login', query: { redirect: to.fullPath } }
  }
})

export default router
