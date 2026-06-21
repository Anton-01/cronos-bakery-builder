<script setup lang="ts">
import Card from 'primevue/card'
import { useAdminAuthStore } from '../stores/adminAuth'
const adminAuth = useAdminAuthStore()
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Mi Perfil</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Mi Perfil</div>
      </div>
    </div>

    <Card style="max-width:560px;">
      <template #title>Configuración de cuenta</template>
      <template #content>
        <div style="padding:1rem; text-align:center; color:var(--admin-text-muted);">
          <i class="pi pi-user" style="font-size:3rem; opacity:0.4; display:block; margin-bottom:1rem;" />
          <p style="font-size:0.9rem;">Sección en construcción</p>
          <p v-if="adminAuth.admin" style="font-size:0.8rem; margin-top:0.5rem;">
            {{ adminAuth.admin.name }} — {{ adminAuth.admin.email }}
          </p>
        </div>
      </template>
    </Card>
  </div>
</template>
