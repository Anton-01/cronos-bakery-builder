import type { RouteRecordRaw } from 'vue-router'

export const productBuilderRoutes: RouteRecordRaw[] = [
  {
    path: '/builder',
    name: 'builder',
    component: () => import('./pages/ProductBuilderPage.vue'),
    meta: { layout: 'default' },
  },
]
