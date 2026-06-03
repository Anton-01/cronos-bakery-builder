<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--banner" :style="config.color ? `background:${config.color}` : ''">
    <p>{{ config.text }}</p>
    <a v-if="config.cta_url" class="block__cta" :href="config.cta_url">
      {{ config.cta_label ?? 'Go' }}
    </a>
  </section>
</template>
