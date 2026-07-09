<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'
import { useThemeStore } from '@/stores/theme'

/**
 * Shell del entry point Storefront (sitio público del cliente).
 * Carga el tema activo (CSS vars + Google Fonts) y solo conoce los layouts
 * públicos; el panel admin vive en un entry point separado (admin.html).
 */
const layouts = {
  default: DefaultLayout,
  auth: AuthLayout,
  blank: BlankLayout,
} as const

const route = useRoute()
const layout = computed(
  () => layouts[(route.meta.layout as keyof typeof layouts) ?? 'default'] ?? DefaultLayout,
)

const themeStore = useThemeStore()
onMounted(() => { void themeStore.load() })
</script>

<template>
  <component :is="layout">
    <RouterView />
  </component>
</template>
