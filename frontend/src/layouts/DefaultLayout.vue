<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="layout layout--default">
    <header class="layout__header">
      <RouterLink to="/" class="layout__brand">Cronos Bakery</RouterLink>
      <nav class="layout__nav">
        <RouterLink to="/catalog">Catalog</RouterLink>
        <RouterLink to="/builder">Build a Cake</RouterLink>
        <RouterLink to="/orders">My Orders</RouterLink>
      </nav>
    </header>

    <main class="layout__content">
      <slot />
    </main>

    <footer class="layout__footer">
      <small>&copy; {{ new Date().getFullYear() }} Cronos Bakery Builder</small>
    </footer>
  </div>
</template>
