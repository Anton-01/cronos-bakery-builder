<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--cta">
    <h2 v-if="config.heading">{{ config.heading }}</h2>
    <p v-if="config.subheading">{{ config.subheading }}</p>
    <a v-if="config.cta_url" class="block__cta" :href="config.cta_url">
      {{ config.cta_label ?? 'Get started' }}
    </a>
  </section>
</template>
