import type { RouteRecordRaw } from 'vue-router'

export const ordersRoutes: RouteRecordRaw[] = [
  {
    path: '/orders',
    name: 'orders',
    component: () => import('./pages/OrdersPage.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
]
