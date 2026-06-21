<script setup lang="ts">
import { computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Avatar from 'primevue/avatar'
import Menu from 'primevue/menu'
import { ref } from 'vue'
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

const activeMenuUser = ref<AdminUser | null>(null)
const menuRef = ref()

function openMenu(event: Event, user: AdminUser) {
  activeMenuUser.value = user
  menuRef.value?.toggle(event)
}

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

const menuItems = computed(() => {
  if (!activeMenuUser.value) return []
  const user = activeMenuUser.value
  return [
    ...(user.is_suspended
      ? [{ label: 'Reactivar', icon: 'pi pi-check-circle', command: () => emit('reactivate', user) }]
      : [{ label: 'Suspender', icon: 'pi pi-ban', command: () => emit('suspend', user) }]
    ),
    { label: 'Impersonar', icon: 'pi pi-eye', command: () => emit('impersonate', user) },
    { label: 'Cerrar Sesiones', icon: 'pi pi-lock', command: () => emit('revoke-sessions', user) },
    { label: 'Resetear Contraseña', icon: 'pi pi-key', command: () => emit('send-password-reset', user) },
  ]
})
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

    <Column header="Acciones" style="width: 130px;">
      <template #body="{ data }">
        <div style="display:flex; gap:0.25rem;">
          <Button icon="pi pi-pencil" size="small" severity="info" text rounded title="Editar" @click="emit('edit', data)" />
          <Button icon="pi pi-trash" size="small" severity="danger" text rounded title="Eliminar" @click="emit('delete', data)" />
          <Button icon="pi pi-ellipsis-v" size="small" severity="secondary" text rounded title="Más acciones" @click="openMenu($event, data)" />
        </div>
      </template>
    </Column>
  </DataTable>

  <Menu ref="menuRef" :model="menuItems" popup />
</template>
