import { apiGet, authApiGet } from './client.js'

const MAX_PER_PAGE = 50
const MAX_FETCH_PAGES = 20

export async function listEvents({ page = 1, perPage = 10 } = {}, { signal } = {}) {
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  params.set('page', String(page))

  const { data, meta } = await apiGet(`/api/v1/events?${params.toString()}`, {
    signal,
    fallbackMessage: 'Failed to fetch events',
  })

  const items = Array.isArray(data) ? data : []
  return { items, meta }
}

export async function listAllPublicEvents({
  signal,
  maxPages = MAX_FETCH_PAGES,
  perPage = MAX_PER_PAGE,
} = {}) {
  const all = []
  let page = 1
  let lastPage = 1

  while (page <= lastPage && page <= maxPages) {
    const { items, meta } = await listEvents({ page, perPage }, { signal })
    all.push(...items)
    lastPage = Math.max(1, Number(meta?.last_page ?? 1))
    page += 1
  }

  return all
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

  const all = []
  let page = 1
  let lastPage = 1

  while (page <= lastPage && page <= maxPages) {
    const params = new URLSearchParams()
    params.set('per_page', String(perPage))
    params.set('page', String(page))

    const result = await authApiGet(`/api/v1/my/events?${params.toString()}`, {
      token,
      signal,
      fallbackMessage: 'Failed to fetch my events',
      allowStatuses: [403, 404],
    })

    if (result.status === 403 || result.status === 404) {
      return []
    }

    const items = Array.isArray(result.data) ? result.data : []
    all.push(...items)
    lastPage = Math.max(1, Number(result.meta?.last_page ?? 1))
    page += 1
  }

  return all
}

export async function getEventDetail(slug, { token, signal } = {}) {
  const safeSlug = String(slug ?? '').trim()
  if (!safeSlug) {
    return { ok: false, status: 400, data: null, payload: null }
  }

  return authApiGet(`/api/v1/events/${encodeURIComponent(safeSlug)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load event details.',
    allowStatuses: [401, 404],
  })
}

