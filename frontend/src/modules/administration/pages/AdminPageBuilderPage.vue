<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import Select from 'primevue/select'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'

import BlockConfigForm from '@/modules/cms/components/builder/BlockConfigForm.vue'
import BlockPaletteDialog from '@/modules/cms/components/builder/BlockPaletteDialog.vue'
import { blockDefinition, blockLabel } from '@/modules/cms/blockCatalog'
import { usePageBuilderStore, type EditableBlock } from '@/modules/cms/stores/pageBuilder'
import type { BlockType, PageType } from '@/modules/cms/types'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const store = usePageBuilderStore()
const { page, meta, blocks, selectedBlock, loading, saving, publishing, loadError, isDirty } =
  storeToRefs(store)
const { success, error } = useToast()
const { confirm } = useConfirm()

const paletteOpen = ref(false)
const activeTab = ref('block')

const pageTypeOptions: Array<{ label: string; value: PageType }> = [
  { label: 'Inicio', value: 'home' },
  { label: 'Nosotros', value: 'about' },
  { label: 'Contacto', value: 'contact' },
  { label: 'FAQ', value: 'faq' },
  { label: 'Políticas', value: 'policies' },
  { label: 'Blog', value: 'blog' },
  { label: 'Landing Page', value: 'landing' },
]

const statusSeverity = computed(() => {
  switch (page.value?.status) {
    case 'published':
      return 'success'
    case 'archived':
      return 'warn'
    default:
      return 'secondary'
  }
})

const statusLabel = computed(() => {
  switch (page.value?.status) {
    case 'published':
      return 'Publicado'
    case 'archived':
      return 'Archivado'
    default:
      return 'Borrador'
  }
})

const selectedFields = computed(() =>
  selectedBlock.value ? (blockDefinition(selectedBlock.value.type)?.fields ?? []) : [],
)

/** Short, human-readable hint of a block's content for the list. */
function blockSummary(block: EditableBlock): string {
  for (const key of ['heading', 'title', 'body', 'url', 'image']) {
    const value = block.data[key]
    if (typeof value === 'string' && value.trim() !== '') {
      const plain = value.replace(/<[^>]*>/g, '').trim()
      return plain.length > 70 ? `${plain.slice(0, 70)}…` : plain
    }
  }
  const items = block.data.items ?? block.data.images
  if (Array.isArray(items) && items.length > 0) {
    return `${items.length} elemento(s)`
  }
  return 'Sin contenido'
}

function onAddBlock(type: BlockType): void {
  store.addBlock(type)
  paletteOpen.value = false
  activeTab.value = 'block'
}

function onSelectBlock(key: string): void {
  store.selectBlock(key)
  activeTab.value = 'block'
}

async function onRemoveBlock(block: EditableBlock): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar bloque',
    message: `¿Eliminar el bloque "${blockLabel(block.type)}"? Se aplicará al guardar.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (ok) store.removeBlock(block.key)
}

async function onSaveDraft(): Promise<void> {
  try {
    await store.saveDraft()
    success('Borrador guardado')
  } catch {
    error('No fue posible guardar. Revisa los campos obligatorios de cada bloque.')
  }
}

async function onPublish(): Promise<void> {
  try {
    await store.publish()
    success('Página publicada')
  } catch {
    error('No fue posible publicar la página.')
  }
}

async function onUnpublish(): Promise<void> {
  try {
    await store.unpublish()
    success('Página movida a borrador')
  } catch {
    error('No fue posible despublicar la página.')
  }
}

function goBack(): void {
  router.push({ name: 'admin.cms' })
}

onBeforeRouteLeave(async () => {
  if (!isDirty.value) return true
  return confirm({
    title: 'Cambios sin guardar',
    message: 'Hay cambios sin guardar en la página. ¿Salir de todos modos?',
    action: 'delete',
    confirmText: 'Salir sin guardar',
  })
})

onMounted(() => {
  void store.load(Number(route.params.id))
})

onBeforeUnmount(() => {
  store.reset()
})
</script>

<template>
  <div class="builder">
    <!-- Toolbar -->
    <div class="builder__toolbar">
      <div class="builder__identity">
        <Button label="Volver" severity="secondary" text @click="goBack" />
        <div>
          <h1 class="builder__title">{{ meta?.title || 'Editor de página' }}</h1>
          <div class="builder__subtitle">
            <Tag :value="statusLabel" :severity="statusSeverity" />
            <span v-if="page?.brand" class="builder__brand">{{ page.brand.name }}</span>
            <code v-if="meta?.slug" class="builder__slug">/{{ meta.slug }}</code>
            <span v-if="isDirty" class="builder__dirty">Cambios sin guardar</span>
          </div>
        </div>
      </div>
      <div class="builder__actions">
        <Button
          label="Guardar borrador"
          severity="secondary"
          outlined
          :loading="saving"
          :disabled="!isDirty && !saving"
          @click="onSaveDraft"
        />
        <Button
          v-if="page?.status !== 'published'"
          label="Publicar"
          :loading="publishing"
          @click="onPublish"
        />
        <Button
          v-else
          label="Despublicar"
          severity="warn"
          outlined
          :loading="publishing"
          @click="onUnpublish"
        />
      </div>
    </div>

    <div v-if="loading" class="builder__loading">
      <ProgressSpinner />
    </div>

    <Message v-else-if="loadError" severity="error" :closable="false">{{ loadError }}</Message>

    <div v-else-if="page" class="builder__layout">
      <!-- Canvas: ordered block list -->
      <section class="builder__canvas">
        <div class="builder__canvas-header">
          <h2>Estructura de la página</h2>
          <Button label="Agregar bloque" @click="paletteOpen = true" />
        </div>

        <p v-if="blocks.length === 0" class="builder__empty">
          Esta página aún no tiene bloques. Usa "Agregar bloque" para construir su contenido.
        </p>

        <article
          v-for="(block, index) in blocks"
          :key="block.key"
          class="builder__block"
          :class="{
            'builder__block--selected': block.key === store.selectedKey,
            'builder__block--inactive': !block.is_active,
          }"
          @click="onSelectBlock(block.key)"
        >
          <header class="builder__block-header">
            <div class="builder__block-heading">
              <span class="builder__block-position">{{ index + 1 }}</span>
              <span class="builder__block-type">{{ blockLabel(block.type) }}</span>
              <Tag v-if="!block.is_active" value="Oculto" severity="secondary" />
            </div>
            <div class="builder__block-actions" @click.stop>
              <Button label="Subir" size="small" text :disabled="index === 0" @click="store.moveBlock(block.key, -1)" />
              <Button
                label="Bajar"
                size="small"
                text
                :disabled="index === blocks.length - 1"
                @click="store.moveBlock(block.key, 1)"
              />
              <ToggleSwitch
                :model-value="block.is_active"
                :title="block.is_active ? 'Visible en el sitio' : 'Oculto en el sitio'"
                @update:model-value="store.toggleBlock(block.key)"
              />
              <Button label="Eliminar" size="small" text severity="danger" @click="onRemoveBlock(block)" />
            </div>
          </header>
          <p class="builder__block-summary">{{ blockSummary(block) }}</p>
        </article>
      </section>

      <!-- Inspector -->
      <aside class="builder__inspector">
        <Tabs v-model:value="activeTab">
          <TabList>
            <Tab value="block">Bloque</Tab>
            <Tab value="page">Página y SEO</Tab>
          </TabList>
          <TabPanels>
            <TabPanel value="block">
              <template v-if="selectedBlock">
                <div class="builder__inspector-title">
                  {{ blockLabel(selectedBlock.type) }}
                </div>
                <BlockConfigForm :fields="selectedFields" :data="selectedBlock.data" />
              </template>
              <p v-else class="builder__empty">Selecciona un bloque de la izquierda para configurarlo.</p>
            </TabPanel>

            <TabPanel value="page">
              <div v-if="meta" class="builder__page-form">
                <div class="builder__field">
                  <label>Título</label>
                  <InputText v-model="meta.title" fluid />
                </div>
                <div class="builder__field">
                  <label>Slug</label>
                  <InputText v-model="meta.slug" fluid style="font-family: monospace" />
                  <small>Único dentro de la marca. Se regenera desde el título si se deja vacío.</small>
                </div>
                <div class="builder__field">
                  <label>Tipo de página</label>
                  <Select
                    v-model="meta.type"
                    :options="pageTypeOptions"
                    option-label="label"
                    option-value="value"
                    fluid
                  />
                </div>
                <div class="builder__field">
                  <label>Meta título (SEO)</label>
                  <InputText v-model="meta.meta_title" fluid />
                </div>
                <div class="builder__field">
                  <label>Meta descripción (SEO)</label>
                  <Textarea v-model="meta.meta_description" rows="3" auto-resize fluid />
                </div>
              </div>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </aside>
    </div>

    <BlockPaletteDialog :visible="paletteOpen" @close="paletteOpen = false" @select="onAddBlock" />
  </div>
</template>

<style scoped>
.builder__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
}
.builder__identity {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.builder__title {
  margin: 0;
  font-size: 1.25rem;
}
.builder__subtitle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
  flex-wrap: wrap;
}
.builder__brand {
  font-size: 0.8rem;
  color: var(--admin-text-secondary, #5a6a85);
  font-weight: 600;
}
.builder__slug {
  font-size: 0.78rem;
  color: var(--admin-primary, #5d87ff);
}
.builder__dirty {
  font-size: 0.75rem;
  color: var(--admin-warning, #ffae1f);
  font-weight: 600;
}
.builder__actions {
  display: flex;
  gap: 0.5rem;
}
.builder__loading {
  display: flex;
  justify-content: center;
  padding: 4rem;
}
.builder__layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 380px;
  gap: 1.25rem;
  align-items: start;
}
.builder__canvas {
  background: var(--admin-surface, #fff);
  border: 1px solid var(--admin-border, #e5eaef);
  border-radius: var(--admin-radius, 12px);
  padding: 1.25rem;
}
.builder__canvas-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.builder__canvas-header h2 {
  margin: 0;
  font-size: 1rem;
}
.builder__empty {
  font-size: 0.85rem;
  color: var(--admin-text-muted, #7c8fac);
  border: 1px dashed var(--admin-border, #e5eaef);
  border-radius: 8px;
  padding: 1.25rem;
  text-align: center;
}
.builder__block {
  border: 1px solid var(--admin-border, #e5eaef);
  border-radius: 10px;
  padding: 0.875rem 1rem;
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.builder__block:hover {
  border-color: color-mix(in srgb, var(--admin-primary, #5d87ff) 50%, transparent);
}
.builder__block--selected {
  border-color: var(--admin-primary, #5d87ff);
  box-shadow: 0 0 0 1px var(--admin-primary, #5d87ff);
}
.builder__block--inactive {
  opacity: 0.6;
}
.builder__block-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.builder__block-heading {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.builder__block-position {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 50%;
  background: var(--admin-bg, #f5f7fa);
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--admin-text-secondary, #5a6a85);
}
.builder__block-type {
  font-weight: 600;
  font-size: 0.9rem;
}
.builder__block-actions {
  display: flex;
  align-items: center;
  gap: 0.375rem;
}
.builder__block-summary {
  margin: 0.5rem 0 0;
  font-size: 0.8rem;
  color: var(--admin-text-muted, #7c8fac);
}
.builder__inspector {
  background: var(--admin-surface, #fff);
  border: 1px solid var(--admin-border, #e5eaef);
  border-radius: var(--admin-radius, 12px);
  padding: 0.5rem;
  position: sticky;
  top: 1rem;
}
.builder__inspector-title {
  font-weight: 700;
  font-size: 0.9rem;
  margin: 0.5rem 0 1rem;
}
.builder__page-form {
  padding-top: 0.5rem;
}
.builder__field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
  margin-bottom: 1rem;
}
.builder__field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary, #5a6a85);
}
.builder__field small {
  color: var(--admin-text-muted, #7c8fac);
  font-size: 0.75rem;
}
@media (max-width: 1100px) {
  .builder__layout {
    grid-template-columns: 1fr;
  }
  .builder__inspector {
    position: static;
  }
}
</style>
