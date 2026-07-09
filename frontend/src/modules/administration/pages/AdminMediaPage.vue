<script setup lang="ts">
import Card from 'primevue/card'
import MediaLibrary from '@/components/MediaLibrary.vue'
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1 style="margin:0 0 0.25rem;">Biblioteca de Medios</h1>
        <p style="margin:0; color:var(--admin-text-secondary); font-size:0.85rem;">
          Repositorio central de archivos del sitio. Los formatos aceptados se
          gobiernan desde «Tipos de Archivo».
        </p>
      </div>
    </div>

    <Card>
      <template #content>
        <MediaLibrary />
      </template>
    </Card>
  </div>
</template>
