import type { RouteRecordRaw } from 'vue-router'

export const paymentsRoutes: RouteRecordRaw[] = [
  {
    // Payment step for a placed order (gateway selection + initiation).
    path: '/orders/:id/pay',
    name: 'payments.pay',
    component: () => import('./pages/PaymentPage.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
]
