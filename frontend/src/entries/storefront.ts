import { createApp } from 'vue'
import { createPinia } from 'pinia'

import StorefrontApp from '@/StorefrontApp.vue'
import storefrontRouter from '@/router/storefront'
import { setSessionInvalidHandler } from '@/services/http'
import { useAuthStore } from '@/stores/auth'

// Estilos EXCLUSIVOS del sitio público. Ni PrimeVue, ni primeicons, ni
// admin.css se cargan aquí: el panel admin vive en su propio entry point.
import '@/style.css'

const app = createApp(StorefrontApp)

app.use(createPinia())
app.use(storefrontRouter)

// Sesión determinista: ante 401/419/caída de red con sesión activa, el
// interceptor de Axios dispara el cierre forzado LOCAL del cliente.
setSessionInvalidHandler('customer', () => {
  useAuthStore().forceLogout()
})

app.mount('#app')
