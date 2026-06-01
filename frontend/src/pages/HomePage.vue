<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="home">
    <h1>Artisanal cakes, built your way.</h1>
    <p>
      Design a custom cake, browse our catalog and track your order from the oven
      to your door.
    </p>
    <RouterLink class="home__cta" to="/builder">Start building</RouterLink>
  </section>
</template>
