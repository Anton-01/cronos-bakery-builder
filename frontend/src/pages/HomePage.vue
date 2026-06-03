<script setup lang="ts">
import { RouterLink } from 'vue-router'

import BannerSlot from '@/modules/cms/components/BannerSlot.vue'
</script>

<template>
  <section class="home">
    <!-- Administrable banners rendered from the active theme configuration. -->
    <BannerSlot placement="home_top" />

    <h1>Artisanal cakes, built your way.</h1>
    <p>
      Design a custom cake, browse our catalog and track your order from the oven
      to your door.
    </p>
    <RouterLink class="home__cta" to="/builder">Start building</RouterLink>

    <BannerSlot placement="home_middle" />
  </section>
</template>
