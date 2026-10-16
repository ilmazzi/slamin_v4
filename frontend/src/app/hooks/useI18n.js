import { createContext, createElement, useContext, useEffect, useRef, useState } from 'react'
import { getApiUrl } from '../api/url'
import { DEFAULT_LOCALE, getClientLocale, localeHeaders, LOCALE_STORAGE_KEY } from '../i18n/locale.js'

const resolveKeyPath = (source, key) => {
  return key.split('.').reduce((acc, part) => acc?.[part], source)
}

const applyReplacements = (value, replacements) => {
  if (!replacements || typeof replacements !== 'object') {
    return value
  }

  return Object.entries(replacements)
    .sort(([a], [b]) => b.length - a.length)
    .reduce(
    (current, [name, replacement]) => current.replaceAll(`:${name}`, String(replacement)),
    value,
  )
}

const staticFallbacks = {
  it: {
    common: {
      loading: 'Caricamento...',
      no_results: 'Nessun risultato',
    },
    contact: {
      title: 'Contattaci',
      subtitle: 'Hai domande? Siamo qui per aiutarti',
      intro: 'Per qualsiasi domanda, suggerimento o supporto, non esitare a contattarci.',
      email_label: 'E-mail',
    },
    about: {
      title: 'chi siamo',
      statement_first: 'due entità,',
      statement_second: 'una community open-source',
      entities_label: 'Le realtà dietro Slam In',
      slamin_description: 'Slam In It OÜ è un’azienda estone che ha avuto l’idea di creare una piattaforma mondiale per la poesia e gli eventi.',
      contribute: 'Se ti interessa contribuire alla comunità, contattaci via',
      haevol_description: 'Hævol è un’impresa sociale italiana. Sviluppa infrastrutture open-source per progetti culturali e umanitari',
      dashboard: {
        title: 'dashboard',
        person: 'Nome Cognome',
        poems: 'Poesie',
        events: 'Eventi',
        views: 'Visualizzazioni',
        likes: 'Like',
      },
    },
  },
  en: {
    common: {
      loading: 'Loading...',
      no_results: 'No results',
    },
    contact: {
      title: 'Contact us',
      subtitle: 'Have questions? We are here to help',
      intro: 'For any questions, suggestions or support, please do not hesitate to contact us.',
      email_label: 'Email',
    },
    about: {
      title: 'about us',
      statement_first: 'two organizations,',
      statement_second: 'one open-source community',
      entities_label: 'The organizations behind Slam In',
      slamin_description: 'Slam In It OÜ is an Estonian company that came up with the idea of creating a worldwide platform for poetry and events.',
      contribute: 'If you are interested in contributing to the community, contact us at',
      haevol_description: 'Hævol is an Italian social enterprise. It develops open-source infrastructure for cultural and humanitarian projects.',
      dashboard: {
        title: 'dashboard',
        person: 'First Last',
        poems: 'Poems',
        events: 'Events',
        views: 'Views',
        likes: 'Likes',
      },
    },
  },
}

const I18nContext = createContext(null)

function useI18nState() {
  const [locale, setLocale] = useState(getClientLocale)
  const [translations, setTranslations] = useState({})
  const [fallbackLocale, setFallbackLocale] = useState(DEFAULT_LOCALE)
  const fallbackLocaleRef = useRef(DEFAULT_LOCALE)
  const [availableLocales, setAvailableLocales] = useState([])

  const getLocaleCode = (entry) => (typeof entry === 'string' ? entry : entry?.code)

  useEffect(() => {
    const controller = new AbortController()

    const loadTranslations = async () => {
      try {
        const response = await fetch(getApiUrl(`/api/v1/i18n/${locale}.json`), {
          signal: controller.signal,
          headers: { Accept: 'application/json', ...localeHeaders() },
          cache: 'no-store',
        })

        if (!response.ok) {
          if (locale !== fallbackLocaleRef.current) {
            setLocale(fallbackLocaleRef.current)
          }
          return
        }

        const payload = await response.json()
        const bundle = payload?.data != null ? payload.data : payload
        setTranslations(bundle.translations || {})
        const nextFallbackLocale = bundle.default_locale || fallbackLocaleRef.current
        fallbackLocaleRef.current = nextFallbackLocale
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
  }, [locale])

  useEffect(() => {
    if (typeof document !== 'undefined') {
      document.documentElement.lang = locale
    }
    if (typeof window !== 'undefined') {
      window.localStorage.setItem(LOCALE_STORAGE_KEY, locale)
    }
  }, [locale])

  const t = (key, replacements) => {
    const value = resolveKeyPath(translations, key)
    if (typeof value === 'string') {
      return applyReplacements(value, replacements)
    }

    const localeFallback = resolveKeyPath(staticFallbacks[locale] || {}, key)
    if (typeof localeFallback === 'string') {
      return applyReplacements(localeFallback, replacements)
    }

    const defaultFallback = resolveKeyPath(staticFallbacks[fallbackLocale] || {}, key)
    if (typeof defaultFallback === 'string') {
      return applyReplacements(defaultFallback, replacements)
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
