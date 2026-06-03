import type { RouteRecordRaw } from 'vue-router'

export const notificationsRoutes: RouteRecordRaw[] = [
  {
    path: '/notifications',
    name: 'notifications',
    component: () => import('./pages/NotificationsPage.vue'),
    meta: { layout: 'default', requiresAuth: true },
  },
]
