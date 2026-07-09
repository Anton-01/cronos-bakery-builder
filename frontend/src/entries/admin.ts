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

app.use(createPinia())
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

// Sesión determinista: ante 401/419/caída de red con sesión activa, el
// interceptor de Axios dispara el cierre forzado LOCAL del panel admin.
setSessionInvalidHandler('admin', () => {
  useAdminAuthStore().forceLogout()
})

// Registro global de la directiva Tooltip → habilita `v-tooltip` en todo el admin.
app.directive('tooltip', Tooltip)

app.mount('#app')
