<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'

import MenuTree from '@/modules/cms/components/MenuTree.vue'
import { useCartStore } from '@/modules/orders/stores/cart'
import { useAuthStore } from '@/stores/auth'
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()
const { theme, headerMenu, footer, logo } = storeToRefs(themeStore)

const auth = useAuthStore()
const cart = useCartStore()

onMounted(() => {
  if (auth.isAuthenticated) {
    void cart.load()
  }
})
</script>

<template>
  <div class="layout layout--default">
    <header class="layout__header">
      <RouterLink to="/" class="layout__brand">
        <img v-if="logo" :src="logo" :alt="theme?.name ?? 'Cronos Bakery'" class="layout__logo" />
        <span v-else class="layout__brand-text">{{ theme?.name ?? 'Cronos Bakery' }}</span>
      </RouterLink>

      <nav class="layout__nav">
        <MenuTree v-if="headerMenu" :items="headerMenu.items" />
        <template v-else>
          <RouterLink to="/catalog">Catalog</RouterLink>
          <RouterLink to="/builder">Build a Cake</RouterLink>
        </template>
        <RouterLink to="/carrito" class="layout__cart">
          Cart<span v-if="cart.itemCount" class="layout__cart-badge">{{ cart.itemCount }}</span>
        </RouterLink>
      </nav>
    </header>

    <main class="layout__content">
      <slot />
    </main>

    <footer class="layout__footer">
      <div class="layout__footer-inner">
        <RouterLink to="/" class="layout__footer-brand">
          <img v-if="logo" :src="logo" :alt="theme?.name ?? 'Cronos Bakery'" class="layout__footer-logo" />
          <span v-else class="layout__footer-brand-text">{{ theme?.name ?? 'Cronos Bakery' }}</span>
        </RouterLink>

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

        <div class="layout__footer-bottom">
          <small>{{ footer?.copyright ?? `© ${new Date().getFullYear()} Cronos Bakery Builder` }}</small>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.layout__brand-text {
  font-family: var(--font-script);
  font-size: 2rem;
  color: var(--color-primary);
}

.layout__footer-inner {
  max-width: 1200px;
  margin: 0 auto;
}

.layout__footer-brand {
  display: inline-block;
  margin-bottom: 1.5rem;
}

.layout__footer-brand-text {
  font-family: var(--font-script);
  font-size: 2.5rem;
  color: var(--color-primary);
}

.layout__footer-logo {
  height: 60px;
  width: auto;
}

.layout__footer-bottom {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border-light);
}
</style>
