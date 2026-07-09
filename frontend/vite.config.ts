import { fileURLToPath, URL } from 'node:url'
import { defineConfig, type Plugin } from 'vite'
import vue from '@vitejs/plugin-vue'

/**
 * MPA de dos entry points físicamente separados:
 *   - index.html  → src/entries/storefront.ts (sitio público, sin PrimeVue)
 *   - admin.html  → src/entries/admin.ts (panel admin: PrimeVue + admin.css)
 *
 * Este middleware replica en dev/preview lo que el servidor de producción
 * debe hacer: toda URL bajo /admin se sirve desde admin.html y el resto
 * desde index.html (history fallback por interfaz). Así, un F5 dentro del
 * panel jamás carga el CSS del storefront ni viceversa.
 */
function adminHtmlFallback(): Plugin {
  const rewrite = (url: string | undefined): string | null => {
    const path = (url ?? '').split('?')[0]
    if ((path === '/admin' || path.startsWith('/admin/')) && !path.includes('.')) {
      return '/admin.html'
    }
    return null
  }

  return {
    name: 'admin-html-fallback',
    configureServer(server) {
      server.middlewares.use((req, _res, next) => {
        const target = rewrite(req.url)
        if (target) req.url = target
        next()
      })
    },
    configurePreviewServer(server) {
      server.middlewares.use((req, _res, next) => {
        const target = rewrite(req.url)
        if (target) req.url = target
        next()
      })
    },
  }
}

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), adminHtmlFallback()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
  build: {
    rollupOptions: {
      input: {
        storefront: fileURLToPath(new URL('./index.html', import.meta.url)),
        admin: fileURLToPath(new URL('./admin.html', import.meta.url)),
      },
    },
  },
  optimizeDeps: {
    // PrimeVue's <Editor> resolves "quill" at runtime. Pre-bundling it here
    // prevents the "Failed to resolve import 'quill'" 500 from vite:import-analysis.
    include: ['quill'],
  },
})
