export const LOCALE_STORAGE_KEY = 'slamin.locale'
export const DEFAULT_LOCALE = 'it'

export function getClientLocale() {
  if (typeof window === 'undefined') {
    return DEFAULT_LOCALE
  }

  const stored = String(window.localStorage.getItem(LOCALE_STORAGE_KEY) || '')
    .trim()
    .toLowerCase()
    .slice(0, 10)

  return stored || DEFAULT_LOCALE
}

export function localeHeaders() {
  const locale = getClientLocale()
  return locale ? { 'X-Locale': locale } : {}
}
