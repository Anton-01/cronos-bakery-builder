<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Toaster } from 'vue-sonner'

import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'
import { useThemeStore } from '@/stores/theme'

/**
 * Routes declare which shell they want via `meta.layout`. Anything unspecified
 * falls back to the public-facing default layout.
 */
const layouts = {
  default: DefaultLayout, admin: AdminLayout, auth: AuthLayout, blank: BlankLayout,
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
  <Teleport to="body">
    <Toaster
        position="top-right"
        :richColors="false"
        closeButton
        :toastOptions="{
          style: {
            fontSize: '0.875rem',
            borderRadius: '12px',
            padding: '16px 20px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.1)',
            border: '1px solid',
            maxWidth: '420px',
          },
      }"
    />
  </Teleport>
  <component :is="layout">
    <RouterView />
  </component>
</template>
