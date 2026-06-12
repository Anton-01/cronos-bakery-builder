<script setup lang="ts">
import { useAdminAuthStore } from '../stores/adminAuth'
const adminAuth = useAdminAuthStore()
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Mi Perfil</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Mi Perfil
        </div>
      </div>
    </div>

    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Configuración de cuenta</h3>
      </div>
      <div class="admin-content-card__body" style="padding: 2rem; text-align: center; color: var(--admin-text-muted);">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.4; margin-bottom: 1rem;">
          <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        <p style="font-size: 0.9rem;">Sección en construcción</p>
        <p v-if="adminAuth.admin" style="font-size: 0.8rem; margin-top: 0.5rem;">
          {{ adminAuth.admin.name }} — {{ adminAuth.admin.email }}
        </p>
      </div>
    </div>
  </div>
</template>