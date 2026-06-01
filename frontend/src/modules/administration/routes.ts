import type { RouteRecordRaw } from 'vue-router'

export const administrationRoutes: RouteRecordRaw[] = [
  {
    path: '/admin/login',
    name: 'admin.login',
    component: () => import('./pages/AdminLoginPage.vue'),
    meta: { layout: 'auth' },
  },
  {
    path: '/admin',
    name: 'admin.dashboard',
    component: () => import('./pages/AdministrationPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true },
  },
]
