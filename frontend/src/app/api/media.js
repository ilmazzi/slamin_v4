import { apiGet, authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'
import { localeHeaders } from '../i18n/locale.js'
import { readApiErrorMessage } from './slaminApi.js'
import { getApiUrl } from './url.js'

export async function listMedia({
  page = 1,
  perPage = 12,
  q = '',
  type = '',
  sort = 'newest',
  nickname = '',
  excludeId = null,
  excludeUserId = null,
  token: paramsToken,
} = {}, { token: optionToken, signal } = {}) {
  const token = optionToken ?? paramsToken
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  if (nickname) {
    params.set('nickname', String(nickname))
  }
  if (excludeId) {
    params.set('exclude_id', String(excludeId))
  }
  if (excludeUserId) {
    params.set('exclude_user_id', String(excludeUserId))
  }

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

export async function uploadVideoMedia(
  payload,
  { token, signal, onProgress, onUploaded } = {},
) {
  return new Promise((resolve, reject) => {
    const request = new XMLHttpRequest()
    const abort = () => request.abort()

    request.open('POST', getApiUrl('/api/v1/media/videos'))
    request.setRequestHeader('Accept', 'application/json')
    request.setRequestHeader('Authorization', `Bearer ${token}`)
    Object.entries(localeHeaders()).forEach(([name, value]) => request.setRequestHeader(name, value))

    request.upload.addEventListener('progress', (event) => {
      if (!event.lengthComputable || typeof onProgress !== 'function') {
        return
      }

      onProgress({
        loaded: event.loaded,
        total: event.total,
        percent: Math.min(100, Math.round((event.loaded / event.total) * 100)),
      })
    })
    request.upload.addEventListener('load', () => onUploaded?.())

    request.addEventListener('load', () => {
      signal?.removeEventListener('abort', abort)

      let response = {}
      try {
        response = request.responseText ? JSON.parse(request.responseText) : {}
      } catch {
        response = {}
      }

      if (request.status >= 200 && request.status < 300) {
        resolve(response?.data?.media ?? null)
        return
      }

      reject(new Error(readApiErrorMessage(response, 'Unable to upload video')))
    })
    request.addEventListener('error', () => {
      signal?.removeEventListener('abort', abort)
      reject(new Error('Unable to upload video'))
    })
    request.addEventListener('abort', () => {
      signal?.removeEventListener('abort', abort)
      reject(new DOMException('The operation was aborted.', 'AbortError'))
    })

    if (signal?.aborted) {
      request.abort()
      return
    }

    signal?.addEventListener('abort', abort, { once: true })
    request.send(payload)
  })
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

export async function listMyMedia(
  { page = 1, perPage = 50 } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))

  const { data, meta } = await authApiGet(`/api/v1/my/media?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Failed to fetch my media',
  })

  return {
    items: Array.isArray(data) ? data : [],
    meta,
  }
}

export async function updateMediaItem(mediaId, payload, { token, signal } = {}) {
  const { data } = await authApiPatch(`/api/v1/media/${encodeURIComponent(mediaId)}`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to update media',
  })
  return data?.media ?? null
}

export async function deleteMediaItem(mediaId, { token, signal } = {}) {
  await authApiDelete(`/api/v1/media/${encodeURIComponent(mediaId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to delete media',
  })
}
