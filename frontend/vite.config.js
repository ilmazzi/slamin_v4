import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, '.', '')
  const apiProxyTarget = env.VITE_API_PROXY_TARGET || 'https://slamin.test'
  const apiProxyHost = env.VITE_API_PROXY_HOST || 'slamin.test'

  return {
    plugins: [react()],
    server: {
      proxy: {
        '/api': {
          target: apiProxyTarget,
          changeOrigin: true,
          secure: false,
          headers: {
            Host: apiProxyHost,
          },
        },
        '/storage': {
          target: apiProxyTarget,
          changeOrigin: true,
          secure: false,
          headers: {
            Host: apiProxyHost,
          },
        },
      },
    },
  }
})
