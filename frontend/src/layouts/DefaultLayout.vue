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
          <RouterLink to="/catalog">Catalogo</RouterLink>
          <RouterLink to="/builder">Arma tu Pastel</RouterLink>
        </template>
        <RouterLink to="/carrito" class="layout__cart-link" aria-label="Carrito de compras">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <span v-if="cart.itemCount" class="layout__cart-badge">{{ cart.itemCount }}</span>
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

.layout__cart-link {
  position: relative;
  display: flex;
  align-items: center;
  padding: 0.25rem;
  color: var(--color-text);
  transition: color 0.2s ease;
}

.layout__cart-link:hover {
  color: var(--color-primary);
}

.layout__cart-badge {
  position: absolute;
  top: -4px;
  right: -8px;
  background: var(--color-primary);
  color: #fff;
  font-size: 0.6rem;
  font-weight: 600;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  letter-spacing: 0;
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
