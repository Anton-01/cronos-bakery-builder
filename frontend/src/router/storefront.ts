import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

import { authRoutes } from '@/modules/authentication/routes'
import { cmsRoutes } from '@/modules/cms/routes'
import { catalogRoutes } from '@/modules/catalog/routes'
import { productBuilderRoutes } from '@/modules/product-builder/routes'
import { ordersRoutes } from '@/modules/orders/routes'
import { paymentsRoutes } from '@/modules/payments/routes'
import { calendarRoutes } from '@/modules/calendar/routes'
import { notificationsRoutes } from '@/modules/notifications/routes'

/**
 * Router EXCLUSIVO del Storefront (entry `src/entries/storefront.ts`).
 * No conoce ninguna ruta del panel admin: el admin vive en un entry point
 * físicamente separado (`admin.html`) con su propio router, de modo que ni
 * el CSS ni el JS de una interfaz se inyectan en la otra.
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/pages/HomePage.vue'),
  },
  ...authRoutes,
  ...cmsRoutes,
  ...catalogRoutes,
  ...productBuilderRoutes,
  ...ordersRoutes,
  ...paymentsRoutes,
  ...calendarRoutes,
  ...notificationsRoutes,
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

/**
 * Guard del cliente — el admin tiene su propio guard en router/admin.ts.
 *
 * SÍNCRONO por diseño (anti-FOUC): lee localStorage (misma fuente que
 * hidrata el store de Pinia) y redirige ANTES de que el router confirme la
 * navegación. La validez real del token la vigila el interceptor (§28).
 */
router.beforeEach((to) => {
  const hasSession = localStorage.getItem('auth_token') !== null

  if (to.meta.requiresAuth && !hasSession) {
    return { name: 'auth.login', query: { redirect: to.fullPath } }
  }

  // Con sesión activa, login/registro no se visitan: al destino o al home.
  if ((to.name === 'auth.login' || to.name === 'auth.register') && hasSession) {
    const redirect = typeof to.query.redirect === 'string' ? to.query.redirect : null
    return redirect ?? { name: 'home' }
  }
})

export default router
