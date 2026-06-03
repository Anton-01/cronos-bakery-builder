import type { RouteRecordRaw } from 'vue-router'

export const calendarRoutes: RouteRecordRaw[] = [
  {
    path: '/admin/calendar',
    name: 'calendar',
    component: () => import('./pages/CalendarPage.vue'),
    meta: { layout: 'admin', requiresAuth: true },
  },
]
