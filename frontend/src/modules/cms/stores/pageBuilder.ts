import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { blockDefinition } from '../blockCatalog'
import { pageBuilderService } from '../services/pageBuilderService'
import type { BlockType, CmsPage, PageBlockPayload, PagePayload, PageStatus, PageType } from '../types'

/**
 * A block as edited in the builder. Persisted blocks keep their numeric `id`;
 * new ones carry only the local `key` until the next save assigns them one.
 */
export interface EditableBlock {
  key: string
  id: number | null
  type: BlockType
  data: Record<string, unknown>
  is_active: boolean
  section_id: number | null
}

/** Editable page metadata (the non-block part of the builder). */
export interface PageMeta {
  title: string
  slug: string
  type: PageType
  meta_title: string
  meta_description: string
  settings: Record<string, unknown> | null
}

let localKeySequence = 0

function nextKey(): string {
  localKeySequence += 1
  return `local-${localKeySequence}`
}

function toEditableBlocks(page: CmsPage): EditableBlock[] {
  return [...page.blocks]
    .sort((a, b) => a.position - b.position)
    .map((block) => ({
      key: `db-${block.id}`,
      id: block.id,
      type: block.type,
      data: JSON.parse(JSON.stringify(block.config)) as Record<string, unknown>,
      is_active: block.is_active,
      section_id: block.section_id,
    }))
}

function toMeta(page: CmsPage): PageMeta {
  return {
    title: page.title,
    slug: page.slug,
    type: page.type,
    meta_title: page.seo.meta_title ?? '',
    meta_description: page.seo.meta_description ?? '',
    settings: page.settings,
  }
}

/**
 * Editing state of one page inside the builder: load → edit in memory (with
 * dirty tracking) → save draft (metadata + bulk block sync) → publish.
 */
export const usePageBuilderStore = defineStore('pageBuilder', () => {
  const page = ref<CmsPage | null>(null)
  const meta = ref<PageMeta | null>(null)
  const blocks = ref<EditableBlock[]>([])
  const selectedKey = ref<string | null>(null)

  const loading = ref(false)
  const saving = ref(false)
  const publishing = ref(false)
  const loadError = ref<string | null>(null)

  /** Serialized snapshot of the last persisted state, for dirty tracking. */
  const savedSnapshot = ref('')

  const status = computed<PageStatus | null>(() => page.value?.status ?? null)

  const selectedBlock = computed(
    () => blocks.value.find((block) => block.key === selectedKey.value) ?? null,
  )

  const isDirty = computed(() => snapshot() !== savedSnapshot.value)

  function snapshot(): string {
    return JSON.stringify({ meta: meta.value, blocks: blocks.value })
  }

  function markSaved(): void {
    savedSnapshot.value = snapshot()
  }

  function hydrate(loaded: CmsPage): void {
    page.value = loaded
    meta.value = toMeta(loaded)
    blocks.value = toEditableBlocks(loaded)
    if (!blocks.value.some((block) => block.key === selectedKey.value)) {
      selectedKey.value = blocks.value[0]?.key ?? null
    }
    markSaved()
  }

  async function load(pageId: number): Promise<void> {
    loading.value = true
    loadError.value = null
    try {
      hydrate(await pageBuilderService.page(pageId))
    } catch {
      loadError.value = 'No fue posible cargar la página.'
    } finally {
      loading.value = false
    }
  }

  // --- In-memory block editing ----------------------------------------------

  function addBlock(type: BlockType): void {
    const definition = blockDefinition(type)
    const block: EditableBlock = {
      key: nextKey(),
      id: null,
      type,
      data: JSON.parse(JSON.stringify(definition?.defaults ?? {})) as Record<string, unknown>,
      is_active: true,
      section_id: null,
    }
    blocks.value.push(block)
    selectedKey.value = block.key
  }

  function removeBlock(key: string): void {
    blocks.value = blocks.value.filter((block) => block.key !== key)
    if (selectedKey.value === key) {
      selectedKey.value = blocks.value[0]?.key ?? null
    }
  }

  function moveBlock(key: string, direction: -1 | 1): void {
    const index = blocks.value.findIndex((block) => block.key === key)
    const target = index + direction
    if (index === -1 || target < 0 || target >= blocks.value.length) return
    const reordered = [...blocks.value]
    const [moved] = reordered.splice(index, 1)
    reordered.splice(target, 0, moved)
    blocks.value = reordered
  }

  function toggleBlock(key: string): void {
    const block = blocks.value.find((candidate) => candidate.key === key)
    if (block) block.is_active = !block.is_active
  }

  function selectBlock(key: string): void {
    selectedKey.value = key
  }

  // --- Persistence -----------------------------------------------------------

  function blockPayloads(): PageBlockPayload[] {
    return blocks.value.map((block) => ({
      id: block.id,
      section_id: block.section_id,
      type: block.type,
      data: block.data,
      is_active: block.is_active,
    }))
  }

  /**
   * Persist the current builder state without altering the publication
   * status: page metadata first, then the full ordered block list.
   */
  async function saveDraft(): Promise<CmsPage | null> {
    if (!page.value || !meta.value) return null
    saving.value = true
    try {
      const payload: Omit<PagePayload, 'brand_id' | 'blocks'> = {
        title: meta.value.title,
        slug: meta.value.slug || null,
        type: meta.value.type,
        meta_title: meta.value.meta_title || null,
        meta_description: meta.value.meta_description || null,
        settings: meta.value.settings,
        status: page.value.status,
      }
      await pageBuilderService.updatePage(page.value.id, payload)
      const saved = await pageBuilderService.syncBlocks(page.value.id, blockPayloads())
      hydrate(saved)
      return saved
    } finally {
      saving.value = false
    }
  }

  /** Save any pending changes, then move the page to `published`. */
  async function publish(): Promise<CmsPage | null> {
    if (!page.value) return null
    publishing.value = true
    try {
      if (isDirty.value) {
        await saveDraft()
      }
      const published = await pageBuilderService.publish(page.value.id)
      hydrate(published)
      return published
    } finally {
      publishing.value = false
    }
  }

  async function unpublish(): Promise<CmsPage | null> {
    if (!page.value) return null
    publishing.value = true
    try {
      const draft = await pageBuilderService.unpublish(page.value.id)
      hydrate(draft)
      return draft
    } finally {
      publishing.value = false
    }
  }

  function reset(): void {
    page.value = null
    meta.value = null
    blocks.value = []
    selectedKey.value = null
    loadError.value = null
    savedSnapshot.value = ''
  }

  return {
    page,
    meta,
    blocks,
    selectedKey,
    selectedBlock,
    status,
    loading,
    saving,
    publishing,
    loadError,
    isDirty,
    load,
    addBlock,
    removeBlock,
    moveBlock,
    toggleBlock,
    selectBlock,
    saveDraft,
    publish,
    unpublish,
    reset,
  }
})
