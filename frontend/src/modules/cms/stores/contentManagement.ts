import { defineStore } from 'pinia'
import { ref } from 'vue'
import { cmsContentService } from '../services/cmsContentService'
import { pageBuilderService } from '../services/pageBuilderService'
import { useOptimistic } from '@/composables/useOptimistic'
import { useSudo } from '@/composables/useSudo'
import { useToast } from '@/composables/useToast'
import type { CmsPage, ContentVersion, ContentWorkflow } from '../types'

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
            pages.value = await pageBuilderService.pages()
        } finally {
            loading.value = false
        }
    }

    async function fetchVersions(pageId: number) {
        versionsLoading.value = true
        try {
            versions.value = await cmsContentService.versions(pageId)
        } finally {
            versionsLoading.value = false
        }
    }

    async function fetchWorkflows(pageId: number) {
        workflows.value = await cmsContentService.workflows(pageId)
    }

    async function submitForReview(pageId: number, comment?: string) {
        await optimisticUpdate(
            pages,
            (p) => p.id === pageId,
            { status: 'draft' },
            async () => {
                await cmsContentService.submitForReview(pageId, comment)
                success('Contenido enviado a revisión')
                return pages.value.find((p) => p.id === pageId)!
            },
        )
    }

    async function approvePublication(pageId: number, comment?: string) {
        await withSudo(async () => {
            await optimisticUpdate(
                pages,
                (p) => p.id === pageId,
                { status: 'published' },
                async () => {
                    await cmsContentService.approve(pageId, comment)
                    success('Contenido publicado exitosamente')
                    return pages.value.find((p) => p.id === pageId)!
                },
            )
        })
    }

    async function rejectContent(pageId: number, reason?: string) {
        await cmsContentService.reject(pageId, reason)
        const idx = pages.value.findIndex((p) => p.id === pageId)
        if (idx !== -1) {
            pages.value[idx] = { ...pages.value[idx], status: 'draft' }
        }
        success('Contenido devuelto a borrador')
    }

    async function schedulePublication(pageId: number, publishAt: string) {
        await withSudo(async () => {
            await cmsContentService.schedule(pageId, publishAt)
            success('Publicación programada')
        })
    }

    async function rollbackToVersion(pageId: number, versionId: number) {
        await withSudo(async () => {
            await cmsContentService.rollback(pageId, versionId)
            await fetchPages()
            await fetchVersions(pageId)
            success('Rollback ejecutado correctamente')
        })
    }

    function updatePageInList(pageId: number, patch: Partial<CmsPage>) {
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
