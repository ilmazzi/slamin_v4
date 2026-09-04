import { apiGet, authApiGet } from './client.js'

export async function listMedia({ page = 1, perPage = 12 } = {}, { token, signal } = {}) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))

  const fetcher = token ? authApiGet : apiGet
  const { data, meta } = await fetcher(`/api/v1/media?${params.toString()}`, token
    ? {
        token,
        signal,
        fallbackMessage: 'Unable to load media',
      }
    : {
        signal,
        fallbackMessage: 'Unable to load media',
      })

  return {
    items: Array.isArray(data) ? data : [],
    meta,
  }
}

export async function getMediaById(mediaId, { token, signal } = {}) {
  const safeId = String(mediaId ?? '').trim()
  if (!safeId) {
    return null
  }

  const fetcher = token ? authApiGet : apiGet
  const { data } = await fetcher(`/api/v1/media/${encodeURIComponent(safeId)}`, token
    ? {
        token,
        signal,
        fallbackMessage: 'Unable to load media item',
      }
    : {
        signal,
        fallbackMessage: 'Unable to load media item',
      })

  return data?.media ?? null
}
