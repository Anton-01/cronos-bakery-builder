<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--faq">
    <h2 v-if="config.title">{{ config.title }}</h2>
    <details v-for="(item, index) in config.items ?? []" :key="index" class="block--faq__item">
      <summary>{{ item.question }}</summary>
      <p>{{ item.answer }}</p>
    </details>
  </section>
</template>
