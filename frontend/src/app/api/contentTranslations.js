import { apiGet, authApiDelete, authApiPut } from './client.js'

const BASE_PATHS = {
  poem: '/api/v1/poems',
  article: '/api/v1/articles',
}

function basePath(contentType) {
  const path = BASE_PATHS[contentType]
  if (!path) {
    throw new Error(`Unsupported content type: ${contentType}`)
  }
  return path
}

export async function getContentTranslation(contentType, slug, locale, { token, signal } = {}) {
  const { data } = await apiGet(
    `${basePath(contentType)}/${encodeURIComponent(slug)}/translations/${encodeURIComponent(locale)}`,
    { token, signal, fallbackMessage: 'Unable to load translation' },
  )
  return data?.translation ?? null
}

export async function saveContentTranslation(contentType, id, locale, payload, { token, signal } = {}) {
  const { data } = await authApiPut(
    `${basePath(contentType)}/${encodeURIComponent(id)}/translations/${encodeURIComponent(locale)}`,
    payload,
    { token, signal, fallbackMessage: 'Unable to save translation' },
  )
  return data?.translation ?? null
}

export async function deleteContentTranslation(contentType, id, locale, { token, signal } = {}) {
  await authApiDelete(
    `${basePath(contentType)}/${encodeURIComponent(id)}/translations/${encodeURIComponent(locale)}`,
    { token, signal, fallbackMessage: 'Unable to delete translation' },
  )
}
