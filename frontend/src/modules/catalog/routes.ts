import type { RouteRecordRaw } from 'vue-router'

export const catalogRoutes: RouteRecordRaw[] = [
  {
    path: '/catalog',
    name: 'catalog',
    component: () => import('./pages/CatalogPage.vue'),
    meta: { layout: 'default' },
  },
  {
    // SEO-friendly category landing page.
    path: '/categoria/:slug',
    name: 'catalog.category',
    component: () => import('./pages/CategoryPage.vue'),
    meta: { layout: 'default' },
  },
  {
    // SEO-friendly product detail page.
    path: '/pastel/:slug',
    name: 'catalog.product',
    component: () => import('./pages/ProductPage.vue'),
    meta: { layout: 'default' },
  },
]
