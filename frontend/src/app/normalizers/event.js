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
export function normalizeEventForUi(apiEvent, occurrenceOverride = null) {
  if (!apiEvent) {
    return null
  }

  const venue = apiEvent.venue
  const latitude = venue?.latitude != null ? Number(venue.latitude) : null
  const longitude = venue?.longitude != null ? Number(venue.longitude) : null
  const primaryOccurrence = Array.isArray(apiEvent?.occurrences)
    ? (apiEvent.occurrences.find((occurrence) => occurrence?.is_primary) || apiEvent.occurrences[0] || null)
    : null
  const occurrence = occurrenceOverride || primaryOccurrence
  const startDatetime = occurrence?.starts_at ?? primaryStartIso(apiEvent)
  const category = apiEvent.category
  const categoryLabel = (category && (category.name || category.slug)) || ''
  const hasCost = Boolean(apiEvent.has_cost)
  const price = apiEvent.price != null ? Number(apiEvent.price) : null

  return {
    id: apiEvent.id,
    occurrence_id: occurrence?.id ?? null,
    instance_id: occurrence?.id != null ? `${apiEvent.id}:${occurrence.id}` : String(apiEvent.id),
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
    organizer_name: apiEvent.author?.display_name || apiEvent.author?.nickname || '',
    organizer_nickname: apiEvent.author?.nickname || '',
    organizer_avatar_url: apiEvent.author?.avatar_url || null,
    has_cost: hasCost,
    entry_fee: hasCost && price != null && !Number.isNaN(price) ? price : null,
    views_count: Number(apiEvent.views_count ?? 0),
    likes_count: Number(apiEvent.likes_count ?? 0),
    comments_count: Number(apiEvent.comments_count ?? 0),
    bookmarks_count: Number(apiEvent.bookmarks_count ?? 0),
    is_liked: Boolean(apiEvent.is_liked),
    is_bookmarked: Boolean(apiEvent.is_bookmarked),
    is_owner: Boolean(apiEvent.is_owner),
  }
}

export function normalizeEventsForUi(list) {
  if (!Array.isArray(list)) {
    return []
  }

  return list.flatMap((item) => {
    const occurrences = Array.isArray(item?.occurrences)
      ? [...item.occurrences]
          .filter((occurrence) => occurrence?.starts_at)
          .sort((left, right) => Date.parse(left.starts_at) - Date.parse(right.starts_at))
      : []

    if (occurrences.length === 0) {
      const normalized = normalizeEventForUi(item)
      return normalized ? [normalized] : []
    }

    return occurrences
      .map((occurrence) => normalizeEventForUi(item, occurrence))
      .filter(Boolean)
  })
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

