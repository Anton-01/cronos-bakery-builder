<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'

import { adminPanelService, type AdminUser } from '../services/adminPanelService'

const users = ref<AdminUser[]>([])
const search = ref('')
const loading = ref(true)

async function load(): Promise<void> {
  loading.value = true
  try {
    users.value = (await adminPanelService.users(search.value)).data
  } finally {
    loading.value = false
  }
}

let timer: ReturnType<typeof setTimeout> | undefined
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(load, 250)
})

onMounted(load)
</script>

<template>
  <section class="admin-page">
    <h1>Usuarios</h1>
    <input v-model="search" type="search" placeholder="Buscar por nombre o email…" class="admin-search" />

    <p v-if="loading">Cargando…</p>
    <table v-else class="admin-table">
      <thead>
        <tr><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Rol</th></tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id">
          <td>{{ user.name }}</td>
          <td>{{ user.email }}</td>
          <td>{{ user.phone ?? '—' }}</td>
          <td>{{ user.roles.join(', ') }}</td>
        </tr>
        <tr v-if="users.length === 0"><td colspan="4">Sin resultados.</td></tr>
      </tbody>
    </table>
  </section>
</template>
