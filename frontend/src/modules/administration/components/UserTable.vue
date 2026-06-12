<script setup lang="ts">
import { computed, ref } from 'vue'
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
  revokeSessions: [user: AdminUser]
  sendPasswordReset: [user: AdminUser]
  delete: [user: AdminUser]
  pageChange: [page: number]
  perPageChange: [value: number]
}>()

const openDropdown = ref<number | null>(null)

function toggleDropdown(id: number) {
  openDropdown.value = openDropdown.value === id ? null : id
}

function closeDropdowns() {
  openDropdown.value = null
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('es-ES', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
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

const startItem = computed(() => {
  if (!props.meta) return 0
  return (props.meta.current_page - 1) * props.perPage + 1
})

const endItem = computed(() => {
  if (!props.meta) return 0
  return Math.min(props.meta.current_page * props.perPage, props.meta.total)
})

function emitAndClose(event: string, user: AdminUser) {
  closeDropdowns()
  emit(event as any, user)
}
</script>

<template>
  <div class="user-table-wrapper" @click="closeDropdowns">
    <!-- Loading State -->
    <div v-if="loading" class="user-table-loading">
      <div class="user-table-loading__spinner"></div>
      <p>Cargando usuarios...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="users.length === 0" class="user-table-empty">
      <svg class="user-table-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128H5.228A2 2 0 013 17.208V5.792A2 2 0 015.228 3.872h13.544A2 2 0 0121 5.792v2.5M15 19.128a9.308 9.308 0 00-2.455-5.197" />
        <path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9z" />
      </svg>
      <h3>No se encontraron usuarios</h3>
      <p>Intenta ajustar los filtros de busqueda o agrega un nuevo usuario.</p>
    </div>

    <!-- Data Table -->
    <table v-else class="user-table">
      <thead>
        <tr>
          <th class="user-table__th--num">#</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Telefono</th>
          <th>Estado</th>
          <th>Fecha de Registro</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(user, index) in users" :key="user.id" class="user-table__row">
          <td class="user-table__td--num">{{ startItem + index }}</td>
          <td>
            <div class="user-table__name-cell">
              <div
                v-if="user.avatar"
                class="user-table__avatar"
                :style="{ backgroundImage: `url(${user.avatar})` }"
              ></div>
              <div
                v-else
                class="user-table__avatar user-table__avatar--initials"
                :style="{ backgroundColor: getAvatarColor(user.name) }"
              >
                {{ getInitials(user.name) }}
              </div>
              <div>
                <div class="user-table__name">{{ user.name }}</div>
                <div class="user-table__role-label">{{ user.roles.join(', ') || 'Customer' }}</div>
              </div>
            </div>
          </td>
          <td>{{ user.email }}</td>
          <td>{{ user.phone ?? '—' }}</td>
          <td>
            <span
              class="user-table__badge"
              :class="user.is_suspended ? 'user-table__badge--suspended' : 'user-table__badge--active'"
            >
              {{ user.is_suspended ? 'Suspendido' : 'Activo' }}
            </span>
          </td>
          <td>{{ formatDate(user.created_at) }}</td>
          <td>
            <div class="user-table__actions" @click.stop>
              <button class="user-table__action-btn" title="Editar" @click="emit('edit', user)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                  <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </button>
              <button class="user-table__action-btn user-table__action-btn--danger" title="Eliminar" @click="emitAndClose('delete', user)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                  <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" />
                </svg>
              </button>
              <div class="user-table__dropdown-wrapper">
                <button class="user-table__action-btn" title="Mas acciones" @click="toggleDropdown(user.id)">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                    <circle cx="12" cy="5" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="12" cy="19" r="1" />
                  </svg>
                </button>
                <div v-if="openDropdown === user.id" class="user-table__dropdown">
                  <button v-if="!user.is_suspended" @click="emitAndClose('suspend', user)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                    Suspender
                  </button>
                  <button v-else @click="emitAndClose('reactivate', user)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Reactivar
                  </button>
                  <button @click="emitAndClose('impersonate', user)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    Impersonar
                  </button>
                  <button @click="emitAndClose('revokeSessions', user)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    Cerrar Sesiones
                  </button>
                  <button @click="emitAndClose('sendPasswordReset', user)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                    Resetear Contrasena
                  </button>
                </div>
              </div>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Pagination -->
    <div v-if="meta && meta.total > 0" class="user-table__pagination">
      <div class="user-table__pagination-perpage">
        <span>Items por pagina:</span>
        <select :value="perPage" @change="emit('perPageChange', Number(($event.target as HTMLSelectElement).value))">
          <option :value="5">5</option>
          <option :value="10">10</option>
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </select>
      </div>
      <span class="user-table__pagination-info">
        {{ startItem }} &ndash; {{ endItem }} de {{ meta.total }}
      </span>
      <div class="user-table__pagination-nav">
        <button :disabled="meta.current_page <= 1" @click="emit('pageChange', 1)" title="Primera">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
        </button>
        <button :disabled="meta.current_page <= 1" @click="emit('pageChange', meta.current_page - 1)" title="Anterior">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M15 19l-7-7 7-7" /></svg>
        </button>
        <button :disabled="meta.current_page >= meta.last_page" @click="emit('pageChange', meta.current_page + 1)" title="Siguiente">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 5l7 7-7 7" /></svg>
        </button>
        <button :disabled="meta.current_page >= meta.last_page" @click="emit('pageChange', meta.last_page)" title="Ultima">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.user-table-wrapper {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.user-table-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #6b7280;
}

.user-table-loading__spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.user-table-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #6b7280;
  text-align: center;
}

.user-table-empty__icon {
  width: 64px;
  height: 64px;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.user-table-empty h3 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: #374151;
  font-family: inherit;
}

.user-table-empty p {
  margin: 0;
  font-size: 0.875rem;
}

.user-table {
  width: 100%;
  border-collapse: collapse;
}

.user-table thead {
  background: #f9fafb;
}

.user-table th {
  text-align: left;
  padding: 0.875rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.user-table__th--num {
  width: 50px;
  text-align: center;
}

.user-table td {
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  color: #374151;
  border-bottom: 1px solid #f3f4f6;
  vertical-align: middle;
}

.user-table__td--num {
  text-align: center;
  color: #9ca3af;
  font-weight: 500;
}

.user-table__row:hover {
  background: #f9fafb;
}

.user-table__row:last-child td {
  border-bottom: none;
}

.user-table__name-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-table__avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-size: cover;
  background-position: center;
  flex-shrink: 0;
}

.user-table__avatar--initials {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.user-table__name {
  font-weight: 600;
  color: #111827;
  line-height: 1.3;
}

.user-table__role-label {
  font-size: 0.75rem;
  color: #9ca3af;
  text-transform: capitalize;
}

.user-table__badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.4;
}

.user-table__badge--active {
  background: #ecfdf5;
  color: #059669;
}

.user-table__badge--suspended {
  background: #fef2f2;
  color: #dc2626;
}

.user-table__actions {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.user-table__action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: none;
  background: none;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s;
}

.user-table__action-btn:hover {
  background: #f3f4f6;
  color: #111827;
}

.user-table__action-btn--danger:hover {
  background: #fef2f2;
  color: #dc2626;
}

.user-table__dropdown-wrapper {
  position: relative;
}

.user-table__dropdown {
  position: absolute;
  right: 0;
  top: 100%;
  z-index: 50;
  min-width: 200px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
  padding: 0.375rem;
}

.user-table__dropdown button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: none;
  background: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.8125rem;
  color: #374151;
  text-align: left;
  transition: background 0.15s;
}

.user-table__dropdown button:hover {
  background: #f3f4f6;
}

/* Pagination */
.user-table__pagination {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 1.5rem;
  padding: 0.75rem 1rem;
  border-top: 1px solid #e5e7eb;
  font-size: 0.8125rem;
  color: #6b7280;
}

.user-table__pagination-perpage {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-table__pagination-perpage select {
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 0.25rem 0.5rem;
  font-size: 0.8125rem;
  background: #fff;
  color: #374151;
  cursor: pointer;
}

.user-table__pagination-nav {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.user-table__pagination-nav button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  background: none;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s;
}

.user-table__pagination-nav button:hover:not(:disabled) {
  background: #f3f4f6;
  color: #111827;
}

.user-table__pagination-nav button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}
</style>
