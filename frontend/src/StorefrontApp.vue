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
 *
 * Anti-FOUC: no se renderiza ningún layout hasta que el router resolvió la
 * ruta (`route.matched` vacío = navegación inicial en curso). Las páginas de
 * auth declaran `meta.layout: 'auth'` y montan el AuthLayout aislado.
 */
const layouts = {
  default: DefaultLayout,
  auth: AuthLayout,
  blank: BlankLayout,
} as const

const route = useRoute()

const routeResolved = computed(() => route.matched.length > 0)

const layout = computed(() => {
  const requested = route.meta.layout as keyof typeof layouts | undefined
  return (requested && layouts[requested]) || DefaultLayout
})

const themeStore = useThemeStore()
onMounted(() => { void themeStore.load() })
</script>

<template>
  <component :is="layout" v-if="routeResolved">
    <RouterView />
  </component>
</template>
