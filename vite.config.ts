import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

// Dev: Vite serves the SPA on :3000 and proxies /api to a backend.
//   Worker (default):  wrangler dev --port 8787
//   Laravel:           VITE_API_TARGET=http://127.0.0.1:8000 npm run dev   (see backend/README.md)
// Prod: `vite build` emits dist/, which the Worker serves through the ASSETS binding.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, '.', '')
  const apiTarget = env.VITE_API_TARGET || 'http://127.0.0.1:8000'

  return {
    plugins: [react(), tailwindcss()],
    // .tsx/.ts are the sources: the tracked .js twins are stale compiler output and must not shadow them.
    resolve: {
      extensions: ['.tsx', '.ts', '.jsx', '.js', '.mjs', '.json'],
    },
    // Unit tests live next to the code (.ts/.tsx only: the tracked .js twins are stale output). Component tests opt
    // into jsdom with a `// @vitest-environment jsdom` docblock; e2e/ belongs to Playwright.
    test: {
      include: ['src/**/*.test.{ts,tsx}'],
      exclude: ['e2e/**', 'node_modules/**'],
      setupFiles: ['./src/test/setup.ts'],
    },
    build: {
      outDir: 'dist',
      sourcemap: false,
    },
    server: {
      host: true,
      port: 3000,
      strictPort: true,
      watch: {
        // Tooling state files change constantly and would trigger endless page reloads.
        ignored: ['**/.claude-flow/**', '**/.swarm/**', '**/.claude/**', '**/backend/**', '**/.wrangler/**', '**/docs/**', '**/*.log'],
      },
      allowedHosts: ['doorway-ensnare-bobcat.ngrok-free.dev'],
      proxy: {
        '/api': {
          target: apiTarget,
          changeOrigin: false,
        },
      },
    },
  }
})
