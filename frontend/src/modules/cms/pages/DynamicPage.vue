<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import BlockRenderer from '../components/BlockRenderer.vue'
import { cmsService } from '../services/cmsService'
import type { CmsPage } from '../types'

const props = defineProps<{ slug?: string }>()
const route = useRoute()

const page = ref<CmsPage | null>(null)
const loading = ref(true)
const notFound = ref(false)

function resolveSlug(): string {
  return props.slug ?? (route.params.slug as string)
}

/** Reflect the page's SEO metadata into the document head. */
function applySeo(p: CmsPage): void {
  document.title = p.seo.meta_title || p.title
  if (p.seo.meta_description) {
    let tag = document.querySelector('meta[name="description"]')
    if (!tag) {
      tag = document.createElement('meta')
      tag.setAttribute('name', 'description')
      document.head.appendChild(tag)
    }
    tag.setAttribute('content', p.seo.meta_description)
  }
}

async function load(): Promise<void> {
  loading.value = true
  notFound.value = false
  try {
    const result = await cmsService.page(resolveSlug())
    page.value = result
    applySeo(result)
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => resolveSlug(), load)
</script>

<template>
  <div class="dynamic-page">
    <p v-if="loading" class="dynamic-page__state">Loading…</p>
    <p v-else-if="notFound" class="dynamic-page__state">This page is not available.</p>
    <template v-else-if="page">
      <BlockRenderer v-for="block in page.sections" :key="block.id" :block="block" />
    </template>
  </div>
</template>
