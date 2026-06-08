import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useToast } from '@/composables/useToast'
import { adminPanelService } from '../services/adminPanelService'

export interface PreviewData {
    name: string
    image: string | null
    description: string | null
    base_price: { amount: number; currency: string }
    tags: string | null
}

export function useProductPreview(productId: () => string | undefined) {
    const { error } = useToast()

    const previewVisible = ref(false)
    const previewLoading = ref(false)
    const previewData = ref<PreviewData | null>(null)

    async function openPreview() {
        const pid = productId()
        if (!pid) return
        previewLoading.value = true
        previewVisible.value = true
        try {
            const { token } = await adminPanelService.generatePreviewToken(pid)
            const data = await adminPanelService.getPreview(token)
            previewData.value = data as unknown as PreviewData
        } catch {
            error('Error al cargar la vista previa')
            previewVisible.value = false
        } finally {
            previewLoading.value = false
        }
    }

    function closePreview() {
        previewVisible.value = false
        previewData.value = null
    }

    function onPreviewKeydown(e: KeyboardEvent) {
        if (e.key === 'Escape' && previewVisible.value) closePreview()
    }

    onMounted(() => document.addEventListener('keydown', onPreviewKeydown))
    onBeforeUnmount(() => document.removeEventListener('keydown', onPreviewKeydown))

    return {
        previewVisible,
        previewLoading,
        previewData,
        openPreview,
        closePreview,
    }
}