import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'

import { authRoutes } from '@/modules/authentication/routes'
import { cmsRoutes } from '@/modules/cms/routes'
import { catalogRoutes } from '@/modules/catalog/routes'
import { productBuilderRoutes } from '@/modules/product-builder/routes'
import { ordersRoutes } from '@/modules/orders/routes'
import { paymentsRoutes } from '@/modules/payments/routes'
import { calendarRoutes } from '@/modules/calendar/routes'
import { notificationsRoutes } from '@/modules/notifications/routes'
import { administrationRoutes } from '@/modules/administration/routes'

/**
 * Each feature module owns and exports its own route table. The router simply
 * composes them, keeping navigation concerns decoupled from the shell.
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
  ...administrationRoutes,
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

// Auth guard — routes flag protection via `meta.requiresAuth` (customers) or
// `meta.requiresAdmin` (independent admin guard).
router.beforeEach((to) => {
  if (to.meta.requiresAuth && !localStorage.getItem('auth_token')) {
    return { name: 'auth.login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresAdmin && !localStorage.getItem('admin_token')) {
    return { name: 'admin.login', query: { redirect: to.fullPath } }
  }
})

export default router
