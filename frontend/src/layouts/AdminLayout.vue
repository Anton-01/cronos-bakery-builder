<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watchEffect } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useAdminAuthStore } from '@/modules/administration/stores/adminAuth'

const adminAuth = useAdminAuthStore()
const router = useRouter()
const route = useRoute()
const sidebarOpen = ref(false)
const profileOpen = ref(false)
const profileDropdownRef = ref<HTMLElement | null>(null)
const expandedSections = ref<Set<string>>(new Set())

const adminInitial = computed(() => {
  const name = adminAuth.admin?.name ?? 'A'
  return name.charAt(0).toUpperCase()
})

function toggleProfile() {
  profileOpen.value = !profileOpen.value
}
function onClickOutside(e: MouseEvent) {
  if (profileDropdownRef.value && !profileDropdownRef.value.contains(e.target as Node)) {
    profileOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))


const navSections = [
  {
    title: 'Principal',
    items: [
      { label: 'Dashboard', to: '/admin', icon: 'dashboard', exact: true },
      { label: 'Pedidos', to: '/admin/orders', icon: 'orders' },
      { label: 'Calendario', to: '/admin/calendar', icon: 'calendar' },
    ],
  },
  {
    title: 'Catalogo',
    items: [
      { label: 'Productos', to: '/admin/products', icon: 'products' },
      { label: 'Opciones', to: '/admin/options', icon: 'options' },
      { label: 'Categorias', to: '/admin/categories', icon: 'categories' },
    ],
  },
  {
    title: 'Contenido',
    items: [
      {
        label: 'CMS', to: '/admin/cms', icon: 'cms',
        children: [
          { label: 'Páginas', to: '/admin/cms', icon: 'pages' },
          { label: 'Media Library', to: '/admin/cms/media', icon: 'media' },
          { label: 'Versiones', to: '/admin/cms/versions', icon: 'versions' },
          { label: 'Caché', to: '/admin/cms/cache', icon: 'cache' },
          { label: 'Almacenamiento', to: '/admin/cms/storage', icon: 'storage' },
        ],
      },
      { label: 'Menus', to: '/admin/menus', icon: 'menus' },
      { label: 'Theme Builder', to: '/admin/theme', icon: 'theme' },
    ],
  },
  {
    title: 'Finanzas',
    items: [
      { label: 'Pagos', to: '/admin/payments', icon: 'payments' },
    ],
  },
  {
    title: 'Comunicaciones',
    items: [
      { label: 'Correos', to: '/admin/emails', icon: 'emails' },
      { label: 'Notificaciones', to: '/admin/notifications', icon: 'notifications' },
    ],
  },
  {
    title: 'Administracion',
    items: [
      { label: 'Usuarios', to: '/admin/users', icon: 'users' },
      { label: 'Roles', to: '/admin/roles', icon: 'roles' },
      { label: 'Auditoria', to: '/admin/audit', icon: 'audit' },
      { label: 'Seguridad (2FA)', to: '/admin/security', icon: 'security' },
    ],
  },
]

interface NavChild {
  label: string
  to: string
  icon: string
}

interface NavItem {
  label: string
  to: string
  icon: string
  exact?: boolean
  children?: NavChild[]
}

function isActive(to: string, exact?: boolean): boolean {
  if (exact) return route.path === to
  return route.path.startsWith(to)
}

function hasChildren(item: NavItem): boolean {
  return !!item.children?.length
}

function isExpanded(label: string): boolean {
  return expandedSections.value.has(label)
}

function toggleExpand(label: string) {
  if (expandedSections.value.has(label)) {
    expandedSections.value.delete(label)
  } else {
    expandedSections.value.add(label)
  }
}

function isSectionActive(item: NavItem): boolean {
  if (!item.children) return false
  return item.children.some((c) => isActive(c.to, c.to === '/admin/cms'))
}

watchEffect(() => {
  for (const section of navSections) {
    for (const item of section.items) {
      if ((item as NavItem).children && isSectionActive(item as NavItem)) {
        expandedSections.value.add(item.label)
      }
    }
  }
})

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
  <div class="admin-layout">
    <!-- Mobile overlay -->
    <div v-if="sidebarOpen" class="admin-sidebar__overlay" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" :class="{ 'admin-sidebar--open': sidebarOpen }">
      <RouterLink to="/admin" class="admin-sidebar__brand" @click="sidebarOpen = false">
        <div class="admin-sidebar__brand-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        </div>
        <span class="admin-sidebar__brand-name">Cronos Admin</span>
      </RouterLink>

      <div class="admin-sidebar__nav">
        <div v-for="section in navSections" :key="section.title" class="admin-sidebar__section">
          <div class="admin-sidebar__section-title">{{ section.title }}</div>
          <template v-for="item in section.items" :key="item.to">
            <!-- Item with children (expandable) -->
            <template v-if="hasChildren(item as NavItem)">
              <button
                type="button"
                class="admin-sidebar__link admin-sidebar__link--parent"
                :class="{ 'admin-sidebar__link--active': isSectionActive(item as NavItem) }"
                @click="toggleExpand(item.label)"
              >
                <span class="admin-sidebar__link-icon">
                  <svg v-if="item.icon === 'cms'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
                </span>
                {{ item.label }}
                <svg
                  class="admin-sidebar__chevron"
                  :class="{ 'admin-sidebar__chevron--open': isExpanded(item.label) }"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"
                ><path d="M6 9l6 6 6-6"/></svg>
              </button>
              <Transition name="submenu-slide">
                <div v-if="isExpanded(item.label)" class="admin-sidebar__submenu">
                  <RouterLink
                    v-for="child in (item as NavItem).children"
                    :key="child.to"
                    :to="child.to"
                    class="admin-sidebar__sublink"
                    :class="{ 'admin-sidebar__sublink--active': isActive(child.to, child.to === '/admin/cms') }"
                    @click="sidebarOpen = false"
                  >
                    <span class="admin-sidebar__sublink-dot"></span>
                    {{ child.label }}
                  </RouterLink>
                </div>
              </Transition>
            </template>
            <!-- Regular item (no children) -->
            <RouterLink
              v-else
              :to="item.to"
              class="admin-sidebar__link"
              :class="{ 'admin-sidebar__link--active': isActive(item.to, (item as NavItem).exact) }"
              @click="sidebarOpen = false"
            >
            <span class="admin-sidebar__link-icon">
              <!-- Dashboard -->
              <svg v-if="item.icon === 'dashboard'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
              <!-- Orders -->
              <svg v-else-if="item.icon === 'orders'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
              <!-- Calendar -->
              <svg v-else-if="item.icon === 'calendar'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <!-- Products -->
              <svg v-else-if="item.icon === 'products'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
              <!-- Options -->
              <svg v-else-if="item.icon === 'options'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
              <!-- Categories -->
              <svg v-else-if="item.icon === 'categories'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
              <!-- CMS -->
              <svg v-else-if="item.icon === 'cms'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              <!-- Menus -->
              <svg v-else-if="item.icon === 'menus'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
              <!-- Theme -->
              <svg v-else-if="item.icon === 'theme'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="6.5" cy="13.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/><path d="M13.5 9v4.5L10 16"/></svg>
              <!-- Payments -->
              <svg v-else-if="item.icon === 'payments'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              <!-- Emails -->
              <svg v-else-if="item.icon === 'emails'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22 6 12 13 2 6"/></svg>
              <!-- Notifications -->
              <svg v-else-if="item.icon === 'notifications'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
              <!-- Users -->
              <svg v-else-if="item.icon === 'users'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
              <!-- Roles -->
              <svg v-else-if="item.icon === 'roles'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              <!-- Audit -->
              <svg v-else-if="item.icon === 'audit'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
              <!-- Security -->
              <svg v-else-if="item.icon === 'security'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
              <!-- Default -->
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>
            </span>
            {{ item.label }}
          </RouterLink>
          </template>
        </div>
      </div>

      <!-- User section at bottom -->
      <div class="admin-sidebar__user" v-if="adminAuth.admin">
        <div class="admin-sidebar__avatar">{{ adminInitial }}</div>
        <div class="admin-sidebar__user-info">
          <div class="admin-sidebar__user-name">{{ adminAuth.admin.name }}</div>
          <div class="admin-sidebar__user-role">{{ adminAuth.admin.roles[0] ?? 'Admin' }}</div>
        </div>
        <button type="button" class="admin-sidebar__logout" title="Cerrar sesion" @click="logout">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="admin-main">
      <header class="admin-topbar">
        <div class="admin-topbar__left">
          <button type="button" class="admin-topbar__toggle" @click="sidebarOpen = !sidebarOpen">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          </button>
          <div class="admin-topbar__search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" placeholder="Buscar..." />
          </div>
        </div>
        <div class="admin-topbar__right">
          <button type="button" class="admin-topbar__icon-btn" title="Notificaciones">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
          </button>
          <RouterLink to="/" class="admin-topbar__icon-btn" title="Ver tienda">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          </RouterLink>

          <!-- User profile dropdown -->
          <div ref="profileDropdownRef" class="admin-profile-dropdown">
            <button type="button" class="admin-profile-dropdown__trigger" @click.stop="toggleProfile">
              <div class="admin-profile-dropdown__avatar">{{ adminInitial }}</div>
            </button>
            <Transition name="dropdown-fade">
              <div v-if="profileOpen" class="admin-profile-dropdown__menu">
                <div class="admin-profile-dropdown__header">
                  <span class="admin-profile-dropdown__label">User Profile</span>
                </div>
                <div class="admin-profile-dropdown__user">
                  <div class="admin-profile-dropdown__user-avatar">{{ adminInitial }}</div>
                  <div class="admin-profile-dropdown__user-info">
                    <div class="admin-profile-dropdown__user-name">{{ adminAuth.admin?.name ?? 'Admin' }}</div>
                    <div class="admin-profile-dropdown__user-role">{{ adminAuth.admin?.roles?.[0] ?? 'Admin' }}</div>
                    <div class="admin-profile-dropdown__user-email">{{ adminAuth.admin?.email ?? '' }}</div>
                  </div>
                </div>
                <div class="admin-profile-dropdown__links">
                  <RouterLink to="/admin/profile" class="admin-profile-dropdown__link" @click="profileOpen = false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div>
                      <span class="admin-profile-dropdown__link-label">Mi Perfil</span>
                      <span class="admin-profile-dropdown__link-desc">Configuración de cuenta</span>
                    </div>
                  </RouterLink>
                  <RouterLink to="/admin/notifications" class="admin-profile-dropdown__link" @click="profileOpen = false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22 6 12 13 2 6"/></svg>
                    <div>
                      <span class="admin-profile-dropdown__link-label">Mi Bandeja</span>
                      <span class="admin-profile-dropdown__link-desc">Mensajes y correos</span>
                    </div>
                  </RouterLink>
                  <RouterLink to="/admin/tasks" class="admin-profile-dropdown__link" @click="profileOpen = false">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <div>
                      <span class="admin-profile-dropdown__link-label">Mis Tareas</span>
                      <span class="admin-profile-dropdown__link-desc">Lista de pendientes</span>
                    </div>
                  </RouterLink>
                </div>
                <div class="admin-profile-dropdown__footer">
                  <button type="button" class="admin-profile-dropdown__logout" @click="logout">
                    Logout
                  </button>
                </div>
              </div>
            </Transition>
          </div>

        </div>
      </header>

      <div class="admin-page-content">
        <slot />
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-sidebar__nav {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem 0;
}
/* Profile dropdown */
.admin-profile-dropdown {
  position: relative;
}
.admin-profile-dropdown__trigger {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 0;
  border-radius: 50%;
  transition: opacity 0.15s ease;
}
.admin-profile-dropdown__trigger:hover {
  opacity: 0.8;
}
.admin-profile-dropdown__avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--admin-primary, #5d87ff);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}
.admin-profile-dropdown__menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 320px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
  border: 1px solid var(--admin-border, #e5eaef);
  z-index: 1000;
  overflow: hidden;
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
}
.admin-profile-dropdown__header {
  padding: 0.75rem 1.25rem;
  border-bottom: 1px solid var(--admin-border, #e5eaef);
}
.admin-profile-dropdown__label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--admin-text, #2a3547);
}
.admin-profile-dropdown__user {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--admin-border, #e5eaef);
}
.admin-profile-dropdown__user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--admin-primary, #5d87ff);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.admin-profile-dropdown__user-info {
  min-width: 0;
}
.admin-profile-dropdown__user-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--admin-text, #2a3547);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.admin-profile-dropdown__user-role {
  font-size: 0.78rem;
  color: var(--admin-text-muted, #a1aab4);
  margin-top: 0.1rem;
}
.admin-profile-dropdown__user-email {
  font-size: 0.75rem;
  color: var(--admin-text-muted, #a1aab4);
  margin-top: 0.15rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.admin-profile-dropdown__links {
  padding: 0.5rem 0;
}
.admin-profile-dropdown__link {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.65rem 1.25rem;
  text-decoration: none;
  color: var(--admin-text, #2a3547);
  transition: background 0.12s ease;
}
.admin-profile-dropdown__link:hover {
  background: var(--admin-primary-light, #ecf2ff);
}
.admin-profile-dropdown__link svg {
  color: var(--admin-primary, #5d87ff);
  flex-shrink: 0;
}
.admin-profile-dropdown__link-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--admin-text, #2a3547);
}
.admin-profile-dropdown__link-desc {
  display: block;
  font-size: 0.72rem;
  color: var(--admin-text-muted, #a1aab4);
  margin-top: 0.1rem;
}
.admin-profile-dropdown__footer {
  padding: 0.75rem 1.25rem;
  border-top: 1px solid var(--admin-border, #e5eaef);
}
.admin-profile-dropdown__logout {
  width: 100%;
  padding: 0.55rem;
  border: 1px solid var(--admin-primary, #5d87ff);
  border-radius: 8px;
  background: transparent;
  color: var(--admin-primary, #5d87ff);
  font-family: var(--admin-font, 'Plus Jakarta Sans', sans-serif);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}
.admin-profile-dropdown__logout:hover {
  background: var(--admin-primary, #5d87ff);
  color: #fff;
}
/* Submenu parent button */
.admin-sidebar__link--parent {
  width: 100%;
  text-align: left;
  border: none;
  cursor: pointer;
  justify-content: flex-start;
}
.admin-sidebar__chevron {
  margin-left: auto;
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  transition: transform 0.2s ease;
  color: var(--admin-text-muted, #8c97a8);
}
.admin-sidebar__chevron--open {
  transform: rotate(180deg);
}
/* Submenu children */
.admin-sidebar__submenu {
  overflow: hidden;
}
.admin-sidebar__sublink {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.45rem 1rem 0.45rem 2.75rem;
  font-size: 0.8125rem;
  color: var(--admin-text-secondary, #5a6a85);
  text-decoration: none;
  border-radius: var(--admin-radius-sm, 8px);
  transition: all 0.15s ease;
}
.admin-sidebar__sublink:hover {
  color: var(--admin-primary, #5d87ff);
  background: var(--admin-primary-light, #ecf2ff);
}
.admin-sidebar__sublink--active {
  color: var(--admin-primary, #5d87ff);
  font-weight: 600;
}
.admin-sidebar__sublink-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--admin-text-muted, #8c97a8);
  flex-shrink: 0;
  transition: background 0.15s ease;
}
.admin-sidebar__sublink--active .admin-sidebar__sublink-dot,
.admin-sidebar__sublink:hover .admin-sidebar__sublink-dot {
  background: var(--admin-primary, #5d87ff);
}
/* Submenu slide transition */
.submenu-slide-enter-active {
  transition: all 0.25s ease;
  max-height: 300px;
}
.submenu-slide-leave-active {
  transition: all 0.2s ease;
  max-height: 300px;
}
.submenu-slide-enter-from,
.submenu-slide-leave-to {
  opacity: 0;
  max-height: 0;
}
/* Dropdown transition */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
