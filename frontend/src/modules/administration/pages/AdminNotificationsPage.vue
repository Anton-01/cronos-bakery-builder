<script setup lang="ts">
import { onMounted, ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Card from 'primevue/card'
import ProgressSpinner from 'primevue/progressspinner'

import { adminPanelService, type NotificationLog, type Paginated } from '../services/adminPanelService'

const logsResponse = ref<Paginated<NotificationLog> | null>(null)
const loading = ref(true)

function formatDate(dateStr: string): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateStr))
}

function channelSeverity(channel: string): 'info' | 'warn' | 'success' | 'secondary' {
  if (channel === 'email') return 'info'
  if (channel === 'sms') return 'warn'
  if (channel === 'push') return 'success'
  return 'secondary'
}

function statusSeverity(status: string): 'success' | 'warn' | 'danger' | 'secondary' {
  if (status === 'sent' || status === 'delivered') return 'success'
  if (status === 'pending') return 'warn'
  if (status === 'failed') return 'danger'
  return 'secondary'
}

onMounted(async () => {
  try {
    logsResponse.value = await adminPanelService.notificationLogs()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Notificaciones</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Comunicaciones <span>/</span> Notificaciones</div>
      </div>
    </div>

    <Card>
      <template #title>
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span>Registro de Notificaciones</span>
          <span v-if="logsResponse?.meta" style="font-size:0.875rem; font-weight:400; color:var(--admin-text-muted);">
            Página {{ logsResponse.meta.current_page }} — {{ logsResponse.meta.total }} registros
          </span>
        </div>
      </template>
      <template #content>
        <div v-if="loading" style="display:flex; justify-content:center; padding:3rem;">
          <ProgressSpinner />
        </div>

        <DataTable v-else-if="logsResponse" :value="logsResponse.data" class="p-datatable-sm">
          <template #empty>
            <div style="text-align:center; padding:2rem; color:var(--admin-text-muted);">No hay notificaciones registradas.</div>
          </template>

          <Column header="Canal" style="width:100px;">
            <template #body="{ data }">
              <Tag :value="data.channel" :severity="channelSeverity(data.channel)" />
            </template>
          </Column>
          <Column header="Destinatario" field="recipient" />
          <Column header="Asunto" field="subject" />
          <Column header="Estado" style="width:110px;">
            <template #body="{ data }">
              <Tag :value="data.status" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Fecha" style="width:180px;">
            <template #body="{ data }">{{ formatDate(data.sent_at) }}</template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>
