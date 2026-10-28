export const DEFAULT_EVENT_MAP_CENTER = { lat: 41.9028, lng: 12.4964 }

export function computeCenterFromEvents(items) {
  const withCoords = items.filter(
    (event) => Number.isFinite(event.latitude) && Number.isFinite(event.longitude),
  )

  if (withCoords.length === 0) {
    return DEFAULT_EVENT_MAP_CENTER
  }

  const sum = withCoords.reduce(
    (acc, event) => {
      acc.lat += event.latitude
      acc.lng += event.longitude
      return acc
    },
    { lat: 0, lng: 0 },
  )

  return {
    lat: sum.lat / withCoords.length,
    lng: sum.lng / withCoords.length,
  }
}

export function matchesClientFilters(event, { searchQuery, cityQuery, freeOnly }) {
  if (freeOnly && event.has_cost) {
    return false
  }

  const normalizedSearch = searchQuery.trim().toLowerCase()
  if (normalizedSearch) {
    const text = [
      event.title,
      event.subtitle,
      event.description,
      event.organizer_name,
      event.organizer_nickname,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
    if (!text.includes(normalizedSearch)) {
      return false
    }
  }

  const normalizedCity = cityQuery.trim().toLowerCase()
  if (normalizedCity && !String(event.city || '').toLowerCase().includes(normalizedCity)) {
    return false
  }

  return true
}

