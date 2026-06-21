<script setup lang="ts">
import Button from 'primevue/button'

defineProps<{
  userName: string
}>()
const emit = defineEmits<{
  stop: []
}>()
</script>

<template>
  <div class="impersonation-banner">
    <div class="impersonation-banner__content">
      <i class="pi pi-eye"></i>
      <span>Estás viendo como <strong>{{ userName }}</strong></span>
    </div>
    <Button label="Volver al Admin" severity="warning" size="small" @click="emit('stop')" />
  </div>
</template>

<style scoped>
.impersonation-banner {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  background: #f59e0b;
  color: #78350f;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 0.625rem 1.5rem;
  font-size: 0.875rem;
  font-weight: 500;
}
.impersonation-banner__content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
