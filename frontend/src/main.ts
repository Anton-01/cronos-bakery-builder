import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import { definePreset } from '@primevue/themes'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import 'primeicons/primeicons.css'

import App from './App.vue'
import router from './router'
import './style.css'
import './styles/admin.css'

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

const app = createApp(App)

app.use(createPinia())
app.use(router)
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

app.mount('#app')
