import { request } from '@/services/http'
import type { CmsPage } from '../types'

interface Wrapped<T> {
  data: T
}

/**
 * Read-only access to published CMS pages for dynamic frontend rendering.
 */
export const cmsService = {
  page(slug: string): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({ url: `/cms/pages/${slug}`, method: 'GET' }).then((r) => r.data)
  },

  pages(): Promise<CmsPage[]> {
    return request<Wrapped<CmsPage[]>>({ url: '/cms/pages', method: 'GET' }).then((r) => r.data)
  },
}
