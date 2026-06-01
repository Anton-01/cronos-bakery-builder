<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'

import { themeService } from '../services/themeService'
import type { Banner } from '../types'

const props = defineProps<{ placement: string }>()

const banners = ref<Banner[]>([])

async function load(): Promise<void> {
  try {
    banners.value = await themeService.banners(props.placement)
  } catch {
    banners.value = []
  }
}

onMounted(load)
watch(() => props.placement, load)
</script>

<template>
  <div v-if="banners.length" class="banner-slot">
    <component
      :is="banner.link ? 'a' : 'div'"
      v-for="banner in banners"
      :key="banner.id"
      :href="banner.link ?? undefined"
      class="banner-slot__item"
    >
      <img :src="banner.image" :alt="banner.title" />
    </component>
  </div>
</template>
