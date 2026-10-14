import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

const frontendDir = path.dirname(fileURLToPath(import.meta.url))
const laravelDir = path.resolve(frontendDir, '..')
const DEFAULT_API_ORIGIN = 'http://slamin_v4.test'

const parseHost = (value) => {
  try {
    return new URL(value).host
  } catch {
    return 'slamin_v4.test'
  }
}

const envValue = (...values) => {
  for (const value of values) {
    const normalized = String(value ?? '').trim()
    if (normalized && !normalized.includes('${')) {
      return normalized
    }
  }
  return ''
}

const applyReverbClientEnv = (localEnv, rootEnv) => {
  const resolved = {
    VITE_REVERB_APP_KEY: envValue(
      localEnv.VITE_REVERB_APP_KEY,
      rootEnv.VITE_REVERB_APP_KEY,
      rootEnv.REVERB_APP_KEY,
    ),
    VITE_REVERB_HOST: envValue(
      localEnv.VITE_REVERB_HOST,
      rootEnv.VITE_REVERB_HOST,
      rootEnv.REVERB_HOST,
    ),
    VITE_REVERB_PORT: envValue(
      localEnv.VITE_REVERB_PORT,
      rootEnv.VITE_REVERB_PORT,
      rootEnv.REVERB_PORT,
    ),
    VITE_REVERB_SCHEME: envValue(
      localEnv.VITE_REVERB_SCHEME,
      rootEnv.VITE_REVERB_SCHEME,
      rootEnv.REVERB_SCHEME,
    ),
  }

  for (const [key, value] of Object.entries(resolved)) {
    if (value) {
      process.env[key] = value
    }
  }

  return resolved
}

export default defineConfig(({ mode }) => {
  const localEnv = loadEnv(mode, frontendDir, '')
  const rootEnv = loadEnv(mode, laravelDir, '')
  applyReverbClientEnv(localEnv, rootEnv)
  const apiProxyTarget =
    localEnv.VITE_API_PROXY_TARGET || rootEnv.APP_URL || DEFAULT_API_ORIGIN
  const apiProxyHost = localEnv.VITE_API_PROXY_HOST || parseHost(apiProxyTarget)

  return {
    envDir: laravelDir,
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
        '/share': {
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
