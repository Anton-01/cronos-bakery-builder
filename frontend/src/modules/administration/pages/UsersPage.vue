<script setup lang="ts">
import { onMounted, watch, ref } from 'vue'

import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'

import UserTable from '../components/UserTable.vue'
import UserFormModal from '../components/UserFormModal.vue'
import SuspendUserModal from '../components/SuspendUserModal.vue'
import ImpersonationBanner from '../components/ImpersonationBanner.vue'

import { adminPanelService, type AdminUser } from '../services/adminPanelService'
import { useUserManagementStore } from '../stores/userManagement'
const store = useUserManagementStore()
const { confirm } = useConfirm()
const toast = useToast()
const showUserForm = ref(false)
const editingUser = ref<AdminUser | null>(null)
const showSuspendModal = ref(false)
const suspendingUser = ref<AdminUser | null>(null)
const impersonating = ref<{ name: string; token: string } | null>(null)
let debounceTimer: ReturnType<typeof setTimeout> | undefined

watch(() => store.search, () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    store.currentPage = 1
    store.fetchUsers()
  }, 300)
})

onMounted(() => {
  store.fetchUsers()
})
function openCreateForm() {
  editingUser.value = null
  showUserForm.value = true
}

function openEditForm(user: AdminUser) {
  editingUser.value = user
  showUserForm.value = true
}

async function handleSaveUser(data: Record<string, string>) {
  try {
    if (editingUser.value) {
      await store.updateUser(editingUser.value.id, data)
      toast.success('Usuario actualizado correctamente.')
    } else {
      await store.createUser(data as any)
      toast.success('Usuario creado correctamente.')
    }
    showUserForm.value = false
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Error al guardar el usuario.')
  }
}

function openSuspendModal(user: AdminUser) {
  suspendingUser.value = user
  showSuspendModal.value = true
}
async function handleSuspend(data: { reason: string; suspended_until?: string }) {
  if (!suspendingUser.value) return
  try {
    await store.suspendUser(suspendingUser.value.id, data.reason, data.suspended_until)
    toast.success('Usuario suspendido correctamente.')
    showSuspendModal.value = false
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Error al suspender el usuario.')
  }
}
async function handleReactivate(user: AdminUser) {
  const ok = await confirm({
    title: 'Reactivar usuario',
    message: `¿Reactivar la cuenta de ${user.name}? El usuario podrá volver a iniciar sesión.`,
    action: 'activate',
    confirmText: 'Reactivar',
  })
  if (!ok) return
  try {
    await store.reactivateUser(user.id)
    toast.success('Usuario reactivado correctamente.')
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Error al reactivar el usuario.')
  }
}
async function handleImpersonate(user: AdminUser) {

  console.log({ user })
  const ok = await confirm({
    title: 'Impersonar usuario',
    message: `Vas a iniciar sesión como ${user.name}. Esto es solo para soporte técnico.`,
    action: 'warning',
    confirmText: 'Impersonar',
  })
  if (!ok) return
  try {
    const result = await adminPanelService.impersonateUser(user.id)
    impersonating.value = { name: user.name, token: result.token }
    localStorage.setItem('impersonation_token', result.token)
    localStorage.setItem('impersonation_admin_token', localStorage.getItem('admin_token') ?? '')
    toast.success(`Ahora estás viendo como ${user.name}.`)
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Error al impersonar.')
  }
}
function stopImpersonation() {
  const adminToken = localStorage.getItem('impersonation_admin_token')
  localStorage.removeItem('impersonation_token')
  localStorage.removeItem('impersonation_admin_token')
  if (adminToken) localStorage.setItem('admin_token', adminToken)
  impersonating.value = null
  toast.success('Has vuelto a tu sesion de administrador.')
}
async function handleRevokeSessions(user: AdminUser) {
  const ok = await confirm({
    title: 'Cerrar sesiones',
    message: `¿Cerrar todas las sesiones activas de ${user.name}? El usuario deberá volver a iniciar sesión en todos sus dispositivos.`,
    action: 'warning',
    confirmText: 'Cerrar sesiones',
  })
  if (!ok) return
  try {
    await adminPanelService.revokeUserSessions(user.id)
    toast.success('Sesiones cerradas correctamente.')
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Error al cerrar sesiones.')
  }
}
async function handleSendPasswordReset(user: AdminUser) {
  const ok = await confirm({
    title: 'Resetear contraseña',
    message: `¿Enviar enlace de reseteo de contraseña a ${user.email}?`,
    action: 'info',
    confirmText: 'Enviar enlace',
  })
  if (!ok) return
  try {
    await adminPanelService.sendPasswordReset(user.id)
    toast.success('Enlace de reseteo enviado correctamente.')
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Error al enviar el enlace.')
  }
}

async function handleDelete(user: AdminUser) {

  console.log(user)
  const ok = await confirm({
    title: 'Eliminar usuario',
    message: `¿Eliminar permanentemente a ${user.name}? Esta acción no se puede deshacer. Se eliminarán todos sus datos, sesiones y tokens.`,
    action: 'delete',
    confirmText: 'Eliminar',
  })

  console.log({ok})
  if (!ok) return
  try {
    await store.deleteUser(user.id)
    toast.success('Usuario eliminado correctamente.')
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'Error al eliminar el usuario.')
  }
}
</script>

<template>
  <section class="admin-page">
    <!-- Page Header -->
    <div class="admin-page-header">
      <div>
        <h1>Usuarios</h1>
        <div class="admin-page-header__breadcrumb">
          Inicio <span>/</span> Usuarios
        </div>
      </div>
      <svg class="users-page__header-illustration" viewBox="0 0 180 140" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect x="50" y="30" width="80" height="60" rx="8" fill="#d4e2ff" />
        <rect x="55" y="35" width="70" height="50" rx="4" fill="#eef3ff" />
        <rect x="62" y="48" width="28" height="3" rx="1.5" fill="#7fadff" />
        <rect x="62" y="55" width="20" height="3" rx="1.5" fill="#b3d0ff" />
        <rect x="62" y="62" width="24" height="3" rx="1.5" fill="#b3d0ff" />
        <rect x="100" y="45" width="18" height="22" rx="4" fill="#d4e2ff" />
        <circle cx="109" cy="51" r="4" fill="#7fadff" />
        <rect x="103" y="58" width="12" height="6" rx="2" fill="#7fadff" />
        <circle cx="45" cy="90" r="14" fill="#d4e2ff" />
        <circle cx="45" cy="85" r="6" fill="#7fadff" />
        <path d="M33 99c0-6.627 5.373-9 12-9s12 2.373 12 9" fill="#7fadff" />
        <circle cx="140" cy="55" r="12" fill="#d4e2ff" />
        <circle cx="140" cy="51" r="5" fill="#7fadff" />
        <path d="M130 63c0-5.523 4.477-8 10-8s10 2.477 10 8" fill="#7fadff" />
        <circle cx="90" cy="110" r="10" fill="#d4e2ff" />
        <circle cx="90" cy="107" r="4" fill="#7fadff" />
        <path d="M82 117c0-4.418 3.582-6 8-6s8 1.582 8 6" fill="#7fadff" />
        <line x1="57" y1="92" x2="68" y2="82" stroke="#b3d0ff" stroke-width="1.5" stroke-dasharray="3 2" />
        <line x1="112" y1="80" x2="128" y2="63" stroke="#b3d0ff" stroke-width="1.5" stroke-dasharray="3 2" />
        <line x1="98" y1="95" x2="98" y2="107" stroke="#b3d0ff" stroke-width="1.5" stroke-dasharray="3 2" />
      </svg>
    </div>

    <!-- Toolbar -->
    <div class="users-page__toolbar">
      <div class="users-page__search-wrapper">
        <svg class="users-page__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
          <circle cx="11" cy="11" r="8" /><path d="M21 21l-4.35-4.35" />
        </svg>
        <input
            v-model="store.search"
            type="search"
            placeholder="Buscar usuario..."
            class="users-page__search"
        />
      </div>
      <div class="users-page__toolbar-actions">
        <select v-model="store.statusFilter" class="users-page__filter" @change="store.currentPage = 1; store.fetchUsers()">
          <option value="">Todos los estados</option>
          <option value="active">Activos</option>
          <option value="suspended">Suspendidos</option>
        </select>
        <select v-model="store.roleFilter" class="users-page__filter" @change="store.currentPage = 1; store.fetchUsers()">
          <option value="">Todos los roles</option>
          <option value="customer">Customer</option>
          <option value="staff">Staff</option>
          <option value="admin">Admin</option>
        </select>
        <button class="users-page__add-btn" @click="openCreateForm">
          Agregar Usuario
        </button>
      </div>
    </div>

    <!-- DataTable -->
    <UserTable
        :users="store.users"
        :loading="store.loading"
        :meta="store.meta"
        :per-page="store.perPage"
        @edit="openEditForm"
        @suspend="openSuspendModal"
        @reactivate="handleReactivate"
        @impersonate="handleImpersonate"
        @revoke-sessions="handleRevokeSessions"
        @send-password-reset="handleSendPasswordReset"
        @delete="handleDelete"
        @page-change="store.setPage"
        @per-page-change="store.setPerPage"
    />

    <!-- Modals -->
    <UserFormModal
        :open="showUserForm"
        :user="editingUser"
        @close="showUserForm = false"
        @save="handleSaveUser"
    />

    <SuspendUserModal
        :open="showSuspendModal"
        :user="suspendingUser"
        @close="showSuspendModal = false"
        @confirm="handleSuspend"
    />

    <!-- Impersonation Banner -->
    <ImpersonationBanner v-if="impersonating" :user-name="impersonating.name" @stop="stopImpersonation"/>
  </section>
</template>

<style scoped>

.users-page__header-illustration {
  width: 180px;
  height: 140px;
  flex-shrink: 0;
}
.users-page__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
  padding: 1rem 1.25rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}
.users-page__search-wrapper {
  position: relative;
  flex: 1;
  max-width: 340px;
}
.users-page__search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}
.users-page__search {
  width: 100%;
  padding: 0.5rem 0.75rem 0.5rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  font-family: inherit;
  background: #fff;
  color: #111827;
  transition: border-color 0.15s;
}
.users-page__search:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.users-page__toolbar-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.users-page__filter {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.8125rem;
  font-family: inherit;
  background: #fff;
  color: #374151;
  cursor: pointer;
}
.users-page__add-btn {
  padding: 0.5rem 1.25rem;
  background: #6366f1;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s;
  white-space: nowrap;
}
.users-page__add-btn:hover {
  background: #4f46e5;
}
</style>
