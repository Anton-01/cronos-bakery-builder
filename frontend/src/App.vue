<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'
import { useThemeStore } from '@/stores/theme'

const layouts = {
  default: DefaultLayout, admin: AdminLayout, auth: AuthLayout, blank: BlankLayout,
} as const

const route = useRoute()
const layout = computed(() => layouts[(route.meta.layout as keyof typeof layouts) ?? 'default'] ?? DefaultLayout)

const themeStore = useThemeStore()
onMounted(() => { void themeStore.load() })
</script>

<template>
  <Toast position="top-right" />
  <ConfirmDialog />

  <component :is="layout">
    <RouterView />
  </component>
</template>
