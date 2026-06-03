<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--hero" :style="config.image ? `background-image:url(${config.image})` : ''">
    <div class="block--hero__inner">
      <h1 v-if="config.heading">{{ config.heading }}</h1>
      <p v-if="config.subheading">{{ config.subheading }}</p>
      <a v-if="config.cta_url" class="block__cta" :href="config.cta_url">
        {{ config.cta_label ?? 'Learn more' }}
      </a>
    </div>
  </section>
</template>
