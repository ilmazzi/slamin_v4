function primaryStartIso(apiEvent) {
  const list = Array.isArray(apiEvent?.occurrences) ? apiEvent.occurrences : []
  if (list.length === 0) {
    return null
  }
  const primary = list.find((o) => o?.is_primary) || list[0]
  return primary?.starts_at ?? null
}

/**
 * Adapts `EventResource` to the flatter UI shape used by maps, cards and calendars.
 */
export function normalizeEventForUi(apiEvent) {
  if (!apiEvent) {
    return null
  }

  const venue = apiEvent.venue
  const latitude = venue?.latitude != null ? Number(venue.latitude) : null
  const longitude = venue?.longitude != null ? Number(venue.longitude) : null
  const startDatetime = primaryStartIso(apiEvent)
  const category = apiEvent.category
  const categoryLabel = (category && (category.name || category.slug)) || ''
  const hasCost = Boolean(apiEvent.has_cost)
  const price = apiEvent.price != null ? Number(apiEvent.price) : null

  return {
    id: apiEvent.id,
    slug: apiEvent.slug,
    title: apiEvent.title,
    subtitle: apiEvent.subtitle,
    description: apiEvent.description,
    latitude,
    longitude,
    city: venue?.city || '',
    venue_name: venue?.place_name || '',
    venue_address: venue?.address_line || '',
    start_datetime: startDatetime,
    category: categoryLabel,
    image_url: apiEvent.cover_image_url || null,
    has_cost: hasCost,
    entry_fee: hasCost && price != null && !Number.isNaN(price) ? price : null,
  }
}

export function normalizeEventsForUi(list) {
  if (!Array.isArray(list)) {
    return []
  }

  return list.map((item) => normalizeEventForUi(item)).filter(Boolean)
}

/**
 * Builds one calendar row per occurrence for organizer/personal calendars.
 */
export function expandEventOccurrencesForCalendar(apiEvent, { relations = ['owned'] } = {}) {
  const base = normalizeEventForUi(apiEvent)
  if (!base) {
    return []
  }

  const occurrences = Array.isArray(apiEvent?.occurrences) ? apiEvent.occurrences : []
  if (occurrences.length === 0) {
    return []
  }

  return occurrences
    .filter((occurrence) => occurrence?.starts_at)
    .map((occurrence) => ({
      id: base.id,
      occurrenceId: occurrence.id,
      slug: base.slug,
      title: base.title,
      subtitle: base.subtitle || '',
      start_datetime: occurrence.starts_at,
      venue_name: base.venue_name,
      city: base.city,
      relations,
    }))
}

