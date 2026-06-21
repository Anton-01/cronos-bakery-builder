<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Card from 'primevue/card'
import ProgressSpinner from 'primevue/progressspinner'

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
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Roles y Permisos</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Roles</div>
      </div>
    </div>

    <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
      <ProgressSpinner />
    </div>

    <div v-else class="roles-grid">
      <Card v-for="role in roles" :key="role.name">
        <template #title>{{ role.name }}</template>
        <template #content>
          <ul v-if="role.permissions.length > 0" class="perms-list">
            <li v-for="perm in role.permissions" :key="perm">{{ perm }}</li>
          </ul>
          <em v-else style="color:var(--admin-text-muted); font-size:0.875rem;">Acceso total (Super Admin)</em>
        </template>
      </Card>
    </div>
  </div>
</template>

<style scoped>
.roles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}
.perms-list {
  padding-left: 1.25rem;
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.8;
  color: var(--admin-text-secondary);
}
</style>
