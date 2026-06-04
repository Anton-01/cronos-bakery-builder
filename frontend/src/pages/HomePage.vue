<script setup lang="ts">
import { RouterLink } from 'vue-router'

import BannerSlot from '@/modules/cms/components/BannerSlot.vue'
</script>

<template>
  <section class="home">
    <BannerSlot placement="home_top" />

    <div class="home__hero">
      <h1>Artisanal cakes, built your way.</h1>
      <p>
        Design a custom cake, browse our catalog and track your order from the oven
        to your door.
      </p>
      <RouterLink class="home__cta" to="/builder">Start Building</RouterLink>
    </div>

    <BannerSlot placement="home_middle" />
  </section>
</template>

<style scoped>
.home__hero {
  padding: 5rem 2rem;
  text-align: center;
}
</style>
