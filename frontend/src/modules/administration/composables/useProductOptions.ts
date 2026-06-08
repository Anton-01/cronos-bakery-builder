import { computed, ref } from 'vue'
import type { Editor } from '@tiptap/vue-3'
import type { ShallowRef } from 'vue'
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
        enabled_value_ids: null,
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
    legendEditor: ShallowRef<Editor | undefined>,
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
        const html = legendEditor.value?.getHTML() || ''
        const current = html === '<p></p>' ? null : html
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
        if (!link.enabled_value_ids) return true
        return link.enabled_value_ids.includes(valueId)
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
        legendEditor.value?.commands.setContent(link.legend || '')
        legendModal.value = true
    }

    function closeLegendModal() {
        legendModal.value = false
        legendLinkId.value = null
    }

    async function saveLegend() {
        const pid = productId()
        if (!legendLinkId.value || !pid) return
        const html = legendEditor.value?.getHTML() || ''
        const content = html === '<p></p>' ? null : html
        if (content === legendOriginal.value) {
            legendModal.value = false
            return
        }
        try {
            const idx = optionLinks.value.findIndex((l) => l.id === legendLinkId.value)
            const link = idx !== -1 ? optionLinks.value[idx] : null
            let updated: ProductOptionLink
            if (link?._mapped) {
                updated = await adminPanelService.createProductOptionLink(pid, {
                    template_id: link.template_id,
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
        const allIds = link.template.values.map((v) => v.id)
        let current = link.enabled_value_ids ? [...link.enabled_value_ids] : [...allIds]

        if (current.includes(valueId)) {
            current = current.filter((id) => id !== valueId)
        } else {
            current.push(valueId)
        }

        const enabledIds = current.length === allIds.length ? null : current

        try {
            let updated: ProductOptionLink
            if (link._mapped) {
                updated = await adminPanelService.createProductOptionLink(pid, {
                    template_id: link.template_id,
                    enabled_value_ids: enabledIds ?? undefined,
                })
            } else {
                updated = await adminPanelService.updateProductOptionLink(pid, link.id, { enabled_value_ids: enabledIds })
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