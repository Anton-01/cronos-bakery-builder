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
const pinia = createPinia()

app.use(pinia)
app.use(storefrontRouter)

// El store de sesión se inicializa ANTES de montar: su estado (token desde
// localStorage) es síncrono, así el guard del router conoce el estado real
// al evaluar la PRIMERA navegación.
const auth = useAuthStore(pinia)

// Sesión determinista (§28): ante 401/419/caída de red con sesión activa,
// el interceptor de Axios dispara el cierre forzado LOCAL del cliente.
setSessionInvalidHandler('customer', () => {
  auth.forceLogout()
})

// Hidratación del perfil en segundo plano (no bloquea el primer render).
if (auth.isAuthenticated) {
  void auth.fetchCurrentUser().catch(() => {})
}

// ANTI-FOUC: montar SOLO cuando el router resolvió la navegación inicial
// (guards ejecutados + componente lazy cargado) — el primer paint ya sale
// con el layout correcto de la ruta final.
void storefrontRouter.isReady().then(() => {
  app.mount('#app')
})
