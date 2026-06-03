import type { RouteRecordRaw } from 'vue-router'

export const ordersRoutes: RouteRecordRaw[] = [
  {
    path: '/carrito',
    name: 'cart',
    component: () => import('./pages/CartPage.vue'),
    meta: { layout: 'default' },
  },
  {
    path: '/checkout',
    name: 'checkout',
    component: () => import('./pages/CheckoutPage.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
  {
    path: '/orders',
    name: 'orders',
    component: () => import('./pages/OrdersPage.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
  {
    path: '/orders/:id',
    name: 'orders.detail',
    component: () => import('./pages/OrderDetailPage.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
]
