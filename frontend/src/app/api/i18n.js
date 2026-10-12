import { authApiGet, authApiPost, authApiPut } from './client.js'

export async function listI18nLanguages(token, { signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/i18n/languages', {
    token,
    signal,
    fallbackMessage: 'Unable to load languages',
  })

  return data?.languages ?? []
}

export async function listI18nKeys(token, { locale, group, q, missing, page, perPage, signal } = {}) {
  const params = new URLSearchParams()
  if (locale) params.set('locale', locale)
  if (group) params.set('group', group)
  if (q) params.set('q', q)
  if (missing) params.set('missing', '1')
  if (page) params.set('page', String(page))
  if (perPage) params.set('per_page', String(perPage))

  const result = await authApiGet(`/api/v1/admin/i18n/keys?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load translation keys',
  })

  return {
    keys: result.data?.keys ?? [],
    groups: result.data?.groups ?? [],
    locale: result.data?.locale,
    sourceLocale: result.data?.source_locale,
    meta: result.meta,
  }
}

export async function upsertI18nTranslation(token, payload) {
  const { data } = await authApiPut('/api/v1/admin/i18n/translations', payload, {
    token,
    fallbackMessage: 'Unable to save translation',
  })

  return data?.translation ?? null
}

export async function createI18nLanguage(token, payload) {
  const { data } = await authApiPost('/api/v1/admin/i18n/languages', payload, {
    token,
    fallbackMessage: 'Unable to create language',
  })

  return data?.language ?? null
}

export async function createI18nKey(token, payload) {
  const { data } = await authApiPost('/api/v1/admin/i18n/keys', payload, {
    token,
    fallbackMessage: 'Unable to create translation key',
  })

  return data?.key ?? null
}
