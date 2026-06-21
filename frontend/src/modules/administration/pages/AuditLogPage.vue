<script setup lang="ts">
import { onMounted, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Card from 'primevue/card'
import ProgressSpinner from 'primevue/progressspinner'

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
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Auditoría</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Auditoría</div>
      </div>
    </div>

    <Card>
      <template #title>Registro de Actividad</template>
      <template #subtitle>Toda acción administrativa queda registrada.</template>
      <template #content>
        <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>

        <DataTable v-else :value="logs" class="p-datatable-sm">
          <template #empty>
            <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">Sin registros aún.</div>
          </template>

          <Column header="Fecha" style="width:180px;">
            <template #body="{ data }">{{ new Date(data.created_at).toLocaleString('es-CR') }}</template>
          </Column>
          <Column header="Admin" field="admin_name" style="width:160px." />
          <Column header="Método" style="width:90px;">
            <template #body="{ data }"><code style="font-size:0.8rem;">{{ data.method }}</code></template>
          </Column>
          <Column header="Ruta" field="path" />
          <Column header="Estado" field="status_code" style="width:80px." />
          <Column header="IP" field="ip_address" style="width:130px." />
        </DataTable>
      </template>
    </Card>
  </div>
</template>
