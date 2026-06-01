<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--cards">
    <h2 v-if="config.title">{{ config.title }}</h2>
    <div class="block--cards__grid">
      <article v-for="(card, index) in config.items ?? []" :key="index" class="card">
        <img v-if="card.image" :src="card.image" :alt="card.title ?? ''" />
        <h3 v-if="card.title">{{ card.title }}</h3>
        <p v-if="card.text">{{ card.text }}</p>
      </article>
    </div>
  </section>
</template>
