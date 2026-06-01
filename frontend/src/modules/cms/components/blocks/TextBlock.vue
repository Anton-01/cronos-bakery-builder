<script setup lang="ts">
defineProps<{ config: Record<string, any> }>()
</script>

<template>
  <section class="block block--text">
    <h2 v-if="config.heading">{{ config.heading }}</h2>
    <!-- Body is admin-authored rich text stored as HTML. -->
    <div v-if="config.body" class="block--text__body" v-html="config.body"></div>
  </section>
</template>
