<script setup lang="ts">
import { computed } from 'vue'

import type { PageBlock, BlockType } from '../types'

import HeroBlock from './blocks/HeroBlock.vue'
import BannerBlock from './blocks/BannerBlock.vue'
import GalleryBlock from './blocks/GalleryBlock.vue'
import CardsBlock from './blocks/CardsBlock.vue'
import TextBlock from './blocks/TextBlock.vue'
import VideoBlock from './blocks/VideoBlock.vue'
import CtaBlock from './blocks/CtaBlock.vue'
import FaqBlock from './blocks/FaqBlock.vue'
import TestimonialsBlock from './blocks/TestimonialsBlock.vue'
import ProductsBlock from './blocks/ProductsBlock.vue'

const props = defineProps<{ block: PageBlock }>()

/** Maps a stored block type to the component that renders it. */
const registry: Record<BlockType, unknown> = {
  hero: HeroBlock,
  banner: BannerBlock,
  gallery: GalleryBlock,
  cards: CardsBlock,
  text: TextBlock,
  video: VideoBlock,
  cta: CtaBlock,
  faq: FaqBlock,
  testimonials: TestimonialsBlock,
  products: ProductsBlock,
}

const component = computed(() => registry[props.block.type] ?? null)
</script>

<template>
  <component :is="component" v-if="component" :config="block.config" />
</template>
