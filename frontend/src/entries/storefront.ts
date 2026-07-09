import { createApp } from 'vue'
import { createPinia } from 'pinia'

import StorefrontApp from '@/StorefrontApp.vue'
import storefrontRouter from '@/router/storefront'

// Estilos EXCLUSIVOS del sitio público. Ni PrimeVue, ni primeicons, ni
// admin.css se cargan aquí: el panel admin vive en su propio entry point.
import '@/style.css'

const app = createApp(StorefrontApp)

app.use(createPinia())
app.use(storefrontRouter)

app.mount('#app')
