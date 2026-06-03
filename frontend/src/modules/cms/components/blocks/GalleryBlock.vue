<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--gallery">
    <h2 v-if="config.title">{{ config.title }}</h2>
    <div class="block--gallery__grid">
      <figure v-for="(image, index) in config.images ?? []" :key="index">
        <img :src="image.url ?? image" :alt="image.alt ?? ''" />
        <figcaption v-if="image.caption">{{ image.caption }}</figcaption>
      </figure>
    </div>
  </section>
</template>
