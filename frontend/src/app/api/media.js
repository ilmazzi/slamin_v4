import { apiGet, authApiGet, authApiPatch, authApiPost } from './client.js'

export async function listMedia({
  page = 1,
  perPage = 12,
  q = '',
  type = '',
  sort = 'newest',
  token: paramsToken,
} = {}, { token: optionToken, signal } = {}) {
  const token = optionToken ?? paramsToken
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))

  const query = String(q ?? '').trim()
  if (query) {
    params.set('q', query)
  }

  const mediaType = String(type ?? '').trim()
  if (mediaType) {
    params.set('type', mediaType)
  }

  const sortValue = String(sort ?? '').trim()
  if (sortValue && sortValue !== 'newest') {
    params.set('sort', sortValue)
  }

  const path = `/api/v1/media?${params.toString()}`
  const fetcher = token ? authApiGet : apiGet
  const { data, meta } = await fetcher(path, token
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

export async function syncMediaVideoStatus(mediaId, { token, signal } = {}) {
  const safeId = String(mediaId ?? '').trim()
  if (!safeId) {
    return null
  }

  const fetcher = token ? authApiGet : apiGet
  const { data } = await fetcher(`/api/v1/media/${encodeURIComponent(safeId)}/video-status`, token
    ? {
        token,
        signal,
        fallbackMessage: 'Unable to refresh video status',
      }
    : {
        signal,
        fallbackMessage: 'Unable to refresh video status',
      })

  return data?.media ?? null
}

export async function getMyMediaLimits({ token, signal } = {}) {
  const result = await authApiGet('/api/v1/my/media/limits', {
    token,
    signal,
    allowStatuses: [403],
    fallbackMessage: 'Unable to load media limits',
  })

  if (!result.ok) {
    const error = new Error(result.status === 403 ? 'FORBIDDEN' : 'Unable to load media limits')
    error.status = result.status
    throw error
  }

  return result.data?.limits ?? null
}

export async function uploadPhotoMedia(payload, { token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/media/photos', payload, {
    token,
    signal,
    fallbackMessage: 'Unable to upload photo',
  })

  return data?.media ?? null
}

export async function uploadVideoMedia(payload, { token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/media/videos', payload, {
    token,
    signal,
    fallbackMessage: 'Unable to upload video',
  })

  return data?.media ?? null
}

export async function getMediaSettings({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/media/settings', {
    token,
    signal,
    fallbackMessage: 'Unable to load media settings',
  })

  return data?.settings ?? null
}

export async function updateMediaSettings(payload, { token, signal } = {}) {
  const { data } = await authApiPatch('/api/v1/admin/media/settings', payload, {
    token,
    signal,
    fallbackMessage: 'Unable to save media settings',
  })

  return data?.settings ?? null
}

export async function testPeerTubeConnection({ token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/admin/media/settings/test-peertube', {}, {
    token,
    signal,
    fallbackMessage: 'Unable to test PeerTube connection',
  })

  return Boolean(data?.connected)
}
