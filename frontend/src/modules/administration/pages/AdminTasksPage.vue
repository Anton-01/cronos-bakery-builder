<script setup lang="ts">
import Card from 'primevue/card'
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Mis Tareas</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Mis Tareas</div>
      </div>
    </div>

    <Card style="max-width:560px;">
      <template #title>Lista de pendientes</template>
      <template #content>
        <div style="padding:1rem; text-align:center; color:var(--admin-text-muted);">
          <i class="pi pi-file" style="font-size:3rem; opacity:0.4; display:block; margin-bottom:1rem;" />
          <p style="font-size:0.9rem;">Sección en construcción</p>
        </div>
      </template>
    </Card>
  </div>
</template>
