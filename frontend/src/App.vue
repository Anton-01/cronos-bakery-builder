<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Toaster } from 'vue-sonner'

import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useThemeStore } from '@/stores/theme'

/**
 * Routes declare which shell they want via `meta.layout`. Anything unspecified
 * falls back to the public-facing default layout.
 */
const layouts = {
  default: DefaultLayout,
  admin: AdminLayout,
  auth: AuthLayout,
} as const

const route = useRoute()
const layout = computed(
  () => layouts[(route.meta.layout as keyof typeof layouts) ?? 'default'] ?? DefaultLayout,
)

// Load and apply the active branding theme as soon as the app boots.
const themeStore = useThemeStore()
onMounted(() => {
  void themeStore.load()
})
</script>

<template>
  <Toaster
    position="top-right"
    :toastOptions="{
      style: {
        fontFamily: 'var(--admin-font, inherit)',
        fontSize: '0.875rem',
        borderRadius: '10px',
        boxShadow: '0 4px 20px rgba(0,0,0,0.06)',
        border: '1px solid rgba(0,0,0,0.04)',
        padding: '14px 18px',
      },
      class: 'cronos-toast',
    }"
    richColors
  />
  <component :is="layout">
    <RouterView />
  </component>
</template>
