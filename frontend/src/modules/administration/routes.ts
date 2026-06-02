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
    component: () => import('./pages/AdminDashboardPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true },
  },
  {
    path: '/admin/users',
    name: 'admin.users',
    component: () => import('./pages/UsersPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true },
  },
  {
    path: '/admin/roles',
    name: 'admin.roles',
    component: () => import('./pages/RolesPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true },
  },
  {
    path: '/admin/audit',
    name: 'admin.audit',
    component: () => import('./pages/AuditLogPage.vue'),
    meta: { layout: 'admin', requiresAdmin: true },
  },
]
