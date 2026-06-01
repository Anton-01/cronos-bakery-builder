<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { RouterLink } from 'vue-router'

import MenuTree from '@/modules/cms/components/MenuTree.vue'
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()
const { theme, headerMenu, footer, logo } = storeToRefs(themeStore)
</script>

<template>
  <div class="layout layout--default">
    <header class="layout__header">
      <RouterLink to="/" class="layout__brand">
        <img v-if="logo" :src="logo" :alt="theme?.name ?? 'Cronos Bakery'" class="layout__logo" />
        <span v-else>{{ theme?.name ?? 'Cronos Bakery' }}</span>
      </RouterLink>

      <!-- Dynamic, CMS-driven navigation menu. -->
      <nav class="layout__nav">
        <MenuTree v-if="headerMenu" :items="headerMenu.items" />
        <template v-else>
          <RouterLink to="/catalog">Catalog</RouterLink>
          <RouterLink to="/builder">Build a Cake</RouterLink>
        </template>
      </nav>
    </header>

    <main class="layout__content">
      <slot />
    </main>

    <footer class="layout__footer">
      <div v-if="footer && footer.columns.length" class="layout__footer-cols">
        <div v-for="(col, index) in footer.columns" :key="index" class="layout__footer-col">
          <h4>{{ col.title }}</h4>
          <ul>
            <li v-for="link in col.links" :key="link.url">
              <RouterLink :to="link.url">{{ link.label }}</RouterLink>
            </li>
          </ul>
        </div>
      </div>
      <small>{{ footer?.copyright ?? `© ${new Date().getFullYear()} Cronos Bakery Builder` }}</small>
    </footer>
  </div>
</template>
