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
    statistics: {
      title: 'Le tue statistiche',
      subtitle: 'Scopri come la community interagisce con ciò che pubblichi.',
      private_label: 'Solo per te',
      back: 'Torna alla dashboard',
      view_all: 'Vedi tutte le statistiche',
      loading: 'Caricamento statistiche...',
      load_error: 'Non è stato possibile caricare le statistiche.',
      retry: 'Riprova',
      metrics_title: 'Interazioni ricevute',
      metrics: {
        views_received: 'visualizzazioni ricevute',
        likes_received: 'mi piace ricevuti',
        comments_received: 'commenti ricevuti',
        followers: 'follower',
      },
      publications_title: 'Le tue pubblicazioni',
      publications_subtitle: 'Sono conteggiati solo i contenuti pubblicati e visibili a tutti.',
      publications: { poems: 'Poesie', articles: 'Articoli', events: 'Eventi', media: 'Media' },
      top_views: 'Più visualizzati',
      top_likes: 'Più apprezzati',
      empty: 'Non ci sono ancora dati per questa classifica.',
      untitled: 'Senza titolo',
      item_metrics: 'Visualizzazioni, mi piace e commenti',
      content_types: { poem: 'Poesia', article: 'Articolo', event: 'Evento', media: 'Media' },
    },
    poems: {
      form: {
        edit_title: 'Modifica poesia',
        edit_lead: 'Aggiorna il testo e le informazioni della tua poesia.',
        error_not_owner: 'Non puoi modificare questa poesia.',
        error_updating_poem: 'Impossibile aggiornare la poesia.',
        success_updating_poem: 'Poesia aggiornata con successo.',
      },
    },
    articles: {
      form: {
        edit_title: 'Modifica articolo',
        edit_lead: 'Aggiorna il testo e le informazioni del tuo articolo.',
        error_not_owner: 'Non puoi modificare questo articolo.',
        error_updating_article: 'Impossibile aggiornare l’articolo.',
        success_updating_article: 'Articolo aggiornato con successo.',
      },
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
    },
    support: {
      title: 'Supporto',
      subtitle: 'Hai bisogno di aiuto? Compila il form sottostante e il nostro team ti risponderà il prima possibile.',
      name: 'Nome',
      name_placeholder: 'Il tuo nome completo',
      email: 'E-mail',
      email_placeholder: 'La tua e-mail',
      category: 'Categoria',
      category_placeholder: 'Seleziona una categoria',
      subject: 'Oggetto',
      subject_placeholder: 'Descrivi brevemente il problema',
      message: 'Messaggio',
      message_placeholder: 'Descrivi il tuo problema in dettaglio...',
      message_help: 'Più dettagli fornisci, più velocemente potremo aiutarti.',
      attachments: 'Allegati',
      optional: '(opzionale)',
      choose_files: 'Scegli file',
      no_files: 'Nessun file selezionato',
      attachments_help: 'Puoi allegare fino a 5 screenshot o file (max 10MB per file)',
      cancel: 'Annulla',
      submit: 'Invia richiesta',
      sending: 'Invio...',
      success: 'La richiesta è stata inviata. Ti risponderemo il prima possibile.',
      error: 'Non è stato possibile inviare la richiesta. Riprova più tardi.',
      response_title: 'Tempi di risposta',
      response_text: 'Solitamente rispondiamo entro 24-48 ore nei giorni lavorativi. Per urgenze, contattaci direttamente a',
      categories: {
        technical: 'Problema tecnico',
        account: 'Account e accesso',
        content: 'Contenuti e moderazione',
        events: 'Eventi e ingaggi',
        media: 'Foto e video',
        other: 'Altro',
      },
    },
    profile: {
      page: {
        spoken_languages: 'Lingue parlate',
        spoken_languages_placeholder: 'Italiano, Inglese, Spagnolo',
        spoken_languages_hint: 'Separa le lingue con una virgola. Saranno visibili nel tuo profilo pubblico.',
      },
    },
  },
  en: {
    common: {
      loading: 'Loading...',
      no_results: 'No results',
    },
    statistics: {
      title: 'Your statistics',
      subtitle: 'See how the community interacts with the things you publish.',
      private_label: 'Only for you',
      back: 'Back to dashboard',
      view_all: 'View all statistics',
      loading: 'Loading statistics...',
      load_error: 'Statistics could not be loaded.',
      retry: 'Try again',
      metrics_title: 'Interactions received',
      metrics: {
        views_received: 'views received',
        likes_received: 'likes received',
        comments_received: 'comments received',
        followers: 'followers',
      },
      publications_title: 'Your publications',
      publications_subtitle: 'Only published content visible to everyone is counted.',
      publications: { poems: 'Poems', articles: 'Articles', events: 'Events', media: 'Media' },
      top_views: 'Most viewed',
      top_likes: 'Most liked',
      empty: 'There is no data for this ranking yet.',
      untitled: 'Untitled',
      item_metrics: 'Views, likes and comments',
      content_types: { poem: 'Poem', article: 'Article', event: 'Event', media: 'Media' },
    },
    poems: {
      form: {
        edit_title: 'Edit poem',
        edit_lead: 'Update your poem text and details.',
        error_not_owner: 'You cannot edit this poem.',
        error_updating_poem: 'Unable to update the poem.',
        success_updating_poem: 'Poem updated successfully.',
      },
    },
    articles: {
      form: {
        edit_title: 'Edit article',
        edit_lead: 'Update your article text and details.',
        error_not_owner: 'You cannot edit this article.',
        error_updating_article: 'Unable to update the article.',
        success_updating_article: 'Article updated successfully.',
      },
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
    },
    support: {
      title: 'Support',
      subtitle: 'Need help? Fill in the form below and our team will get back to you as soon as possible.',
      name: 'Name',
      name_placeholder: 'Your full name',
      email: 'Email',
      email_placeholder: 'Your email',
      category: 'Category',
      category_placeholder: 'Select a category',
      subject: 'Subject',
      subject_placeholder: 'Briefly describe the problem',
      message: 'Message',
      message_placeholder: 'Describe your problem in detail...',
      message_help: 'The more details you provide, the faster we can help.',
      attachments: 'Attachments',
      optional: '(optional)',
      choose_files: 'Choose files',
      no_files: 'No files selected',
      attachments_help: 'You can attach up to 5 screenshots or files (max 10MB per file)',
      cancel: 'Cancel',
      submit: 'Send request',
      sending: 'Sending...',
      success: 'Your request has been sent. We will reply as soon as possible.',
      error: 'Your request could not be sent. Please try again later.',
      response_title: 'Response times',
      response_text: 'We usually reply within 24-48 hours on business days. For urgent matters, contact us directly at',
      categories: {
        technical: 'Technical issue',
        account: 'Account and access',
        content: 'Content and moderation',
        events: 'Events and opportunities',
        media: 'Photos and videos',
        other: 'Other',
      },
    },
    profile: {
      page: {
        spoken_languages: 'Spoken languages',
        spoken_languages_placeholder: 'Italian, English, Spanish',
        spoken_languages_hint: 'Separate languages with commas. They will appear on your public profile.',
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
