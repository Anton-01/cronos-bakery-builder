import { request } from '@/services/http'

/**
 * Servicio compartido de la Media Library centralizada. Lo consumen el
 * componente global `MediaLibrary.vue` y la página de tipos de archivo.
 * La validación de qué se puede subir vive en BD (`allowed_file_types`).
 */

export interface MediaAsset {
  id: number
  original_name: string
  disk: string
  path: string
  mime_type: string
  size: number
  transformations: Record<string, unknown> | null
  processing_status: 'pending' | 'processing' | 'completed' | 'failed'
  storage_provider_id: number | null
  uploaded_by: number | null
  url: string | null
  created_at: string | null
}

export interface AllowedFileType {
  id: number
  name: string
  category: string
  description: string | null
  mime_types: string[]
  extensions: string[]
  icon_reference: string
  is_active: boolean
}

interface Wrapped<T> { data: T }
interface Paginated<T> { data: T[]; meta?: { current_page: number; last_page: number; total: number } }

export interface MediaListParams {
  page?: number
  per_page?: number
  search?: string
  /** Prefijo MIME ("image/") o MIME exacto ("application/pdf"). */
  mime?: string
  /** Filtra por un tipo del catálogo allowed_file_types. */
  file_type_id?: number
}

export const mediaLibraryService = {
  assets(params: MediaListParams = {}): Promise<Paginated<MediaAsset>> {
    return request<Paginated<MediaAsset>>({ url: '/admin/media', method: 'GET', params })
  },

  upload(file: File, onProgress?: (pct: number) => void): Promise<MediaAsset> {
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

  delete(id: number): Promise<void> {
    return request({ url: `/admin/media/${id}`, method: 'DELETE' })
  },

  fileTypes(onlyActive = false): Promise<AllowedFileType[]> {
    return request<Wrapped<AllowedFileType[]>>({
      url: '/admin/file-types',
      method: 'GET',
      params: onlyActive ? { only_active: 1 } : undefined,
    }).then((r) => r.data)
  },

  updateFileType(id: number, data: Partial<AllowedFileType>): Promise<AllowedFileType> {
    return request<Wrapped<AllowedFileType>>({
      url: `/admin/file-types/${id}`,
      method: 'PUT',
      data,
    }).then((r) => r.data)
  },
}
