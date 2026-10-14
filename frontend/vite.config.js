import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

const DEFAULT_API_ORIGIN = 'http://slamin_v4.test'

const parseHost = (value) => {
  try {
    return new URL(value).host
  } catch {
    return 'slamin_v4.test'
  }
}

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const localEnv = loadEnv(mode, '.', '')
  const rootEnv = loadEnv(mode, '..', '')
  const apiProxyTarget =
    localEnv.VITE_API_PROXY_TARGET || rootEnv.APP_URL || DEFAULT_API_ORIGIN
  const apiProxyHost = localEnv.VITE_API_PROXY_HOST || parseHost(apiProxyTarget)

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
        '/broadcasting': {
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
