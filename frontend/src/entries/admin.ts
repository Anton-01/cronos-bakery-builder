import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import { definePreset } from '@primevue/themes'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import Tooltip from 'primevue/tooltip'
import 'primeicons/primeicons.css'

import AdminApp from '@/AdminApp.vue'
import adminRouter from '@/router/admin'
import { setSessionInvalidHandler } from '@/services/http'
import { useAdminAuthStore } from '@/modules/administration/stores/adminAuth'

// Estilos EXCLUSIVOS del panel admin. El CSS global del storefront
// (style.css) jamás se importa aquí: cada interfaz tiene su entry point.
import '@/styles/admin.css'

const AdminPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#ecf2ff',
      100: '#d5e3ff',
      200: '#b3ccff',
      300: '#84aaff',
      400: '#5d87ff',
      500: '#4a74e8',
      600: '#3a5ecb',
      700: '#2f4fa8',
      800: '#274387',
      900: '#1e3570',
      950: '#121f45',
    },
  },
})

const app = createApp(AdminApp)
const pinia = createPinia()

app.use(pinia)
app.use(adminRouter)
app.use(PrimeVue, {
  theme: {
    preset: AdminPreset,
    options: {
      darkModeSelector: false,
    },
  },
})
app.use(ConfirmationService)
app.use(ToastService)

// El store de sesión se inicializa ANTES de montar: su estado (token desde
// localStorage) es síncrono, así el guard del router conoce el estado real
// al evaluar la PRIMERA navegación — nunca se pinta UI protegida "a ciegas".
const adminAuth = useAdminAuthStore(pinia)

// Sesión determinista (§28): ante 401/419/caída de red con sesión activa,
// el interceptor de Axios dispara el cierre forzado LOCAL del panel admin.
setSessionInvalidHandler('admin', () => {
  adminAuth.forceLogout()
})

// Hidratación del perfil en segundo plano (no bloquea el primer render;
// si el token ya no es válido, el propio interceptor forzará el logout).
if (adminAuth.isAuthenticated) {
  void adminAuth.fetchCurrentAdmin().catch(() => {})
}

// Registro global de la directiva Tooltip → habilita `v-tooltip` en todo el admin.
app.directive('tooltip', Tooltip)

// ANTI-FOUC: montar SOLO cuando el router resolvió la navegación inicial
// (guards ejecutados + componente lazy cargado). Sin esto, el shell se pinta
// con la ruta vacía y el layout por defecto — el flash del Dashboard.
void adminRouter.isReady().then(() => {
  app.mount('#app')
})
