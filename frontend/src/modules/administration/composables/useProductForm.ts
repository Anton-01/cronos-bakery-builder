import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import type { Editor } from '@tiptap/vue-3'
import type { ShallowRef } from 'vue'
import { useToast } from '@/composables/useToast'
import {
    adminPanelService,
    type AdminProductDetail,
    type PbOption,
} from '../services/adminPanelService'
import type { GalleryImage } from './useMediaGallery'

export type ProductStatus = 'draft' | 'private' | 'public'

export interface ProductFormState {
    name: string
    slug: string
    description: string
    status: ProductStatus
    base_price_amount: number
    base_price_currency: string
    discount_type: 'none' | 'percentage' | 'fixed'
    discount_value: number
    tax_class: string
    vat: number
    tags: string
}

export const statusOptions: { value: ProductStatus; label: string; desc: string }[] = [
    { value: 'draft', label: 'Borrador', desc: 'No visible, en edición' },
    { value: 'private', label: 'Privado', desc: 'Solo accesible con enlace directo' },
    { value: 'public', label: 'Público', desc: 'Visible en la tienda' },
]

function generateSlug(name: string): string {
    return name
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '')
}

function isBlobUrl(url: string | null | undefined): boolean {
    return !!url && url.startsWith('blob:')
}

interface UseProductFormDeps {
    editor: ShallowRef<Editor | undefined>
    thumbnail: { value: string | null }
    thumbnailFile: { value: File | null }
    gallery: { value: GalleryImage[] }
    setThumbnailFromUrl: (url: string | null) => void
    setGalleryFromImages: (images: { id: string; path: string; name: string | null; alt_text: string | null; position: number }[]) => void
    setLinksFromOptions: (options: PbOption[]) => void
    loadOptionLinks: () => Promise<void>
}

export function useProductForm(deps: UseProductFormDeps) {
    const route = useRoute()
    const router = useRouter()
    const { success, error } = useToast()

    const productId = computed(() => route.params.id as string | undefined)
    const isEdit = computed(() => !!productId.value)
    const loading = ref(false)
    const saving = ref(false)

    const form = reactive<ProductFormState>({
        name: '',
        slug: '',
        description: '',
        status: 'draft',
        base_price_amount: 0,
        base_price_currency: 'MXN',
        discount_type: 'none',
        discount_value: 0,
        tax_class: 'standard',
        vat: 16,
        tags: '',
    })

    const formSnapshot = ref('')

    const isDirty = computed(() => {
        if (!isEdit.value) return true
        return JSON.stringify(form) !== formSnapshot.value || deps.thumbnailFile.value !== null
    })

    function onNameInput() {
        if (!isEdit.value) {
            form.slug = generateSlug(form.name)
        }
    }

    function hydrateForm(p: AdminProductDetail) {
        form.name = p.name
        form.slug = p.slug
        form.description = p.description ?? ''
        form.status = p.is_active ? 'public' : 'draft'
        form.base_price_amount = p.base_price.amount
        form.base_price_currency = p.base_price.currency

        const detail = p as AdminProductDetail & {
            discount_type?: string
            discount_value?: number
            tax_class?: string
            vat?: number
            tags?: string
        }
        form.discount_type = (detail.discount_type as ProductFormState['discount_type']) ?? 'none'
        form.discount_value = detail.discount_value ?? 0
        form.tax_class = detail.tax_class ?? 'standard'
        form.vat = detail.vat ?? 16
        form.tags = detail.tags ?? ''
    }

    async function loadProduct() {
        if (!productId.value) return
        loading.value = true
        try {
            const p = await adminPanelService.showProduct(productId.value)
            hydrateForm(p)

            const imageUrl = isBlobUrl(p.image) ? null : (p.image ?? null)
            deps.setThumbnailFromUrl(imageUrl)
            deps.setGalleryFromImages(p.gallery ?? [])
            deps.editor.value?.commands.setContent(form.description)

            if (p.options?.length) {
                deps.setLinksFromOptions(p.options)
            }

            formSnapshot.value = JSON.stringify(form)
        } catch {
            error('Error al cargar el producto')
        } finally {
            loading.value = false
        }
    }

    async function uploadImages(id: string) {
        if (deps.thumbnailFile.value) {
            await adminPanelService.uploadProductImage(id, deps.thumbnailFile.value, 'image')
            deps.thumbnailFile.value = null
        }
        for (const img of deps.gallery.value) {
            if (img._file) {
                await adminPanelService.uploadProductImage(id, img._file, 'gallery')
            }
        }
        const existingGallery = deps.gallery.value.filter((img) => !img.id.startsWith('new-'))
        for (const img of existingGallery) {
            await adminPanelService.updateProductImage(id, img.id, {
                name: img.name ?? undefined,
                alt_text: img.alt_text ?? undefined,
            })
        }
    }

    async function submitForm() {
        saving.value = true
        try {
            const payload: Record<string, unknown> = {
                name: form.name,
                slug: form.slug,
                description: form.description || null,
                is_active: form.status === 'public',
                base_price_amount: form.base_price_amount,
                currency: form.base_price_currency,
                discount_type: form.discount_type,
                discount_value: form.discount_value,
                tax_class: form.tax_class,
                vat: form.vat,
                tags: form.tags || null,
            }

            if (deps.thumbnail.value && !deps.thumbnailFile.value && !isBlobUrl(deps.thumbnail.value)) {
                payload.image = deps.thumbnail.value
            }
            if (!deps.thumbnail.value) {
                payload.image = null
            }

            if (isEdit.value) {
                await adminPanelService.updateProduct(productId.value!, payload)
                await uploadImages(productId.value!)
                await Promise.all([loadProduct(), deps.loadOptionLinks()])
                success('Producto actualizado exitosamente')
            } else {
                const created = await adminPanelService.createProduct(payload)
                await uploadImages(created.id)
                success('Producto creado exitosamente')
                router.replace(`/admin/productos/${created.id}`)
            }
        } catch {
            error('Error al guardar el producto')
        } finally {
            saving.value = false
        }
    }

    return {
        productId,
        isEdit,
        loading,
        saving,
        form,
        isDirty,
        onNameInput,
        loadProduct,
        submitForm,
    }
}