import { defineStore } from 'pinia'
import { ref } from 'vue'
import { cmsContentService } from '../services/cmsContentService'
import { adminPanelService, type CmsPage } from '@/modules/administration/services/adminPanelService'
import { useOptimistic } from '@/composables/useOptimistic'
import { useSudo } from '@/composables/useSudo'
import { useToast } from '@/composables/useToast'
import type { ContentVersion, ContentWorkflow } from '../types'

export const useContentManagementStore = defineStore('contentManagement', () => {
    const pages = ref<CmsPage[]>([])
    const versions = ref<ContentVersion[]>([])
    const workflows = ref<ContentWorkflow[]>([])
    const loading = ref(false)
    const versionsLoading = ref(false)

    const { optimisticUpdate } = useOptimistic()
    const { withSudo } = useSudo()
    const { success } = useToast()

    async function fetchPages() {
        loading.value = true
        try {
            pages.value = await adminPanelService.cmsPages()
        } finally {
            loading.value = false
        }
    }

    async function fetchVersions(pageId: string) {
        versionsLoading.value = true
        try {
            versions.value = await cmsContentService.versions(pageId)
        } finally {
            versionsLoading.value = false
        }
    }

    async function fetchWorkflows(pageId: string) {
        workflows.value = await cmsContentService.workflows(pageId)
    }

    async function submitForReview(pageId: string, comment?: string) {
        await optimisticUpdate(
            pages,
            (p) => p.id === pageId,
            { is_published: false },
            async () => {
                await cmsContentService.submitForReview(pageId, comment)
                success('Contenido enviado a revisión')
                return pages.value.find((p) => p.id === pageId)!
            },
        )
    }

    async function approvePublication(pageId: string, comment?: string) {
        await withSudo(async () => {
            await optimisticUpdate(
                pages,
                (p) => p.id === pageId,
                { is_published: true },
                async () => {
                    await cmsContentService.approve(pageId, comment)
                    success('Contenido publicado exitosamente')
                    return pages.value.find((p) => p.id === pageId)!
                },
            )
        })
    }

    async function rejectContent(pageId: string, reason?: string) {
        await cmsContentService.reject(pageId, reason)
        const idx = pages.value.findIndex((p) => p.id === pageId)
        if (idx !== -1) {
            pages.value[idx] = { ...pages.value[idx], is_published: false }
        }
        success('Contenido devuelto a borrador')
    }

    async function schedulePublication(pageId: string, publishAt: string) {
        await withSudo(async () => {
            await cmsContentService.schedule(pageId, publishAt)
            success('Publicación programada')
        })
    }

    async function rollbackToVersion(pageId: string, versionId: string) {
        await withSudo(async () => {
            await cmsContentService.rollback(pageId, versionId)
            await fetchPages()
            await fetchVersions(pageId)
            success('Rollback ejecutado correctamente')
        })
    }

    function updatePageInList(pageId: string, patch: Partial<CmsPage>) {
        const idx = pages.value.findIndex((p) => p.id === pageId)
        if (idx !== -1) {
            pages.value[idx] = { ...pages.value[idx], ...patch }
        }
    }

    return {
        pages,
        versions,
        workflows,
        loading,
        versionsLoading,
        fetchPages,
        fetchVersions,
        fetchWorkflows,
        submitForReview,
        approvePublication,
        rejectContent,
        schedulePublication,
        rollbackToVersion,
        updatePageInList,
    }
})