import { createContext, createElement, useContext, useEffect, useState } from 'react'
import { getApiUrl } from '../api/url'

const resolveKeyPath = (source, key) => {
  return key.split('.').reduce((acc, part) => acc?.[part], source)
}

const staticFallbacks = {
  it: {
    common: {
      loading: 'Caricamento...',
      no_results: 'Nessun risultato',
    },
  },
  en: {
    common: {
      loading: 'Loading...',
      no_results: 'No results',
    },
  },
}

const LOCALE_STORAGE_KEY = 'slamin.locale'
const I18nContext = createContext(null)

const getInitialLocale = () => {
  if (typeof window !== 'undefined') {
    const storedLocale = (window.localStorage.getItem(LOCALE_STORAGE_KEY) || '')
      .slice(0, 2)
      .toLowerCase()
    if (storedLocale) {
      return storedLocale
    }
  }

  const documentLocale = (document.documentElement.lang || '').slice(0, 2).toLowerCase()
  const browserLocale = (navigator.language || '').slice(0, 2).toLowerCase()
  return documentLocale || browserLocale || 'en'
}

function useI18nState() {
  const [locale, setLocale] = useState(getInitialLocale)
  const [translations, setTranslations] = useState({})
  const [fallbackLocale, setFallbackLocale] = useState('en')
  const [availableLocales, setAvailableLocales] = useState([])

  const getLocaleCode = (entry) => (typeof entry === 'string' ? entry : entry?.code)

  useEffect(() => {
    const controller = new AbortController()

    const loadTranslations = async () => {
      try {
        const response = await fetch(getApiUrl(`/api/v1/i18n/${locale}.json`), {
          signal: controller.signal,
          headers: { Accept: 'application/json' },
          cache: 'no-store',
        })

        if (!response.ok) {
          if (locale !== fallbackLocale) {
            setLocale(fallbackLocale)
          }
          return
        }

        const payload = await response.json()
        const bundle = payload?.data != null ? payload.data : payload
        setTranslations(bundle.translations || {})
        const nextFallbackLocale = bundle.default_locale || fallbackLocale
        setFallbackLocale(nextFallbackLocale)

        const nextAvailableLocales = bundle.available_languages?.length
          ? bundle.available_languages
          : bundle.available_locales || []

        setAvailableLocales(nextAvailableLocales)

        const availableCodes = nextAvailableLocales.map(getLocaleCode).filter(Boolean)

        if (!availableCodes.includes(locale) && locale !== nextFallbackLocale) {
          setLocale(nextFallbackLocale)
        }
      } catch {
        // Keep previous translations if request fails.
      }
    }

    loadTranslations()

    return () => controller.abort()
  }, [locale, fallbackLocale])

  useEffect(() => {
    if (typeof document !== 'undefined') {
      document.documentElement.lang = locale
    }
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(LOCALE_STORAGE_KEY, locale)
    }
  }, [locale])

  const t = (key) => {
    const value = resolveKeyPath(translations, key)
    if (typeof value === 'string') {
      return value
    }

    const localeFallback = resolveKeyPath(staticFallbacks[locale] || {}, key)
    if (typeof localeFallback === 'string') {
      return localeFallback
    }

    const defaultFallback = resolveKeyPath(staticFallbacks[fallbackLocale] || {}, key)
    if (typeof defaultFallback === 'string') {
      return defaultFallback
    }

    return key
  }

  return {
    t,
    locale,
    setLocale,
    availableLocales,
  }
}

export function I18nProvider({ children }) {
  const value = useI18nState()

  return createElement(I18nContext.Provider, { value }, children)
}

export function useI18n() {
  const context = useContext(I18nContext)

  if (!context) {
    throw new Error('useI18n must be used within an I18nProvider')
  }

  return context
}
