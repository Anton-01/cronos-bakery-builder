<script setup lang="ts">
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
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      </svg>
      <span>Estas viendo como <strong>{{ userName }}</strong></span>
    </div>
    <button @click="emit('stop')">Volver al Admin</button>
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

.impersonation-banner button {
  padding: 0.325rem 0.875rem;
  background: #78350f;
  color: #fef3c7;
  border: none;
  border-radius: 6px;
  font-size: 0.8125rem;
  font-weight: 500;
  cursor: pointer;
}

.impersonation-banner button:hover {
  background: #451a03;
}
</style>
