import { onBeforeUnmount, ref } from 'vue'
import type { ProductImage } from '../services/adminPanelService'

export interface GalleryImage extends ProductImage {
    _file?: File
    _preview?: string
}

interface FileMeta {
    name: string
    size: string
    type: string
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

export function useMediaGallery() {
    const thumbnail = ref<string | null>(null)
    const thumbnailFile = ref<File | null>(null)
    const thumbnailMeta = ref<FileMeta | null>(null)
    const gallery = ref<GalleryImage[]>([])

    const dragOverThumb = ref(false)
    const dragOverGallery = ref(false)
    const thumbInput = ref<HTMLInputElement | null>(null)
    const galleryInput = ref<HTMLInputElement | null>(null)

    function setThumbFromFile(file: File) {
        thumbnailFile.value = file
        thumbnail.value = URL.createObjectURL(file)
        thumbnailMeta.value = {
            name: file.name,
            size: formatFileSize(file.size),
            type: file.type,
        }
    }

    function onThumbDrop(e: DragEvent) {
        dragOverThumb.value = false
        const file = e.dataTransfer?.files?.[0]
        if (file && file.type.startsWith('image/')) {
            setThumbFromFile(file)
        }
    }

    function onThumbSelect(e: Event) {
        const file = (e.target as HTMLInputElement).files?.[0]
        if (file) setThumbFromFile(file)
    }

    function removeThumb() {
        if (thumbnail.value?.startsWith('blob:')) {
            URL.revokeObjectURL(thumbnail.value)
        }
        thumbnail.value = null
        thumbnailFile.value = null
        thumbnailMeta.value = null
    }

    function onGalleryDrop(e: DragEvent) {
        dragOverGallery.value = false
        const files = e.dataTransfer?.files
        if (files) addGalleryFiles(files)
    }

    function onGallerySelect(e: Event) {
        const files = (e.target as HTMLInputElement).files
        if (files) addGalleryFiles(files)
    }

    function addGalleryFiles(files: FileList) {
        for (const file of Array.from(files)) {
            if (!file.type.startsWith('image/')) continue
            gallery.value.push({
                id: `new-${Date.now()}-${Math.random()}`,
                path: '',
                name: file.name.replace(/\.[^.]+$/, ''),
                alt_text: '',
                position: gallery.value.length,
                _file: file,
                _preview: URL.createObjectURL(file),
            })
        }
    }

    function removeGalleryImage(idx: number) {
        const img = gallery.value[idx]
        if (img._preview) URL.revokeObjectURL(img._preview)
        gallery.value.splice(idx, 1)
    }

    function setThumbnailFromUrl(url: string | null) {
        thumbnail.value = url
        thumbnailFile.value = null
        thumbnailMeta.value = url
            ? { name: url.split('/').pop() || 'image', size: '-', type: 'image/*' }
            : null
    }

    function setGalleryFromImages(images: ProductImage[]) {
        gallery.value = images.map((img) => ({ ...img }))
    }

    function revokeAllBlobUrls() {
        if (thumbnail.value?.startsWith('blob:')) URL.revokeObjectURL(thumbnail.value)
        gallery.value.forEach((img) => {
            if (img._preview) URL.revokeObjectURL(img._preview)
        })
    }

    onBeforeUnmount(revokeAllBlobUrls)

    return {
        thumbnail,
        thumbnailFile,
        thumbnailMeta,
        gallery,
        dragOverThumb,
        dragOverGallery,
        thumbInput,
        galleryInput,
        onThumbDrop,
        onThumbSelect,
        removeThumb,
        onGalleryDrop,
        onGallerySelect,
        removeGalleryImage,
        setThumbnailFromUrl,
        setGalleryFromImages,
    }
}