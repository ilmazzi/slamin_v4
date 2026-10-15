import { useCallback, useEffect, useRef, useState } from 'react'
import { getContentTranslation } from '../api/contentTranslations.js'
import { useAuth } from './useAuth.js'

export function withSavedTranslation(item, translation) {
  if (!item) {
    return item
  }
  const others = (item.translations ?? []).filter((entry) => entry.locale !== translation.locale)
  const translations = [...others, { locale: translation.locale, title: translation.title }]
    .sort((a, b) => a.locale.localeCompare(b.locale))
  return { ...item, translations }
}

export function withoutTranslation(item, locale) {
  if (!item) {
    return item
  }
  return {
    ...item,
    translations: (item.translations ?? []).filter((entry) => entry.locale !== locale),
    translation: item.translation?.locale === locale ? null : item.translation,
  }
}

function initialStateFor(item) {
  const translation = item?.translation ?? null
  return {
    itemId: item?.id ?? null,
    selected: translation?.locale ?? '',
    cache: translation ? { [translation.locale]: translation } : {},
  }
}

/**
 * Tracks which language version of a poem/article is displayed.
 * `selected === ''` means the original text.
 */
export function useContentTranslationView(contentType, item) {
  const { token } = useAuth()
  const [state, setState] = useState(() => initialStateFor(item))
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')
  const requestRef = useRef(null)
  const itemId = item?.id ?? null
  const current = state.itemId === itemId ? state : initialStateFor(item)

  useEffect(() => () => requestRef.current?.abort(), [])

  const update = useCallback(
    (changer) => setState((prev) => changer(prev.itemId === itemId ? prev : initialStateFor(item))),
    [item, itemId],
  )

  const select = useCallback(
    async (locale) => {
      requestRef.current?.abort()
      setError('')

      if (!locale || current.cache[locale]) {
        setIsLoading(false)
        update((base) => ({ ...base, selected: locale || '' }))
        return
      }

      const controller = new AbortController()
      requestRef.current = controller
      setIsLoading(true)

      try {
        const translation = await getContentTranslation(contentType, item.slug, locale, {
          token,
          signal: controller.signal,
        })
        if (translation) {
          update((base) => ({ ...base, selected: locale, cache: { ...base.cache, [locale]: translation } }))
        }
      } catch (err) {
        if (err?.name !== 'AbortError') {
          setError(err?.message || 'Request failed')
        }
      } finally {
        if (requestRef.current === controller) {
          setIsLoading(false)
        }
      }
    },
    [contentType, current.cache, item?.slug, token, update],
  )

  const remember = useCallback(
    (translation) => update((base) => ({
      ...base,
      selected: translation.locale,
      cache: { ...base.cache, [translation.locale]: translation },
    })),
    [update],
  )

  const forget = useCallback(
    (locale) => update((base) => {
      const cache = { ...base.cache }
      delete cache[locale]
      return { ...base, cache, selected: base.selected === locale ? '' : base.selected }
    }),
    [update],
  )

  return {
    selectedLocale: current.selected,
    translation: current.selected ? current.cache[current.selected] ?? null : null,
    isLoading,
    error,
    select,
    remember,
    forget,
  }
}
