import type { RouteRecordRaw } from 'vue-router'

export const administrationRoutes: RouteRecordRaw[] = [
  {
    path: '/admin',
    name: 'admin.dashboard',
    component: () => import('./pages/AdministrationPage.vue'),
    meta: { layout: 'admin', requiresAuth: true },
  },
]
