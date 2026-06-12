import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

import { adminPanelService, type AdminUser } from '../services/adminPanelService'

export const useUserManagementStore = defineStore('userManagement', () => {
  const users = ref<AdminUser[]>([])
  const meta = ref<{ current_page: number; last_page: number; total: number } | null>(null)
  const loading = ref(false)
  const search = ref('')
  const statusFilter = ref('')
  const roleFilter = ref('')
  const currentPage = ref(1)
  const perPage = ref(15)

  const isEmpty = computed(() => !loading.value && users.value.length === 0)

  async function fetchUsers() {
    loading.value = true
    try {
      const response = await adminPanelService.users({
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        role: roleFilter.value || undefined,
        page: currentPage.value,
        per_page: perPage.value,
      })
      users.value = response.data
      meta.value = response.meta ?? null
    } finally {
      loading.value = false
    }
  }

  function updateUserInList(updated: AdminUser) {
    const idx = users.value.findIndex(u => u.id === updated.id)
    if (idx !== -1) {
      users.value[idx] = updated
    }
  }

  function removeUserFromList(id: number) {
    users.value = users.value.filter(u => u.id !== id)
    if (meta.value) meta.value.total--
  }

  async function suspendUser(id: number, reason: string, until?: string) {
    const updated = await adminPanelService.suspendUser(id, { reason, suspended_until: until })
    updateUserInList(updated)
    return updated
  }

  async function reactivateUser(id: number) {
    const updated = await adminPanelService.reactivateUser(id)
    updateUserInList(updated)
    return updated
  }

  async function deleteUser(id: number) {
    await adminPanelService.deleteUser(id)
    removeUserFromList(id)
  }

  async function createUser(data: { first_name: string; last_name: string; email: string; phone?: string; password: string; role: string }) {
    const user = await adminPanelService.createUser(data)
    await fetchUsers()
    return user
  }

  async function updateUser(id: number, data: { first_name?: string; last_name?: string; email?: string; phone?: string; role?: string }) {
    const updated = await adminPanelService.updateUser(id, data)
    updateUserInList(updated)
    return updated
  }

  function setPage(page: number) {
    currentPage.value = page
    fetchUsers()
  }

  function setPerPage(value: number) {
    perPage.value = value
    currentPage.value = 1
    fetchUsers()
  }

  function resetFilters() {
    search.value = ''
    statusFilter.value = ''
    roleFilter.value = ''
    currentPage.value = 1
  }

  return {
    users,
    meta,
    loading,
    search,
    statusFilter,
    roleFilter,
    currentPage,
    perPage,
    isEmpty,
    fetchUsers,
    updateUserInList,
    removeUserFromList,
    suspendUser,
    reactivateUser,
    deleteUser,
    createUser,
    updateUser,
    setPage,
    setPerPage,
    resetFilters,
  }
})
