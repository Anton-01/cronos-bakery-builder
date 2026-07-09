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
 *
 * Anti-FOUC (doble candado con el `router.isReady()` del entry):
 *  - No se renderiza NINGÚN layout hasta que la ruta esté resuelta
 *    (`route.matched` vacío = navegación inicial aún en curso).
 *  - El fallback ante un meta.layout desconocido es BlankLayout — el chrome
 *    del Dashboard (AdminLayout) SOLO se pinta cuando la ruta lo declara
 *    explícitamente, nunca por defecto.
 */
const layouts = {
  admin: AdminLayout,
  blank: BlankLayout,
} as const

const route = useRoute()

const routeResolved = computed(() => route.matched.length > 0)

const layout = computed(() => {
  const requested = route.meta.layout as keyof typeof layouts | undefined
  return (requested && layouts[requested]) || BlankLayout
})
</script>

<template>
  <Toast position="top-right" />
  <ConfirmDialog />

  <component :is="layout" v-if="routeResolved">
    <RouterView />
  </component>
</template>
