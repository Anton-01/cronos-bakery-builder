import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from '@/composables/useToast'
import { adminPanelService } from '../services/adminPanelService'

/**
 * "Ver como usuario": solicita un token temporal al API y abre el storefront
 * público (`builder.preview`) en una pestaña nueva. La pestaña no necesita
 * sesión de admin — el token es la única credencial y expira solo.
 */
export function useProductPreview(productId: () => string | undefined) {
    const { error } = useToast()
    const router = useRouter()

    const previewLoading = ref(false)

    async function openPreview() {
        const pid = productId()
        if (!pid || previewLoading.value) return

        // Abrir la ventana ANTES del await evita que el navegador la bloquee
        // como popup; luego se redirige a la ruta tokenizada.
        const win = window.open('', '_blank')
        previewLoading.value = true
        try {
            const { token } = await adminPanelService.generatePreviewToken(pid)
            const url = router.resolve({ name: 'builder.preview', params: { token } }).href
            if (win) {
                win.location.href = url
            } else {
                window.open(url, '_blank')
            }
        } catch {
            win?.close()
            error('Error al generar la vista previa')
        } finally {
            previewLoading.value = false
        }
    }

    return {
        previewLoading,
        openPreview,
    }
}
