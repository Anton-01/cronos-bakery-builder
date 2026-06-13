<template>
  <div class="admin-layout admin-auth-layout">
    <slot />
  </div>
</template>

<style scoped>
.admin-auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--admin-bg, #f5f7fa);
  font-family: var(--admin-font, 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
  color: var(--admin-text, #2a3547);
}
</style>
