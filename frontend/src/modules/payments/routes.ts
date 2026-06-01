import type { RouteRecordRaw } from 'vue-router'

export const paymentsRoutes: RouteRecordRaw[] = [
  {
    path: '/checkout',
    name: 'payments.checkout',
    component: () => import('./pages/PaymentsPage.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
]
