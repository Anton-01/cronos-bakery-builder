import type { RouteRecordRaw } from 'vue-router'

export const productBuilderRoutes: RouteRecordRaw[] = [
  {
    path: '/builder',
    name: 'builder',
    component: () => import('./pages/ProductListPage.vue'),
    meta: { layout: 'default' },
  },
  {
    // Auto-generated configurator for a specific product.
    path: '/builder/:slug',
    name: 'builder.configure',
    component: () => import('./pages/ConfiguratorPage.vue'),
    meta: { layout: 'default' },
  },
]
