import { getApiUrl } from './url.js'
import { readJsonSafe } from './slaminApi.js'
import { authFetch } from './authFetch.js'

export const DEFAULT_EVENT_MAP_CENTER = { lat: 41.9028, lng: 12.4964 }

const MAX_PER_PAGE = 50
const MAX_FETCH_PAGES = 20

function primaryStartIso(apiEvent) {
  const list = Array.isArray(apiEvent?.occurrences) ? apiEvent.occurrences : []
  if (list.length === 0) {
    return null
  }
  const primary = list.find((o) => o?.is_primary) || list[0]
  return primary?.starts_at ?? null
}

/** Adatta `EventResource` (GET /api/v1/events) al formato piatto usato da mappa e card. */
export function normalizeEventForMap(apiEvent) {
  if (!apiEvent) {
    return null
  }
  const v = apiEvent.venue
  const lat = v?.latitude != null ? Number(v.latitude) : null
  const lng = v?.longitude != null ? Number(v.longitude) : null
  const start = primaryStartIso(apiEvent)
  const cat = apiEvent.category
  const categoryLabel = (cat && (cat.name || cat.slug)) || ''
  const hasCost = Boolean(apiEvent.has_cost)
  const price = apiEvent.price != null ? Number(apiEvent.price) : null
  return {
    id: apiEvent.id,
    slug: apiEvent.slug,
    title: apiEvent.title,
    subtitle: apiEvent.subtitle,
    description: apiEvent.description,
    latitude: lat,
    longitude: lng,
    city: v?.city || '',
    venue_name: v?.place_name || '',
    venue_address: v?.address_line || '',
    start_datetime: start,
    category: categoryLabel,
    image_url: apiEvent.cover_image_url || null,
    has_cost: hasCost,
    entry_fee: hasCost && price != null && !Number.isNaN(price) ? price : null,
  }
}

export function computeCenterFromEvents(items) {
  const withCoords = items.filter(
    (e) => Number.isFinite(e.latitude) && Number.isFinite(e.longitude),
  )
  if (withCoords.length === 0) {
    return DEFAULT_EVENT_MAP_CENTER
  }
  const sum = withCoords.reduce(
    (acc, e) => {
      acc.lat += e.latitude
      acc.lng += e.longitude
      return acc
    },
    { lat: 0, lng: 0 },
  )
  return {
    lat: sum.lat / withCoords.length,
    lng: sum.lng / withCoords.length,
  }
}

/**
 * Riga per ogni `occurrence` (stesso evento su più date nel calendario).
 * Dati attesi da GET /api/v1/my/events (EventResource con `occurrences`).
 */
export function expandEventOccurrencesForCalendar(apiEvent, { relations = ['owned'] } = {}) {
  const base = normalizeEventForMap(apiEvent)
  if (!base) {
    return []
  }
  const occ = Array.isArray(apiEvent?.occurrences) ? apiEvent.occurrences : []
  if (occ.length === 0) {
    return []
  }
  return occ
    .filter((o) => o?.starts_at)
    .map((o) => ({
      id: base.id,
      occurrenceId: o.id,
      slug: base.slug,
      title: base.title,
      subtitle: base.subtitle || '',
      start_datetime: o.starts_at,
      venue_name: base.venue_name,
      city: base.city,
      relations,
    }))
}

/**
 * I miei eventi (organizer): GET /api/v1/my/events, tutte le pagine.
 * 403/404 → [] (es. account non organizzatore o route non disponibile).
 */
export async function fetchAllMyEvents({
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
    const response = await authFetch(`/api/v1/my/events?${params.toString()}`, { token, signal })
    if (response.status === 403 || response.status === 404) {
      return []
    }
    if (!response.ok) {
      throw new Error('Failed to fetch my events')
    }
    const payload = await readJsonSafe(response)
    const items = Array.isArray(payload?.data) ? payload.data : []
    all.push(...items)
    lastPage = Math.max(1, Number(payload?.meta?.last_page ?? 1))
    page += 1
  }
  return all
}

export async function fetchAllPublicEvents({ signal, maxPages = MAX_FETCH_PAGES, perPage = MAX_PER_PAGE } = {}) {
  const all = []
  let page = 1
  let lastPage = 1
  while (page <= lastPage && page <= maxPages) {
    const params = new URLSearchParams()
    params.set('per_page', String(perPage))
    params.set('page', String(page))
    const response = await fetch(getApiUrl(`/api/v1/events?${params.toString()}`), {
      signal,
      headers: { Accept: 'application/json' },
    })
    if (!response.ok) {
      throw new Error('Failed to fetch events')
    }
    const payload = await readJsonSafe(response)
    const items = Array.isArray(payload?.data) ? payload.data : []
    all.push(...items)
    lastPage = Math.max(1, Number(payload?.meta?.last_page ?? 1))
    page += 1
  }
  return all
}

export function matchesClientFilters(event, { searchQuery, cityQuery, freeOnly }) {
  if (freeOnly && event.has_cost) {
    return false
  }
  const q = searchQuery.trim().toLowerCase()
  if (q) {
    const text = `${event.title || ''} ${event.description || ''}`.toLowerCase()
    if (!text.includes(q)) {
      return false
    }
  }
  const city = cityQuery.trim().toLowerCase()
  if (city && !String(event.city || '').toLowerCase().includes(city)) {
    return false
  }
  return true
}
