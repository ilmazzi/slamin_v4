export const LOCALE_STORAGE_KEY = 'slamin.locale'

export function getClientLocale() {
  if (typeof window === 'undefined') {
    return 'it'
  }

  const stored = String(window.localStorage.getItem(LOCALE_STORAGE_KEY) || '')
    .trim()
    .toLowerCase()
    .slice(0, 10)

  if (stored) {
    return stored
  }

  const documentLocale = String(document.documentElement.lang || '')
    .trim()
    .toLowerCase()
    .slice(0, 10)

  const browserLocale = String(navigator.language || '')
    .trim()
    .toLowerCase()
    .slice(0, 10)

  return documentLocale || browserLocale || 'it'
}

export function localeHeaders() {
  const locale = getClientLocale()
  return locale ? { 'X-Locale': locale } : {}
}
