import { request } from '@/services/http'
import type { Banner, Menu, Theme } from '../types'

interface Wrapped<T> {
  data: T
}

/**
 * Read-only access to the active branding theme, navigation menus and banners
 * that drive the dynamically-rendered storefront.
 */
export const themeService = {
  theme(): Promise<Theme | null> {
    return request<Wrapped<Theme | null>>({ url: '/theme', method: 'GET' }).then((r) => r.data)
  },

  menu(location: string): Promise<Menu> {
    return request<Wrapped<Menu>>({ url: `/menus/${location}`, method: 'GET' }).then((r) => r.data)
  },

  banners(placement: string): Promise<Banner[]> {
    return request<Wrapped<Banner[]>>({ url: `/banners/${placement}`, method: 'GET' }).then(
      (r) => r.data,
    )
  },
}
