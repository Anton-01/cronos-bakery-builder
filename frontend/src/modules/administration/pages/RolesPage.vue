<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminPanelService, type RoleDefinition } from '../services/adminPanelService'

const roles = ref<RoleDefinition[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    roles.value = await adminPanelService.roles()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="admin-page">
    <h1>Roles y permisos</h1>
    <p v-if="loading">Cargando…</p>

    <div v-else class="admin-roles">
      <article v-for="role in roles" :key="role.name" class="admin-card">
        <h3>{{ role.name }}</h3>
        <ul>
          <li v-for="perm in role.permissions" :key="perm">{{ perm }}</li>
          <li v-if="role.permissions.length === 0"><em>Acceso total (Super Admin)</em></li>
        </ul>
      </article>
    </div>
  </section>
</template>
