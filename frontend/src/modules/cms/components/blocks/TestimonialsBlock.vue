<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--testimonials">
    <h2 v-if="config.title">{{ config.title }}</h2>
    <div class="block--testimonials__grid">
      <blockquote v-for="(item, index) in config.items ?? []" :key="index">
        <p>“{{ item.quote }}”</p>
        <footer v-if="item.author">— {{ item.author }}</footer>
      </blockquote>
    </div>
  </section>
</template>
