<script setup lang="ts">
import { RouterLink } from 'vue-router'

import type { Breadcrumb } from '../types'

defineProps<{ items: Breadcrumb[] }>()

function to(crumb: Breadcrumb): string {
  if (crumb.type === 'catalog') return '/catalog'
  if (crumb.type === 'category') return `/categoria/${crumb.slug}`
  return `/pastel/${crumb.slug}`
}
</script>

<template>
  <nav class="breadcrumbs" aria-label="breadcrumb">
    <ol>
      <li v-for="(crumb, index) in items" :key="index">
        <RouterLink v-if="index < items.length - 1" :to="to(crumb)">{{ crumb.label }}</RouterLink>
        <span v-else aria-current="page">{{ crumb.label }}</span>
        <span v-if="index < items.length - 1" class="breadcrumbs__sep">/</span>
      </li>
    </ol>
  </nav>
</template>
