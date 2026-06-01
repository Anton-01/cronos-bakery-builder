<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="layout layout--admin">
    <aside class="layout__sidebar">
      <h2 class="layout__brand">Cronos Admin</h2>
      <nav class="layout__nav">
        <RouterLink to="/admin">Dashboard</RouterLink>
        <RouterLink to="/admin/cms">CMS</RouterLink>
        <RouterLink to="/admin/catalog">Catalog</RouterLink>
        <RouterLink to="/admin/orders">Orders</RouterLink>
        <RouterLink to="/admin/calendar">Calendar</RouterLink>
      </nav>
    </aside>

    <section class="layout__main">
      <slot />
    </section>
  </div>
</template>
