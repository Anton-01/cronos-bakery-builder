import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
  },
  optimizeDeps: {
    // PrimeVue's <Editor> resolves "quill" at runtime. Pre-bundling it here
    // prevents the "Failed to resolve import 'quill'" 500 from vite:import-analysis.
    include: ['quill'],
  },
})
