<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import { useAdminAuthStore } from '@/modules/administration/stores/adminAuth'

const adminAuth = useAdminAuthStore()
const router = useRouter()

// The "Gestión" sections. Implemented panel views link to routes; the rest are
// managed through their admin APIs (built in earlier phases).
const sections = [
  { label: 'Dashboard', to: '/admin' },
  { label: 'Usuarios', to: '/admin/users' },
  { label: 'Roles', to: '/admin/roles' },
  { label: 'Auditoría', to: '/admin/audit' },
  { label: 'Seguridad (2FA)', to: '/admin/security' },
]

const apiManaged = [
  'CMS',
  'Menús',
  'Theme Builder',
  'Productos',
  'Opciones',
  'Pedidos',
  'Calendario',
  'Pagos',
  'Correos',
  'Automatizaciones',
]

async function logout(): Promise<void> {
  await adminAuth.logout()
  await router.push({ name: 'admin.login' })
}

onMounted(() => {
  if (adminAuth.isAuthenticated && !adminAuth.admin) {
    void adminAuth.fetchCurrentAdmin()
  }
})
</script>

<template>
  <div class="layout layout--admin">
    <aside class="layout__sidebar">
      <h2 class="layout__brand">Cronos Admin</h2>

      <nav class="layout__nav">
        <RouterLink v-for="section in sections" :key="section.to" :to="section.to">
          {{ section.label }}
        </RouterLink>
      </nav>

      <div class="admin-sidebar__api">
        <h4>Gestión vía API</h4>
        <ul>
          <li v-for="item in apiManaged" :key="item">{{ item }}</li>
        </ul>
      </div>
    </aside>

    <section class="layout__main">
      <header class="admin-topbar">
        <span v-if="adminAuth.admin">
          {{ adminAuth.admin.name }} · {{ adminAuth.admin.roles.join(', ') }}
        </span>
        <button type="button" class="admin-logout" @click="logout">Cerrar sesión</button>
      </header>

      <slot />
    </section>
  </div>
</template>
