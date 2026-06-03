<script setup lang="ts">
import { onMounted, ref } from 'vue'

import { adminPanelService, type NotificationLog, type Paginated } from '../services/adminPanelService'

const logsResponse = ref<Paginated<NotificationLog> | null>(null)
const loading = ref(true)

function formatDate(dateStr: string): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateStr))
}

function channelBadgeClass(channel: string): Record<string, boolean> {
  return {
    'admin-badge--info': channel === 'email',
    'admin-badge--warning': channel === 'sms',
    'admin-badge--success': channel === 'push',
  }
}

function statusBadgeClass(status: string): Record<string, boolean> {
  return {
    'admin-badge--success': status === 'sent' || status === 'delivered',
    'admin-badge--warning': status === 'pending',
    'admin-badge--error': status === 'failed',
  }
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
    <!-- Page header -->
    <div class="admin-page-header">
      <div>
        <h1>Notificaciones</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Comunicaciones <span>/</span> Notificaciones
        </div>
      </div>
    </div>

    <div class="admin-content-card">
      <div class="admin-content-card__header">
        <h3 class="admin-content-card__title">Registro de Notificaciones</h3>
        <span
          v-if="logsResponse?.meta"
          style="font-size: 0.875rem; color: var(--admin-text-muted);"
        >
          Página {{ logsResponse.meta.current_page }} &mdash; {{ logsResponse.meta.total }} registros en total
        </span>
      </div>
      <div class="admin-content-card__body">
        <p
          v-if="loading"
          style="text-align: center; padding: 2rem; color: var(--admin-text-muted);"
        >
          Cargando notificaciones...
        </p>

        <template v-else-if="logsResponse">
          <p
            v-if="logsResponse.data.length === 0"
            style="text-align: center; padding: 2rem; color: var(--admin-text-muted);"
          >
            No hay notificaciones registradas.
          </p>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Canal</th>
                <th>Destinatario</th>
                <th>Asunto</th>
                <th>Estado</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logsResponse.data" :key="log.id">
                <td>
                  <span class="admin-badge" :class="channelBadgeClass(log.channel)">
                    {{ log.channel }}
                  </span>
                </td>
                <td>{{ log.recipient }}</td>
                <td>{{ log.subject }}</td>
                <td>
                  <span class="admin-badge" :class="statusBadgeClass(log.status)">
                    {{ log.status }}
                  </span>
                </td>
                <td>{{ formatDate(log.sent_at) }}</td>
              </tr>
            </tbody>
          </table>
        </template>
      </div>
    </div>
  </div>
</template>
