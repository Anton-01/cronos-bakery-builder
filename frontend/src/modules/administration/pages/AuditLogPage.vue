<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminPanelService, type AuditLog } from '../services/adminPanelService'

const logs = ref<AuditLog[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    logs.value = (await adminPanelService.auditLogs()).data
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="admin-page">
    <h1>Auditoría</h1>
    <p>Toda acción administrativa queda registrada.</p>

    <p v-if="loading">Cargando…</p>
    <table v-else class="admin-table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Admin</th>
          <th>Método</th>
          <th>Ruta</th>
          <th>Estado</th>
          <th>IP</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="log in logs" :key="log.id">
          <td>{{ new Date(log.created_at).toLocaleString('es-CR') }}</td>
          <td>{{ log.admin_name }}</td>
          <td><code>{{ log.method }}</code></td>
          <td>{{ log.path }}</td>
          <td>{{ log.status_code }}</td>
          <td>{{ log.ip_address }}</td>
        </tr>
        <tr v-if="logs.length === 0">
          <td colspan="6">Sin registros aún.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
