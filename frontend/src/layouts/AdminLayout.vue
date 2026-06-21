<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import type { MenuItem } from 'primevue/menuitem'

import { useAdminAuthStore } from '@/modules/administration/stores/adminAuth'

const adminAuth = useAdminAuthStore()
const router = useRouter()
const route = useRoute()
const sidebarOpen = ref(false)
const profileMenuRef = ref()

const adminInitial = computed(() => {
  const name = adminAuth.admin?.name ?? 'A'
  return name.charAt(0).toUpperCase()
})

const navSections = [
  {
    title: 'Principal',
    items: [
      { label: 'Dashboard', to: '/admin', icon: 'pi pi-home', exact: true },
      { label: 'Pedidos', to: '/admin/orders', icon: 'pi pi-shopping-bag' },
      { label: 'Calendario', to: '/admin/calendar', icon: 'pi pi-calendar' },
    ],
  },
  {
    title: 'Catalogo',
    items: [
      { label: 'Productos', to: '/admin/products', icon: 'pi pi-box' },
      { label: 'Opciones', to: '/admin/options', icon: 'pi pi-sliders-h' },
      { label: 'Categorias', to: '/admin/categories', icon: 'pi pi-list' },
    ],
  },
  {
    title: 'Contenido',
    items: [
      { label: 'CMS', to: '/admin/cms', icon: 'pi pi-file-edit' },
      { label: 'Menus', to: '/admin/menus', icon: 'pi pi-bars' },
      { label: 'Theme Builder', to: '/admin/theme', icon: 'pi pi-palette' },
    ],
  },
  {
    title: 'Finanzas',
    items: [
      { label: 'Pagos', to: '/admin/payments', icon: 'pi pi-credit-card' },
    ],
  },
  {
    title: 'Comunicaciones',
    items: [
      { label: 'Correos', to: '/admin/emails', icon: 'pi pi-envelope' },
      { label: 'Notificaciones', to: '/admin/notifications', icon: 'pi pi-bell' },
    ],
  },
  {
    title: 'Administracion',
    items: [
      { label: 'Usuarios', to: '/admin/users', icon: 'pi pi-users' },
      { label: 'Roles', to: '/admin/roles', icon: 'pi pi-shield' },
      { label: 'Auditoria', to: '/admin/audit', icon: 'pi pi-chart-line' },
      { label: 'Seguridad (2FA)', to: '/admin/security', icon: 'pi pi-lock' },
    ],
  },
]

function isActive(to: string, exact?: boolean): boolean {
  if (exact) return route.path === to
  return route.path.startsWith(to)
}

const profileMenuItems = computed<MenuItem[]>(() => [
  {
    label: 'User Profile',
    items: [
      {
        label: adminAuth.admin?.name ?? 'Admin',
        disabled: true,
      },
      {
        separator: true,
      },
      {
        label: 'Mi Perfil',
        icon: 'pi pi-user',
        command: () => { router.push('/admin/profile') },
      },
      {
        label: 'Mi Bandeja',
        icon: 'pi pi-envelope',
        command: () => { router.push('/admin/notifications') },
      },
      {
        label: 'Mis Tareas',
        icon: 'pi pi-file',
        command: () => { router.push('/admin/tasks') },
      },
      {
        separator: true,
      },
      {
        label: 'Cerrar Sesion',
        icon: 'pi pi-sign-out',
        command: () => logout(),
      },
    ],
  },
])

async function logout(): Promise<void> {
  await adminAuth.logout()
  await router.push({ name: 'admin.login' })
}

function toggleProfileMenu(event: Event) {
  profileMenuRef.value?.toggle(event)
}

onMounted(() => {
  if (adminAuth.isAuthenticated && !adminAuth.admin) {
    void adminAuth.fetchCurrentAdmin()
  }
})
</script>

<template>
  <div class="admin-layout">
    <!-- Mobile overlay -->
    <div v-if="sidebarOpen" class="admin-sidebar__overlay" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" :class="{ 'admin-sidebar--open': sidebarOpen }">
      <RouterLink to="/admin" class="admin-sidebar__brand" @click="sidebarOpen = false">
        <div class="admin-sidebar__brand-icon">
          <i class="pi pi-th-large" style="color:#fff; font-size:1rem;"></i>
        </div>
        <span class="admin-sidebar__brand-name">Cronos Admin</span>
      </RouterLink>

      <div class="admin-sidebar__nav">
        <div v-for="section in navSections" :key="section.title" class="admin-sidebar__section">
          <div class="admin-sidebar__section-title">{{ section.title }}</div>
          <RouterLink
            v-for="item in section.items"
            :key="item.to"
            :to="item.to"
            class="admin-sidebar__link"
            :class="{ 'admin-sidebar__link--active': isActive(item.to, item.exact) }"
            @click="sidebarOpen = false"
          >
            <i :class="[item.icon, 'admin-sidebar__link-icon']"></i>
            {{ item.label }}
          </RouterLink>
        </div>
      </div>

      <div v-if="adminAuth.admin" class="admin-sidebar__user">
        <Avatar :label="adminInitial" shape="circle" class="admin-sidebar__avatar-btn" />
        <div class="admin-sidebar__user-info">
          <div class="admin-sidebar__user-name">{{ adminAuth.admin.name }}</div>
          <div class="admin-sidebar__user-role">{{ adminAuth.admin.roles[0] ?? 'Admin' }}</div>
        </div>
        <Button
          icon="pi pi-sign-out"
          severity="secondary"
          text
          rounded
          title="Cerrar sesion"
          @click="logout"
          class="admin-sidebar__logout"
        />
      </div>
    </aside>

    <!-- Main content -->
    <div class="admin-main">
      <header class="admin-topbar">
        <div class="admin-topbar__left">
          <Button
            icon="pi pi-bars"
            severity="secondary"
            text
            class="admin-topbar__toggle"
            @click="sidebarOpen = !sidebarOpen"
          />
          <div class="admin-topbar__search">
            <i class="pi pi-search" style="color:var(--admin-text-muted); font-size:0.9rem;"></i>
            <input type="text" placeholder="Buscar..." />
          </div>
        </div>
        <div class="admin-topbar__right">
          <Button
            icon="pi pi-bell"
            severity="secondary"
            text
            rounded
            title="Notificaciones"
          />
          <RouterLink to="/" class="admin-topbar__shop-link" title="Ver tienda">
            <Button icon="pi pi-external-link" severity="secondary" text rounded />
          </RouterLink>

          <Button
            :label="adminInitial"
            rounded
            @click="toggleProfileMenu"
            class="admin-topbar__avatar-btn"
          />
          <Menu ref="profileMenuRef" :model="profileMenuItems" popup />
        </div>
      </header>

      <div class="admin-page-content">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-topbar__shop-link {
  text-decoration: none;
}

.admin-topbar__avatar-btn {
  width: 36px !important;
  height: 36px !important;
  min-width: 36px !important;
  padding: 0 !important;
  font-size: 0.85rem !important;
  font-weight: 700 !important;
  background: var(--admin-primary) !important;
  border-color: var(--admin-primary) !important;
}

.admin-sidebar__avatar-btn {
  flex-shrink: 0;
  background: var(--admin-primary-light) !important;
  color: var(--admin-primary) !important;
  font-weight: 700;
}

.admin-sidebar__logout {
  color: var(--admin-text-muted) !important;
  flex-shrink: 0;
}

.admin-sidebar__logout:hover {
  color: var(--admin-error) !important;
}

.admin-sidebar__link-icon {
  width: 20px;
  font-size: 1rem;
  flex-shrink: 0;
}
</style>
