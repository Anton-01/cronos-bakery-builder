<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

import AdminLayout from '@/layouts/AdminLayout.vue'
import BlankLayout from '@/layouts/BlankLayout.vue'

/**
 * Shell del entry point Admin. Solo conoce los layouts del panel
 * (admin y blank para login/404); jamás importa layouts, estilos ni
 * stores del Storefront.
 */
const layouts = {
  admin: AdminLayout,
  blank: BlankLayout,
} as const

const route = useRoute()
const layout = computed(
  () => layouts[(route.meta.layout as keyof typeof layouts) ?? 'admin'] ?? AdminLayout,
)
</script>

<template>
  <Toast position="top-right" />
  <ConfirmDialog />

  <component :is="layout">
    <RouterView />
  </component>
</template>
