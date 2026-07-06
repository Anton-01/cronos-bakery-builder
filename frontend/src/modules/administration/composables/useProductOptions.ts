import { computed, ref, type Ref } from 'vue'
import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import {
    adminPanelService,
    type OptionTemplate,
    type ProductOptionLink,
    type PbOption,
} from '../services/adminPanelService'

export interface MappedOptionLink extends ProductOptionLink {
    _mapped?: boolean
}

function mapOptionsToLinks(options: PbOption[]): MappedOptionLink[] {
    return options.map((opt) => ({
        id: opt.id,
        product_id: opt.product_id,
        template_id: opt.id,
        legend: null,
        excluded_value_ids: null,
        position: opt.position,
        _mapped: true,
        template: {
            id: opt.id,
            key: opt.key,
            label: opt.label,
            type: opt.type,
            help_text: opt.help_text,
            is_required: opt.is_required,
            position: opt.position,
            config: opt.config,
            values: opt.values.map((v) => ({
                ...v,
                template_id: opt.id,
            })),
        },
    }))
}

export function useProductOptions(
    productId: () => string | undefined,
    legendContent: Ref<string>,
) {
    const { success, error } = useToast()
    const { confirm } = useConfirm()

    const optionLinks = ref<MappedOptionLink[]>([])
    const allTemplates = ref<OptionTemplate[]>([])
    const showAddOption = ref(false)
    const addOptionTemplateId = ref('')
    const expandedLinks = ref<Set<string>>(new Set())

    const legendModal = ref(false)
    const legendLinkId = ref<string | null>(null)
    const legendOriginal = ref<string | null>(null)

    const availableTemplates = computed(() => {
        const linkedIds = new Set(optionLinks.value.map((l) => l.template_id))
        return allTemplates.value.filter((t) => !linkedIds.has(t.id))
    })

    const legendHasChanged = computed(() => {
        const html = legendContent.value
        const current = html === '<p></p>' || html === '' ? null : html
        return current !== legendOriginal.value
    })

    function getOptionTypeLabel(type: string): string {
        const map: Record<string, string> = {
            select: 'Selector',
            radio: 'Radio',
            checkbox: 'Checkbox',
            color: 'Color',
            image: 'Imagen',
            text: 'Texto',
            textarea: 'Área de texto',
        }
        return map[type] ?? type
    }

    function isValueEnabled(link: ProductOptionLink, valueId: string): boolean {
        return !(link.excluded_value_ids ?? []).includes(valueId)
    }

    function toggleLinkExpand(linkId: string) {
        if (expandedLinks.value.has(linkId)) {
            expandedLinks.value.delete(linkId)
        } else {
            expandedLinks.value.add(linkId)
        }
    }

    function openLegendModal(link: ProductOptionLink) {
        legendLinkId.value = link.id
        legendOriginal.value = link.legend || null
        legendContent.value = link.legend || ''
        legendModal.value = true
    }

    function closeLegendModal() {
        legendModal.value = false
        legendLinkId.value = null
    }

    function resolveTemplateId(link: MappedOptionLink): string | null {
        if (!link._mapped) return link.template_id
        const tpl = allTemplates.value.find((t) => t.key === link.template?.key)
        return tpl?.id ?? null
    }

    async function saveLegend() {
        const pid = productId()
        if (!legendLinkId.value || !pid) return
        const html = legendContent.value
        const content = html === '<p></p>' || html === '' ? null : html
        if (content === legendOriginal.value) {
            legendModal.value = false
            return
        }
        try {
            const idx = optionLinks.value.findIndex((l) => l.id === legendLinkId.value)
            const link = idx !== -1 ? optionLinks.value[idx] : null
            let updated: ProductOptionLink
            if (link?._mapped) {
                const templateId = resolveTemplateId(link)
                if (!templateId) {
                    error('No se encontró la plantilla de opción correspondiente')
                    return
                }
                updated = await adminPanelService.createProductOptionLink(pid, {
                    template_id: templateId,
                    legend: content ?? undefined,
                })
            } else {
                updated = await adminPanelService.updateProductOptionLink(pid, legendLinkId.value, { legend: content })
            }
            if (idx !== -1) optionLinks.value[idx] = updated
            legendModal.value = false
            success('Leyenda actualizada')
        } catch {
            error('Error al guardar la leyenda')
        }
    }

    async function toggleValue(link: MappedOptionLink, valueId: string) {
        const pid = productId()
        if (!pid || !link.template) return
        let excluded = [...(link.excluded_value_ids ?? [])]

        if (excluded.includes(valueId)) {
            excluded = excluded.filter((id) => id !== valueId)
        } else {
            excluded.push(valueId)
        }

        // null = sin exclusiones (hereda todos los valores de la plantilla).
        const excludedIds = excluded.length === 0 ? null : excluded

        try {
            let updated: ProductOptionLink
            if (link._mapped) {
                const templateId = resolveTemplateId(link)
                if (!templateId) {
                    error('No se encontró la plantilla de opción correspondiente')
                    return
                }
                updated = await adminPanelService.createProductOptionLink(pid, {
                    template_id: templateId,
                    excluded_value_ids: excludedIds ?? undefined,
                })
            } else {
                updated = await adminPanelService.updateProductOptionLink(pid, link.id, { excluded_value_ids: excludedIds })
            }
            const idx = optionLinks.value.findIndex((l) => l.id === link.id)
            if (idx !== -1) optionLinks.value[idx] = updated
        } catch {
            error('Error al actualizar valores')
        }
    }

    async function addOptionLink() {
        const pid = productId()
        if (!pid || !addOptionTemplateId.value) return
        try {
            const link = await adminPanelService.createProductOptionLink(pid, {
                template_id: addOptionTemplateId.value,
            })
            optionLinks.value.push(link)
            addOptionTemplateId.value = ''
            showAddOption.value = false
            success('Opción vinculada al producto')
        } catch {
            error('Error al vincular opción')
        }
    }

    async function removeOptionLink(link: MappedOptionLink) {
        const tplName = link.template?.label || 'esta opción'
        const ok = await confirm({
            title: 'Desvincular opción',
            message: `Se eliminará la vinculación de "${tplName}" con este producto. Los valores configurados se perderán.`,
            action: 'delete',
            confirmText: 'Desvincular',
        })
        const pid = productId()
        if (!ok || !pid) return

        try {
            if (!link._mapped) {
                await adminPanelService.deleteProductOptionLink(pid, link.id)
            }
            optionLinks.value = optionLinks.value.filter((l) => l.id !== link.id)
            success('Opción desvinculada')
        } catch {
            error('Error al desvincular opción')
        }
    }

    async function loadOptionLinks() {
        const pid = productId()
        if (!pid) return
        try {
            const [links, templates] = await Promise.all([
                adminPanelService.productOptionLinks(pid),
                adminPanelService.optionTemplates(),
            ])
            if (links.length) {
                optionLinks.value = links
            }
            allTemplates.value = templates
        } catch {
            error('Error al cargar las opciones del producto')
        }
    }

    function setLinksFromOptions(options: PbOption[]) {
        if (options.length) {
            optionLinks.value = mapOptionsToLinks(options)
        }
    }

    return {
        optionLinks,
        allTemplates,
        showAddOption,
        addOptionTemplateId,
        availableTemplates,
        expandedLinks,
        legendModal,
        legendLinkId,
        legendContent,
        legendHasChanged,
        getOptionTypeLabel,
        isValueEnabled,
        toggleLinkExpand,
        openLegendModal,
        closeLegendModal,
        saveLegend,
        toggleValue,
        addOptionLink,
        removeOptionLink,
        loadOptionLinks,
        setLinksFromOptions,
    }
}
