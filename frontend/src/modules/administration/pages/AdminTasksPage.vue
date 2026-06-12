<script setup lang="ts">
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Mis Tareas</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Mis Tareas
        </div>
      </div>
    </div>

    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Lista de pendientes</h3>
      </div>
      <div class="admin-content-card__body" style="padding: 2rem; text-align: center; color: var(--admin-text-muted);">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.4; margin-bottom: 1rem;">
          <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        <p style="font-size: 0.9rem;">Sección en construcción</p>
      </div>
    </div>
  </div>
</template>