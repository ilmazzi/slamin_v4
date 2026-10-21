import { apiGet, authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

const MAX_PER_PAGE = 50
const MAX_FETCH_PAGES = 20

async function collectPaginatedItems(fetchPage, { signal, maxPages, perPage }) {
  const all = []
  let page = 1
  let lastPage = 1

  while (page <= lastPage && page <= maxPages) {
    const { items, meta } = await fetchPage({ page, perPage, signal })
    all.push(...items)
    lastPage = Math.max(1, Number(meta?.last_page ?? 1))
    page += 1
  }

  return all
}

export async function listEvents({ page = 1, perPage = 10, nickname = '', token: paramsToken } = {}, { token: optionToken, signal } = {}) {
  const token = optionToken ?? paramsToken
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  params.set('page', String(page))
  if (nickname) {
    params.set('nickname', String(nickname))
  }

  const path = `/api/v1/events?${params.toString()}`
  const fetcher = token ? authApiGet : apiGet
  const { data, meta } = await fetcher(path, token
    ? {
        token,
        signal,
        fallbackMessage: 'Failed to fetch events',
      }
    : {
        signal,
        fallbackMessage: 'Failed to fetch events',
      })

  const items = Array.isArray(data) ? data : []
  return { items, meta }
}

export async function listAllPublicEvents({
  signal,
  token,
  maxPages = MAX_FETCH_PAGES,
  perPage = MAX_PER_PAGE,
} = {}) {
  return collectPaginatedItems(
    ({ page: nextPage, perPage: nextPerPage, signal: nextSignal }) =>
      listEvents({ page: nextPage, perPage: nextPerPage, token }, { signal: nextSignal }),
    { signal, maxPages, perPage },
  )
}

export async function listMyEvents({
  token,
  signal,
  maxPages = MAX_FETCH_PAGES,
  perPage = MAX_PER_PAGE,
} = {}) {
  if (!token) {
    return []
  }

  return collectPaginatedItems(
    async ({ page: nextPage, perPage: nextPerPage, signal: nextSignal }) => {
      const params = new URLSearchParams()
      params.set('per_page', String(nextPerPage))
      params.set('page', String(nextPage))

      const result = await authApiGet(`/api/v1/my/events?${params.toString()}`, {
        token,
        signal: nextSignal,
        fallbackMessage: 'Failed to fetch my events',
        allowStatuses: [403, 404],
      })

      if (result.status === 403 || result.status === 404) {
        return { items: [], meta: { last_page: 0 } }
      }

      return {
        items: Array.isArray(result.data) ? result.data : [],
        meta: result.meta,
      }
    },
    { signal, maxPages, perPage },
  )
}

export async function getEventDetail(slug, { token, signal } = {}) {
  const safeSlug = String(slug ?? '').trim()
  if (!safeSlug) {
    return { ok: false, status: 400, data: null, payload: null }
  }

  if (token) {
    return authApiGet(`/api/v1/events/${encodeURIComponent(safeSlug)}`, {
      token,
      signal,
      fallbackMessage: 'Unable to load event details.',
    })
  }

  try {
    const result = await apiGet(`/api/v1/events/${encodeURIComponent(safeSlug)}`, {
      signal,
      fallbackMessage: 'Unable to load event details.',
    })
    return { ok: true, status: 200, ...result }
  } catch (error) {
    return {
      ok: false,
      status: 500,
      data: null,
      payload: { message: error?.message || 'Unable to load event details.' },
    }
  }
}

export async function listEventCategories({ signal } = {}) {
  const { data, payload } = await apiGet('/api/v1/event-categories', {
    signal,
    fallbackMessage: 'Failed to fetch event categories',
  })

  const direct = Array.isArray(data) ? data : []
  const nested = Array.isArray(data?.categories) ? data.categories : []
  const payloadNested = Array.isArray(payload?.data?.categories) ? payload.data.categories : []
  return { categories: direct.length ? direct : nested.length ? nested : payloadNested }
}

export async function adminListEventCategories({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/event-categories', {
    token,
    signal,
    fallbackMessage: 'Unable to load event categories',
  })

  return Array.isArray(data?.categories) ? data.categories : []
}

export async function adminStoreEventCategory(payload, { token } = {}) {
  const { data } = await authApiPost('/api/v1/admin/event-categories', payload, {
    token,
    fallbackMessage: 'Unable to save event category',
  })

  return data?.category ?? null
}

export async function adminUpdateEventCategory(id, payload, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/admin/event-categories/${encodeURIComponent(id)}`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to save event category',
    },
  )

  return data?.category ?? null
}

export async function adminDeleteEventCategory(id, { token } = {}) {
  await authApiDelete(`/api/v1/admin/event-categories/${encodeURIComponent(id)}`, {
    token,
    fallbackMessage: 'Unable to delete event category',
  })
}

export async function createEvent(eventData, { token, signal } = {}) {
  const payload = buildEventPayload(eventData)
  const { data } = await authApiPost('/api/v1/events', payload, { token, signal })
  return data?.event ?? null
}

export async function updateEvent(eventId, eventData, { token, signal } = {}) {
  const payload = buildEventPayload(eventData)
  const { data } = await authApiPatch(`/api/v1/events/${eventId}`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to update event.',
  })
  return data?.event ?? null
}

export async function deleteEvent(eventId, { token, signal } = {}) {
  await authApiDelete(`/api/v1/events/${encodeURIComponent(eventId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to delete event.',
  })
}

export async function setEventFollow(eventId, following, { token, signal } = {}) {
  const path = `/api/v1/events/${encodeURIComponent(eventId)}/follow`
  const options = { token, signal, fallbackMessage: 'Unable to update follow.' }
  const result = following ? await authApiPost(path, {}, options) : await authApiDelete(path, options)

  return result.data?.follow ?? null
}

function appendIfDefined(formData, key, value) {
  if (value === undefined || value === null || value === '') return
  formData.append(key, String(value))
}

function buildEventPayload(eventData) {
  const hasFile = typeof File !== 'undefined' && eventData?.cover_image instanceof File
  if (!hasFile) return eventData

  const fd = new FormData()
  Object.entries(eventData || {}).forEach(([key, value]) => {
    if (key === 'cover_image') return
    if (Array.isArray(value) || (value && typeof value === 'object')) {
      fd.append(key, JSON.stringify(value))
      return
    }
    appendIfDefined(fd, key, value)
  })
  fd.append('cover_image', eventData.cover_image)
  return fd
}

export async function listRecentEventPlaces({ token, signal, query } = {}) {
  if (!token) return { places: [] }

  const params = new URLSearchParams()
  const search = String(query ?? '').trim()
  if (search) {
    params.set('q', search)
  }

  const queryString = params.toString()
  const path = queryString
    ? `/api/v1/events/recent-places?${queryString}`
    : '/api/v1/events/recent-places'
  const { data } = await authApiGet(path, {
    token,
    signal,
    fallbackMessage: 'Failed to fetch recent places',
    allowStatuses: [403, 404],
  })
  const places = Array.isArray(data?.places)
    ? data.places
    : (Array.isArray(data) ? data : [])

  return { places }
}

export async function searchEventInviteUsers(query, { token, signal, accountType } = {}) {
  const params = new URLSearchParams({ q: String(query ?? '').trim() })
  if (accountType) {
    params.set('account_type', String(accountType))
  }
  const { data } = await authApiGet(`/api/v1/events/invite-users?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Failed to search users',
  })

  return { users: Array.isArray(data) ? data : [] }
}
