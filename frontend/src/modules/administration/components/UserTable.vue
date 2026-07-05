<script setup lang="ts">
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import type { AdminUser } from '../services/adminPanelService'

const props = defineProps<{
  users: AdminUser[]
  loading: boolean
  meta: { current_page: number; last_page: number; total: number } | null
  perPage: number
}>()
const emit = defineEmits<{
  edit: [user: AdminUser]
  suspend: [user: AdminUser]
  reactivate: [user: AdminUser]
  impersonate: [user: AdminUser]
  'revoke-sessions': [user: AdminUser]
  'send-password-reset': [user: AdminUser]
  delete: [user: AdminUser]
  'page-change': [page: number]
  'per-page-change': [value: number]
}>()

function getInitials(name: string): string {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}

function getAvatarColor(name: string): string {
  const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#14b8a6']
  let hash = 0
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return colors[Math.abs(hash) % colors.length]
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <DataTable
    :value="users"
    :loading="loading"
    :rows="perPage"
    :totalRecords="meta?.total"
    lazy
    paginator
    :rowsPerPageOptions="[5, 10, 15, 25, 50]"
    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
    currentPageReportTemplate="{first} - {last} de {totalRecords}"
    @page="(e) => { emit('page-change', e.page + 1); emit('per-page-change', e.rows) }"
    class="p-datatable-sm"
  >
    <template #empty>
      <div style="text-align:center; padding: 3rem; color: var(--admin-text-muted);">
        <i class="pi pi-users" style="font-size: 3rem; display:block; margin-bottom: 1rem; opacity:0.3;"></i>
        No se encontraron usuarios
      </div>
    </template>

    <Column field="id" header="#" style="width: 60px;">
      <template #body="{ index }">
        <span style="color: var(--admin-text-muted);">{{ (meta?.current_page ?? 1 - 1) * perPage + index + 1 }}</span>
      </template>
    </Column>

    <Column header="Nombre">
      <template #body="{ data }">
        <div style="display:flex; align-items:center; gap:0.75rem;">
          <Avatar
            v-if="data.avatar"
            :image="data.avatar"
            shape="circle"
            size="normal"
          />
          <Avatar
            v-else
            :label="getInitials(data.name)"
            shape="circle"
            size="normal"
            :style="{ backgroundColor: getAvatarColor(data.name), color: '#fff', fontWeight: '600' }"
          />
          <div>
            <div style="font-weight:600; font-size:0.875rem;">{{ data.name }}</div>
            <div style="font-size:0.75rem; color:var(--admin-text-muted); text-transform:capitalize;">
              {{ data.roles.join(', ') || 'Customer' }}
            </div>
          </div>
        </div>
      </template>
    </Column>

    <Column field="email" header="Email" />
    <Column header="Telefono">
      <template #body="{ data }">{{ data.phone ?? '—' }}</template>
    </Column>

    <Column header="Estado" style="width: 120px;">
      <template #body="{ data }">
        <Tag
          :value="data.is_suspended ? 'Suspendido' : 'Activo'"
          :severity="data.is_suspended ? 'danger' : 'success'"
        />
      </template>
    </Column>

    <Column header="Registro" style="width: 160px;">
      <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
    </Column>

    <Column header="Acciones" style="width: 220px;">
      <template #body="{ data }">
        <div style="display:flex; gap:0.15rem;">
          <Button
            v-tooltip.top="'Editar usuario'"
            icon="pi pi-pencil"
            size="small"
            severity="info"
            text
            rounded
            aria-label="Editar usuario"
            @click="emit('edit', data)"
          />
          <Button
            v-if="data.is_suspended"
            v-tooltip.top="'Reactivar cuenta'"
            icon="pi pi-check-circle"
            size="small"
            severity="success"
            text
            rounded
            aria-label="Reactivar cuenta"
            @click="emit('reactivate', data)"
          />
          <Button
            v-else
            v-tooltip.top="'Suspender cuenta'"
            icon="pi pi-ban"
            size="small"
            severity="warn"
            text
            rounded
            aria-label="Suspender cuenta"
            @click="emit('suspend', data)"
          />
          <Button
            v-tooltip.top="'Enviar reseteo de contraseña'"
            icon="pi pi-key"
            size="small"
            severity="warn"
            text
            rounded
            aria-label="Enviar reseteo de contraseña"
            @click="emit('send-password-reset', data)"
          />
          <Button
            v-tooltip.top="'Cerrar todas las sesiones'"
            icon="pi pi-sign-out"
            size="small"
            severity="warn"
            text
            rounded
            aria-label="Cerrar todas las sesiones"
            @click="emit('revoke-sessions', data)"
          />
          <Button
            v-tooltip.top="'Impersonar (soporte)'"
            icon="pi pi-eye"
            size="small"
            severity="secondary"
            text
            rounded
            aria-label="Impersonar usuario"
            @click="emit('impersonate', data)"
          />
          <Button
            v-tooltip.top="'Eliminar usuario'"
            icon="pi pi-trash"
            size="small"
            severity="danger"
            text
            rounded
            aria-label="Eliminar usuario"
            @click="emit('delete', data)"
          />
        </div>
      </template>
    </Column>
  </DataTable>
</template>
