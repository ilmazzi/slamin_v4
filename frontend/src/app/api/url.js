const normalizeApiBaseUrl = (value) => {
  const raw = String(value || '').trim()
  if (!raw) return ''
  if (raw.startsWith('/')) return raw.replace(/\/+$/, '')
  if (/^https?:\/\//i.test(raw)) return raw.replace(/\/+$/, '')
  return `https://${raw.replace(/^\/+/, '').replace(/\/+$/, '')}`
}

const API_BASE_URL = normalizeApiBaseUrl(import.meta.env.VITE_API_BASE_URL)

export const getApiUrl = (path) => {
  if (!path) return API_BASE_URL || ''
  if (/^https?:\/\//i.test(path)) return path
  if (!API_BASE_URL) return path
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  return `${API_BASE_URL}${normalizedPath}`
}

const LARAVEL_LOOPBACK_PORTS = new Set(['', '80', '443', '8000', '8080'])

export const resolvePublicUrl = (value) => {
  const raw = String(value || '').trim()
  if (!raw) return ''

  if (/^(data:|blob:)/i.test(raw)) {
    return raw
  }

  if (/^https?:\/\//i.test(raw)) {
    let parsed
    try {
      parsed = new URL(raw)
    } catch {
      return raw
    }

    const isLoopback = parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1'
    const isHerdHost = parsed.hostname.endsWith('.test')

    if (!isLoopback && !isHerdHost) {
      if (typeof window !== 'undefined' && window.location.protocol === 'https:' && raw.startsWith('http://')) {
        return raw.replace(/^http:\/\//i, 'https://')
      }
      return raw
    }

    if (isLoopback && !LARAVEL_LOOPBACK_PORTS.has(parsed.port)) {
      return raw
    }

    return getApiUrl(`${parsed.pathname}${parsed.search}${parsed.hash}`)
  }

  return getApiUrl(raw)
}
