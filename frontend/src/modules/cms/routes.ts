import type { RouteRecordRaw } from 'vue-router'

export const cmsRoutes: RouteRecordRaw[] = [
  {
    path: '/admin/cms',
    name: 'cms',
    component: () => import('./pages/CmsPage.vue'),
    meta: { layout: 'admin', requiresAuth: true },
  },
]
