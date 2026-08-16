import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// Server-side proxy target: where the *Vite dev server process* reaches the
// backend from. Inside Docker that's the "webserver" (Nginx) service on the
// compose network (see docker-compose.yml's frontend env); running `npm run
// dev` directly on the host, it's the published port on localhost. Either
// way, the *browser* only ever talks to this Vite server's own origin — see
// src/lib/api.ts for why that matters.
const proxyTarget = process.env.VITE_API_PROXY_TARGET || 'http://localhost:8000'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    proxy: {
      '/api': { target: proxyTarget, changeOrigin: true },
      '/sanctum': { target: proxyTarget, changeOrigin: true },
    },
  },
})
