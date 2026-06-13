import { request } from '@/services/http'
import type { ContentVersion, ContentWorkflow, MediaAsset, StorageProvider, CacheSetting, } from '../types'

interface Wrapped<T> { data: T }
interface Paginated<T> { data: T[]; meta?: { current_page: number; last_page: number; total: number } }

export const cmsContentService = {
    // --- Workflow Actions ---
    submitForReview(pageId: string, comment?: string): Promise<ContentWorkflow> {
        return request<Wrapped<ContentWorkflow>>({
            url: `/admin/cms/pages/${pageId}/submit-review`,
            method: 'POST',
            data: { comment },
        }).then((r) => r.data)
    },

    approve(pageId: string, comment?: string): Promise<ContentWorkflow> {
        return request<Wrapped<ContentWorkflow>>({
            url: `/admin/cms/pages/${pageId}/approve`,
            method: 'POST',
            data: { comment },
        }).then((r) => r.data)
    },

    reject(pageId: string, reason?: string): Promise<ContentWorkflow> {
        return request<Wrapped<ContentWorkflow>>({
            url: `/admin/cms/pages/${pageId}/reject`,
            method: 'POST',
            data: { reason },
        }).then((r) => r.data)
    },

    schedule(pageId: string, publishAt: string): Promise<ContentWorkflow> {
        return request<Wrapped<ContentWorkflow>>({
            url: `/admin/cms/pages/${pageId}/schedule`,
            method: 'POST',
            data: { publish_at: publishAt },
        }).then((r) => r.data)
    },

    // --- Versioning ---
    versions(pageId: string): Promise<ContentVersion[]> {
        return request<Wrapped<ContentVersion[]>>({
            url: `/admin/cms/pages/${pageId}/versions`,
            method: 'GET',
        }).then((r) => r.data)
    },

    rollback(pageId: string, versionId: string): Promise<void> {
        return request({
            url: `/admin/cms/pages/${pageId}/rollback`,
            method: 'POST',
            data: { version_id: versionId },
        })
    },

    // --- Media Library ---
    mediaAssets(params: { page?: number; per_page?: number; search?: string } = {}): Promise<Paginated<MediaAsset>> {
        return request<Paginated<MediaAsset>>({
            url: '/admin/media',
            method: 'GET',
            params,
        })
    },

    uploadMedia(file: File, onProgress?: (pct: number) => void): Promise<MediaAsset> {
        const formData = new FormData()
        formData.append('file', file)
        return request<Wrapped<MediaAsset>>({
            url: '/admin/media',
            method: 'POST',
            data: formData,
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: onProgress
                ? (e: { loaded: number; total?: number }) => onProgress(Math.round((e.loaded / (e.total || 1)) * 100))
                : undefined,
        }).then((r) => r.data)
    },

    deleteMedia(id: string): Promise<void> {
        return request({ url: `/admin/media/${id}`, method: 'DELETE' })
    },

    // --- Storage Providers ---
    storageProviders(): Promise<StorageProvider[]> {
        return request<Wrapped<StorageProvider[]>>({
            url: '/admin/storage-providers',
            method: 'GET',
        }).then((r) => r.data)
    },

    updateStorageProvider(id: string, data: Partial<StorageProvider> & { credentials?: Record<string, string> }): Promise<StorageProvider> {
        return request<Wrapped<StorageProvider>>({
            url: `/admin/storage-providers/${id}`,
            method: 'PUT',
            data,
        }).then((r) => r.data)
    },

    // --- Cache Settings ---
    cacheSettings(): Promise<CacheSetting[]> {
        return request<Wrapped<CacheSetting[]>>({
            url: '/admin/cache-settings',
            method: 'GET',
        }).then((r) => r.data)
    },

    updateCacheTtl(id: number, ttlSeconds: number): Promise<CacheSetting> {
        return request<Wrapped<CacheSetting>>({
            url: `/admin/cache-settings/${id}`,
            method: 'PUT',
            data: { ttl_seconds: ttlSeconds },
        }).then((r) => r.data)
    },

    flushCacheTag(tag: string): Promise<void> {
        return request({
            url: '/admin/cache/flush',
            method: 'POST',
            data: { tag },
        })
    },

    // --- Workflows History ---
    workflows(pageId: string): Promise<ContentWorkflow[]> {
        return request<Wrapped<ContentWorkflow[]>>({
            url: `/admin/cms/pages/${pageId}/workflows`,
            method: 'GET',
        }).then((r) => r.data)
    },
}