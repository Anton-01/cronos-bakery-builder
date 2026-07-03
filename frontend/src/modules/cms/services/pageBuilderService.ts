import { request } from '@/services/http'
import type { Brand, CmsPage, PageBlockPayload, PagePayload } from '../types'

interface Wrapped<T> {
  data: T
}

/**
 * Admin API for the block-based page builder: brand-scoped pages plus bulk
 * management of the ordered blocks composing each page.
 */
export const pageBuilderService = {
  brands(): Promise<Brand[]> {
    return request<Wrapped<Brand[]>>({ url: '/admin/cms/brands', method: 'GET' }).then((r) => r.data)
  },

  pages(brandId?: number): Promise<CmsPage[]> {
    return request<Wrapped<CmsPage[]>>({
      url: '/admin/cms/pages',
      method: 'GET',
      params: brandId ? { brand_id: brandId } : {},
    }).then((r) => r.data)
  },

  page(id: number): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({ url: `/admin/cms/pages/${id}`, method: 'GET' }).then((r) => r.data)
  },

  createPage(payload: PagePayload): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({ url: '/admin/cms/pages', method: 'POST', data: payload }).then((r) => r.data)
  },

  updatePage(id: number, payload: Omit<PagePayload, 'brand_id' | 'blocks'>): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({ url: `/admin/cms/pages/${id}`, method: 'PUT', data: payload }).then((r) => r.data)
  },

  deletePage(id: number): Promise<void> {
    return request({ url: `/admin/cms/pages/${id}`, method: 'DELETE' })
  },

  /**
   * Bulk save of the builder state: the full ordered block list. Blocks with
   * an id are updated, without one created, and missing ones deleted.
   */
  syncBlocks(pageId: number, blocks: PageBlockPayload[]): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({
      url: `/admin/cms/pages/${pageId}/blocks/sync`,
      method: 'PUT',
      data: { blocks },
    }).then((r) => r.data)
  },

  publish(pageId: number): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({ url: `/admin/cms/pages/${pageId}/publish`, method: 'PUT' }).then((r) => r.data)
  },

  unpublish(pageId: number): Promise<CmsPage> {
    return request<Wrapped<CmsPage>>({ url: `/admin/cms/pages/${pageId}/unpublish`, method: 'PUT' }).then((r) => r.data)
  },
}
