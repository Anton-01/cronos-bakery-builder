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

// Guard del cliente — el admin tiene su propio guard en router/admin.ts.
router.beforeEach((to) => {
  if (to.meta.requiresAuth && !localStorage.getItem('auth_token')) {
    return { name: 'auth.login', query: { redirect: to.fullPath } }
  }
})

export default router
