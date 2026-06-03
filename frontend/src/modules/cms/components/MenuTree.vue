<script setup lang="ts">
import { RouterLink } from 'vue-router'

import type { MenuItemNode } from '../types'

defineProps<{ items: MenuItemNode[] }>()

/** Internal links start with "/"; everything else is treated as external. */
function isInternal(url: string | null): boolean {
  return !!url && url.startsWith('/')
}
</script>

<template>
  <ul class="menu-tree">
    <li v-for="item in items" :key="item.id" class="menu-tree__item">
      <RouterLink v-if="isInternal(item.url)" :to="item.url!">{{ item.label }}</RouterLink>
      <a v-else-if="item.url" :href="item.url" :target="item.target">{{ item.label }}</a>
      <span v-else>{{ item.label }}</span>

      <!-- Nested submenu (recursive). -->
      <MenuTree v-if="item.children.length" :items="item.children" class="menu-tree--nested" />
    </li>
  </ul>
</template>
