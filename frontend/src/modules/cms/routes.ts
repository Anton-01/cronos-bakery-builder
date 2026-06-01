import type { RouteRecordRaw } from 'vue-router'

export const cmsRoutes: RouteRecordRaw[] = [
  {
    // Dynamic, CMS-driven pages rendered from stored block configuration.
    path: '/p/:slug',
    name: 'cms.page',
    component: () => import('./pages/DynamicPage.vue'),
    meta: { layout: 'default' },
  },
]
