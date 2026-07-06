<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import FileUpload, { type FileUploadUploaderEvent } from 'primevue/fileupload'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import ToggleSwitch from 'primevue/toggleswitch'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'

import { useToast } from '@/composables/useToast'
import { useConfirm } from '@/composables/useConfirm'
import { useAdminAuthStore } from '../stores/adminAuth'
import {
  adminPanelService,
  type AdminProfile,
  type AdminSession,
  type TwoFactorSetup,
} from '../services/adminPanelService'

const { success, error } = useToast()
const { confirm } = useConfirm()
const adminAuth = useAdminAuthStore()

// ============================ General ============================

const profileForm = reactive({ name: '', email: '', phone: '' })
const savingProfile = ref(false)
const uploadingAvatar = ref(false)

const admin = computed(() => adminAuth.admin)

function syncForm(): void {
  profileForm.name = adminAuth.admin?.name ?? ''
  profileForm.email = adminAuth.admin?.email ?? ''
  profileForm.phone = adminAuth.admin?.phone ?? ''
}

function applyProfile(updated: AdminProfile): void {
  if (adminAuth.admin) {
    adminAuth.admin = { ...adminAuth.admin, ...updated }
  }
  syncForm()
}

async function saveProfile(): Promise<void> {
  savingProfile.value = true
  try {
    const updated = await adminPanelService.updateAdminProfile({
      name: profileForm.name.trim(),
      email: profileForm.email.trim(),
      phone: profileForm.phone.trim() || null,
    })
    applyProfile(updated)
    success('Perfil actualizado')
  } catch (e: any) {
    error(e?.response?.data?.message ?? 'Error al actualizar el perfil')
  } finally {
    savingProfile.value = false
  }
}

async function onAvatarSelected(event: FileUploadUploaderEvent): Promise<void> {
  const file = Array.isArray(event.files) ? event.files[0] : event.files
  if (!file) return
  uploadingAvatar.value = true
  try {
    applyProfile(await adminPanelService.uploadAdminAvatar(file))
    success('Avatar actualizado')
  } catch (e: any) {
    error(e?.response?.data?.message ?? 'Error al subir el avatar (máx. 2 MB, JPG/PNG/WebP)')
  } finally {
    uploadingAvatar.value = false
  }
}

async function removeAvatar(): Promise<void> {
  const ok = await confirm({
    title: 'Eliminar avatar',
    message: '¿Eliminar tu foto de perfil?',
    action: 'delete',
    confirmText: 'Eliminar',
  })
  if (!ok) return
  try {
    applyProfile(await adminPanelService.deleteAdminAvatar())
    success('Avatar eliminado')
  } catch {
    error('Error al eliminar el avatar')
  }
}

function initials(name: string): string {
  return name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2)
}

// ============================ Seguridad ============================

const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' })
const changingPassword = ref(false)

async function changePassword(): Promise<void> {
  changingPassword.value = true
  try {
    const res = await adminPanelService.changeAdminPassword({ ...passwordForm })
    success(res.message)
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    loadSessions()
  } catch (e: any) {
    const errors = e?.response?.data?.errors
    error(errors?.current_password?.[0] ?? errors?.password?.[0] ?? 'Error al cambiar la contraseña')
  } finally {
    changingPassword.value = false
  }
}

// --- 2FA (TOTP) ---
const twoFactorSetup = ref<TwoFactorSetup | null>(null)
const twoFactorCode = ref('')
const twoFactorBusy = ref(false)

async function enableTwoFactor(): Promise<void> {
  twoFactorBusy.value = true
  try {
    twoFactorSetup.value = await adminPanelService.enableTwoFactor()
  } catch {
    error('Error al iniciar la activación de 2FA')
  } finally {
    twoFactorBusy.value = false
  }
}

async function confirmTwoFactor(): Promise<void> {
  twoFactorBusy.value = true
  try {
    await adminPanelService.confirmTwoFactor(twoFactorCode.value)
    twoFactorSetup.value = null
    twoFactorCode.value = ''
    if (adminAuth.admin) adminAuth.admin.two_factor_enabled = true
    success('Autenticación de dos factores activada')
  } catch {
    error('Código inválido. Intenta de nuevo.')
  } finally {
    twoFactorBusy.value = false
  }
}

async function disableTwoFactor(): Promise<void> {
  const ok = await confirm({
    title: 'Desactivar 2FA',
    message: 'Tu cuenta quedará protegida solo por contraseña. ¿Continuar?',
    action: 'delete',
    confirmText: 'Desactivar',
  })
  if (!ok) return
  try {
    await adminPanelService.disableTwoFactor()
    if (adminAuth.admin) adminAuth.admin.two_factor_enabled = false
    success('2FA desactivada')
  } catch {
    error('Error al desactivar 2FA')
  }
}

// ============================ Dispositivos ============================

const sessions = ref<AdminSession[]>([])
const loadingSessions = ref(false)
const revokingId = ref<number | null>(null)

async function loadSessions(): Promise<void> {
  loadingSessions.value = true
  try {
    sessions.value = await adminPanelService.adminSessions()
  } catch {
    error('Error al cargar las sesiones')
  } finally {
    loadingSessions.value = false
  }
}

async function revokeSession(session: AdminSession): Promise<void> {
  const ok = await confirm({
    title: 'Cerrar sesión del dispositivo',
    message: `Se cerrará la sesión de "${session.device_name}" (${session.ip_address ?? 'IP desconocida'}).`,
    action: 'warning',
    confirmText: 'Cerrar sesión',
  })
  if (!ok) return
  revokingId.value = session.id
  try {
    await adminPanelService.revokeAdminSession(session.id)
    sessions.value = sessions.value.filter((s) => s.id !== session.id)
    success('Sesión revocada')
  } catch {
    error('Error al revocar la sesión')
  } finally {
    revokingId.value = null
  }
}

async function revokeOthers(): Promise<void> {
  const ok = await confirm({
    title: 'Cerrar las demás sesiones',
    message: 'Se cerrarán todas las sesiones excepto la de este dispositivo.',
    action: 'warning',
    confirmText: 'Cerrar sesiones',
  })
  if (!ok) return
  try {
    await adminPanelService.revokeOtherAdminSessions()
    sessions.value = sessions.value.filter((s) => s.is_current)
    success('Se cerraron las demás sesiones')
  } catch {
    error('Error al cerrar las sesiones')
  }
}

function deviceIcon(session: AdminSession): string {
  const name = session.device_name ?? ''
  if (name.includes('iPhone') || name.includes('Android')) return 'pi pi-mobile'
  if (name.includes('iPad')) return 'pi pi-tablet'
  return 'pi pi-desktop'
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  return new Intl.DateTimeFormat('es-CR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(dateStr))
}

// ============================ Notificaciones ============================

const notificationChannels = [
  { key: 'order_updates', label: 'Actualizaciones de pedidos', desc: 'Nuevos pedidos y cambios de estado' },
  { key: 'production_alerts', label: 'Alertas de producción', desc: 'Retrasos y capacidad del calendario' },
  { key: 'security_alerts', label: 'Alertas de seguridad', desc: 'Inicios de sesión y cambios de credenciales' },
  { key: 'weekly_summary', label: 'Resumen semanal', desc: 'Métricas de la semana por correo' },
  { key: 'marketing', label: 'Novedades del producto', desc: 'Nuevas funciones del panel' },
]

const notificationSettings = reactive<Record<string, boolean>>({})
const savingNotifications = ref(false)

function syncNotifications(): void {
  const current = adminAuth.admin?.notification_settings ?? {}
  for (const channel of notificationChannels) {
    notificationSettings[channel.key] = current[channel.key] ?? true
  }
}

async function saveNotifications(): Promise<void> {
  savingNotifications.value = true
  try {
    applyProfile(await adminPanelService.updateAdminNotifications({ ...notificationSettings }))
    success('Preferencias de notificación guardadas')
  } catch {
    error('Error al guardar las preferencias')
  } finally {
    savingNotifications.value = false
  }
}

onMounted(async () => {
  if (!adminAuth.admin) await adminAuth.fetchCurrentAdmin()
  syncForm()
  syncNotifications()
  loadSessions()
})
</script>

<template>
  <div>
    <div class="admin-page-header">
      <div>
        <h1>Mi Cuenta</h1>
        <div class="admin-page-header__breadcrumb">Inicio <span>/</span> Mi Cuenta</div>
      </div>
    </div>

    <Tabs value="general">
      <TabList>
        <Tab value="general"><i class="pi pi-user" style="margin-right:0.4rem;" />General</Tab>
        <Tab value="security"><i class="pi pi-shield" style="margin-right:0.4rem;" />Seguridad</Tab>
        <Tab value="devices"><i class="pi pi-desktop" style="margin-right:0.4rem;" />Dispositivos</Tab>
        <Tab value="notifications"><i class="pi pi-bell" style="margin-right:0.4rem;" />Notificaciones</Tab>
      </TabList>
      <TabPanels>
        <!-- ==================== General ==================== -->
        <TabPanel value="general">
          <Card style="max-width:640px;">
            <template #content>
              <!-- Avatar -->
              <div class="profile-avatar-row">
                <Avatar
                  v-if="admin?.avatar"
                  :image="admin.avatar"
                  shape="circle"
                  size="xlarge"
                />
                <Avatar
                  v-else
                  :label="initials(admin?.name ?? '?')"
                  shape="circle"
                  size="xlarge"
                  style="background:var(--admin-primary); color:#fff; font-weight:600;"
                />
                <div class="profile-avatar-actions">
                  <FileUpload
                    mode="basic"
                    accept="image/jpeg,image/png,image/webp"
                    :maxFileSize="2 * 1024 * 1024"
                    customUpload
                    auto
                    chooseLabel="Cambiar foto"
                    chooseIcon="pi pi-camera"
                    :disabled="uploadingAvatar"
                    @uploader="onAvatarSelected"
                  />
                  <Button
                    v-if="admin?.avatar"
                    v-tooltip.top="'Eliminar avatar'"
                    icon="pi pi-trash"
                    size="small"
                    severity="danger"
                    text
                    rounded
                    aria-label="Eliminar avatar"
                    @click="removeAvatar"
                  />
                </div>
              </div>
              <small class="profile-hint">JPG, PNG o WebP · máximo 2 MB · se almacena en MinIO.</small>

              <hr class="profile-divider" />

              <div class="profile-field">
                <label for="pf-name">Nombre</label>
                <InputText id="pf-name" v-model="profileForm.name" fluid />
              </div>
              <div class="profile-field">
                <label for="pf-email">Correo electrónico</label>
                <InputText id="pf-email" v-model="profileForm.email" type="email" fluid />
              </div>
              <div class="profile-field">
                <label for="pf-phone">Teléfono</label>
                <InputText id="pf-phone" v-model="profileForm.phone" fluid placeholder="+506 8888-8888" />
              </div>

              <Button
                label="Guardar cambios"
                icon="pi pi-save"
                :loading="savingProfile"
                :disabled="!profileForm.name.trim() || !profileForm.email.trim()"
                @click="saveProfile"
              />
            </template>
          </Card>
        </TabPanel>

        <!-- ==================== Seguridad ==================== -->
        <TabPanel value="security">
          <div class="security-grid">
            <Card>
              <template #title>Cambiar contraseña</template>
              <template #content>
                <div class="profile-field">
                  <label>Contraseña actual</label>
                  <Password v-model="passwordForm.current_password" :feedback="false" toggleMask fluid />
                </div>
                <div class="profile-field">
                  <label>Nueva contraseña</label>
                  <Password v-model="passwordForm.password" toggleMask fluid promptLabel="Mínimo 10 caracteres, letras y números" />
                </div>
                <div class="profile-field">
                  <label>Confirmar nueva contraseña</label>
                  <Password v-model="passwordForm.password_confirmation" :feedback="false" toggleMask fluid />
                </div>
                <Message severity="warn" :closable="false" style="margin-bottom:1rem;">
                  Al cambiar la contraseña se cerrarán todas tus demás sesiones.
                </Message>
                <Button
                  label="Actualizar contraseña"
                  icon="pi pi-lock"
                  :loading="changingPassword"
                  :disabled="!passwordForm.current_password || !passwordForm.password || passwordForm.password !== passwordForm.password_confirmation"
                  @click="changePassword"
                />
              </template>
            </Card>

            <Card>
              <template #title>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                  Autenticación de dos factores
                  <Tag
                    :value="admin?.two_factor_enabled ? 'Activa' : 'Inactiva'"
                    :severity="admin?.two_factor_enabled ? 'success' : 'secondary'"
                  />
                </div>
              </template>
              <template #content>
                <p class="profile-hint" style="margin-bottom:1rem;">
                  Protege tu cuenta con códigos TOTP (Google Authenticator, 1Password, Authy).
                </p>

                <template v-if="!admin?.two_factor_enabled && !twoFactorSetup">
                  <Button label="Activar 2FA" icon="pi pi-shield" :loading="twoFactorBusy" @click="enableTwoFactor" />
                </template>

                <template v-if="twoFactorSetup">
                  <p style="font-size:0.85rem; margin:0 0 0.5rem;">
                    1. Agrega esta clave en tu aplicación de autenticación:
                  </p>
                  <code class="profile-2fa-secret">{{ twoFactorSetup.secret }}</code>
                  <p style="font-size:0.85rem; margin:0.75rem 0 0.5rem;">2. Ingresa el código de 6 dígitos para confirmar:</p>
                  <div style="display:flex; gap:0.5rem;">
                    <InputText v-model="twoFactorCode" placeholder="000000" maxlength="6" style="width:8rem;" />
                    <Button label="Confirmar" :loading="twoFactorBusy" :disabled="twoFactorCode.length !== 6" @click="confirmTwoFactor" />
                  </div>
                </template>

                <template v-if="admin?.two_factor_enabled">
                  <Button label="Desactivar 2FA" icon="pi pi-shield" severity="danger" outlined @click="disableTwoFactor" />
                </template>
              </template>
            </Card>
          </div>
        </TabPanel>

        <!-- ==================== Dispositivos ==================== -->
        <TabPanel value="devices">
          <Card>
            <template #title>
              <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                <span>Sesiones activas</span>
                <Button
                  v-if="sessions.length > 1"
                  label="Cerrar las demás sesiones"
                  icon="pi pi-sign-out"
                  size="small"
                  severity="danger"
                  outlined
                  @click="revokeOthers"
                />
              </div>
            </template>
            <template #content>
              <div v-if="loadingSessions" style="display:flex; justify-content:center; padding:2.5rem;">
                <ProgressSpinner />
              </div>
              <DataTable v-else :value="sessions" dataKey="id">
                <template #empty>
                  <p style="text-align:center; color:var(--admin-text-muted); margin:1.5rem 0;">Sin sesiones activas.</p>
                </template>
                <Column header="Dispositivo">
                  <template #body="{ data }">
                    <div style="display:flex; align-items:center; gap:0.6rem;">
                      <i :class="deviceIcon(data)" style="color:var(--admin-text-muted);" />
                      <span style="font-weight:600;">{{ data.device_name }}</span>
                      <Tag v-if="data.is_current" value="Este dispositivo" severity="success" style="font-size:0.6rem;" />
                      <Tag v-if="data.name === 'impersonation'" value="Impersonación" severity="warn" style="font-size:0.6rem;" />
                    </div>
                  </template>
                </Column>
                <Column header="Dirección IP">
                  <template #body="{ data }">
                    <code class="profile-ip">{{ data.ip_address ?? '—' }}</code>
                  </template>
                </Column>
                <Column header="Última conexión">
                  <template #body="{ data }">{{ formatDate(data.last_used_at ?? data.created_at) }}</template>
                </Column>
                <Column header="Acciones" style="width:6rem;">
                  <template #body="{ data }">
                    <Button
                      v-if="!data.is_current"
                      v-tooltip.top="'Cerrar sesión en este dispositivo'"
                      icon="pi pi-sign-out"
                      size="small"
                      severity="danger"
                      text
                      rounded
                      :loading="revokingId === data.id"
                      aria-label="Cerrar sesión en este dispositivo"
                      @click="revokeSession(data)"
                    />
                  </template>
                </Column>
              </DataTable>
            </template>
          </Card>
        </TabPanel>

        <!-- ==================== Notificaciones ==================== -->
        <TabPanel value="notifications">
          <Card style="max-width:640px;">
            <template #content>
              <div v-for="channel in notificationChannels" :key="channel.key" class="profile-notif-row">
                <div>
                  <div style="font-weight:600; font-size:0.875rem;">{{ channel.label }}</div>
                  <div style="font-size:0.75rem; color:var(--admin-text-muted);">{{ channel.desc }}</div>
                </div>
                <ToggleSwitch v-model="notificationSettings[channel.key]" :aria-label="channel.label" />
              </div>
              <Button
                label="Guardar preferencias"
                icon="pi pi-save"
                style="margin-top:1rem;"
                :loading="savingNotifications"
                @click="saveNotifications"
              />
            </template>
          </Card>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>

<style scoped>
.profile-avatar-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.4rem;
}
.profile-avatar-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.profile-hint {
  font-size: 0.75rem;
  color: var(--admin-text-muted);
}
.profile-divider {
  border: none;
  border-top: 1px solid var(--admin-border);
  margin: 1.25rem 0;
}
.profile-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 1rem;
}
.profile-field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--admin-text-secondary);
}
.security-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.5rem;
  align-items: start;
}
.profile-2fa-secret {
  display: inline-block;
  padding: 0.4rem 0.75rem;
  background: var(--admin-bg);
  border: 1px dashed var(--admin-border);
  border-radius: 6px;
  font-size: 0.85rem;
  letter-spacing: 0.08em;
  user-select: all;
}
.profile-ip {
  font-size: 0.8rem;
  background: var(--admin-bg);
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
}
.profile-notif-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--admin-border);
}
.profile-notif-row:last-of-type {
  border-bottom: none;
}
</style>
