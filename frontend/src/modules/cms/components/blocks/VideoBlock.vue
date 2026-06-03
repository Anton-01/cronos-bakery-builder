<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--video">
    <h2 v-if="config.title">{{ config.title }}</h2>
    <div class="block--video__frame">
      <iframe
        v-if="config.url"
        :src="config.url"
        title="video"
        frameborder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        allowfullscreen
      ></iframe>
    </div>
  </section>
</template>
