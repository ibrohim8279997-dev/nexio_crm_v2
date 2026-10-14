import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// Dev: Vite serves the SPA on :3000 and proxies /api to a backend.
//   Worker (default):  wrangler dev --port 8787
//   Laravel:           VITE_API_TARGET=http://127.0.0.1:8000 npm run dev   (see backend/README.md)
// Prod: `vite build` emits dist/, which the Worker serves through the ASSETS binding.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, '.', '')
  const apiTarget = env.VITE_API_TARGET || 'http://127.0.0.1:8787'

  return {
    plugins: [react(), tailwindcss()],
    build: {
      outDir: 'dist',
      sourcemap: false,
    },
    server: {
      host: true,
      port: 3000,
      strictPort: true,
      proxy: {
        '/api': {
          target: apiTarget,
          changeOrigin: false,
        },
      },
    },
  }
})
