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

const LOCAL_APP_HOST_PATTERN = /^https?:\/\/(?:localhost|127\.0\.0\.1|[^/]+\.test)(?::\d+)?(?:\/|$)/i

export const resolvePublicUrl = (value) => {
  const raw = String(value || '').trim()
  if (!raw) return ''

  if (/^(data:|blob:)/i.test(raw)) {
    return raw
  }

  if (/^https?:\/\//i.test(raw)) {
    if (typeof window !== 'undefined' && window.location.protocol === 'https:' && raw.startsWith('http://')) {
      return raw.replace(/^http:\/\//i, 'https://')
    }

    if (!LOCAL_APP_HOST_PATTERN.test(raw)) {
      return raw
    }

    const [, pathname = '/'] = raw.match(/^https?:\/\/[^/]+(\/.*)?$/i) || []
    return getApiUrl(pathname)
  }

  return getApiUrl(raw)
}
