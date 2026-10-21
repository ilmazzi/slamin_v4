import { formatDateTime, formatLongDate, formatTime } from '../formatters/date.js'
import { formatCurrency } from '../formatters/number.js'

export function normalizeDisplayString(value) {
  if (value === null || value === undefined) {
    return null
  }

  if (typeof value === 'object') {
    if (Array.isArray(value)) {
      const joined = value.map((item) => normalizeDisplayString(item)).filter(Boolean).join(', ')
      return joined || null
    }

    const nested = value.name ?? value.title ?? value.label ?? value.slug ?? value.display_name ?? value.nickname
    return normalizeDisplayString(nested)
  }

  const text = String(value).trim()
  if (!text || text === '[object Object]') {
    return null
  }

  return text
}

export function formatDisplayValue(value, labels = {}) {
  if (value === null || value === undefined || value === '') {
    return '-'
  }

  if (typeof value === 'boolean') {
    return value ? (labels.yes ?? 'Si') : (labels.no ?? 'No')
  }

  if (Array.isArray(value)) {
    return normalizeDisplayString(value) || '-'
  }

  if (typeof value === 'object') {
    return normalizeDisplayString(value) || '-'
  }

  return String(value)
}

function primaryOccurrence(event) {
  const list = Array.isArray(event?.occurrences) ? event.occurrences : []
  return list.find((item) => item?.is_primary) || list[0] || null
}

function hoursBetween(startIso, endIso) {
  const start = Date.parse(startIso ?? '')
  const end = Date.parse(endIso ?? '')
  if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
    return null
  }

  return Math.round(((end - start) / 36e5) * 10) / 10
}

function countAcceptedInvites(invites) {
  if (!Array.isArray(invites)) {
    return 0
  }

  return invites.filter((invite) => {
    const status = String(invite?.status ?? '').toLowerCase()
    return status === 'accepted' || status === 'confirmed'
  }).length
}

export function flattenEventForDetail(event) {
  if (!event) {
    return null
  }

  const venue = event.venue && typeof event.venue === 'object' ? event.venue : null
  const online = event.online && typeof event.online === 'object' ? event.online : null
  const occurrence = primaryOccurrence(event)
  const recurrence = event.recurrence && typeof event.recurrence === 'object' ? event.recurrence : null
  const poll = event.availability_poll && typeof event.availability_poll === 'object' ? event.availability_poll : null
  const categoryName = normalizeDisplayString(event.category)
  const startDatetime = occurrence?.starts_at ?? event.start_datetime ?? null
  const endDatetime = occurrence?.ends_at ?? event.end_datetime ?? null
  const timezone = occurrence?.timezone ?? online?.timezone ?? event.timezone ?? null
  const isOnline = event.delivery_mode === 'online' || Boolean(online?.meeting_url) || Boolean(event.is_online)
  const price = event.price != null ? Number(event.price) : (event.entry_fee != null ? Number(event.entry_fee) : null)
  const hasCost = Boolean(event.has_cost) && Number.isFinite(price) && price > 0
  const capacity = event.audience_capacity != null ? Number(event.audience_capacity) : (event.max_participants != null ? Number(event.max_participants) : null)
  const acceptedAudience = countAcceptedInvites(event.audience_invites)
  const registrationDeadline = event.registration_deadline ?? null
  const registrationOpen = registrationDeadline
    ? Date.parse(registrationDeadline) > Date.now()
    : true

  return {
    ...event,
    category_name: categoryName,
    start_datetime: startDatetime,
    end_datetime: endDatetime,
    timezone,
    city: event.city ?? venue?.city ?? null,
    venue_name: event.venue_name ?? venue?.place_name ?? null,
    venue_address: event.venue_address ?? venue?.address_line ?? null,
    postal_code: venue?.postal_code ?? null,
    country_code: venue?.country_code ?? null,
    latitude: event.latitude ?? venue?.latitude ?? null,
    longitude: event.longitude ?? venue?.longitude ?? null,
    is_online: isOnline,
    online_url: event.online_url ?? online?.meeting_url ?? null,
    has_cost: hasCost,
    entry_fee: hasCost ? price : null,
    currency: event.currency || 'EUR',
    audience_capacity_enabled: Boolean(event.audience_capacity_enabled),
    max_participants: Boolean(event.audience_capacity_enabled) && Number.isFinite(capacity) ? capacity : null,
    duration_hours: hoursBetween(startDatetime, endDatetime),
    is_registration_open: registrationOpen,
    accepted_participants_count: acceptedAudience || countAcceptedInvites(event.participant_invites),
    is_full: Number.isFinite(capacity) && Boolean(event.audience_capacity_enabled) && acceptedAudience >= capacity,
    recurrence_type: recurrence?.frequency ?? event.recurrence_type ?? null,
    recurrence_interval: recurrence?.interval ?? event.recurrence_interval ?? null,
    recurrence_count: recurrence?.occurrences_count ?? event.recurrence_count ?? null,
    recurrence_ends_at: recurrence?.ends_at ?? null,
    availability_deadline: poll?.response_deadline ?? event.invitation_deadline ?? null,
    availability_instructions: poll?.instructions ?? event.availability_instructions ?? null,
    festival_title: normalizeDisplayString(event.festival),
    organizer_name: event.author?.display_name || event.author?.nickname || null,
  }
}

export function isEventOwner(user, event) {
  if (!event) {
    return false
  }

  if (event.is_owner === true) {
    return true
  }

  const userPublicId = user?.public_id ? String(user.public_id) : ''
  const authorPublicId = event?.author?.public_id ? String(event.author.public_id) : ''
  if (userPublicId && authorPublicId) {
    return userPublicId === authorPublicId
  }

  return false
}

export function canManageEvent(user, event) {
  if (!event) {
    return false
  }

  if (event.can_manage === true) {
    return true
  }

  return isEventOwner(user, event)
}

export function hasEventCoordinates(event) {
  if (!event) {
    return false
  }

  const latitude = Number(event.latitude ?? event.venue?.latitude)
  const longitude = Number(event.longitude ?? event.venue?.longitude)

  return Number.isFinite(latitude) && Number.isFinite(longitude)
}

export function buildEventLocationLines(event) {
  if (!event) {
    return []
  }

  const venue = event.venue && typeof event.venue === 'object' ? event.venue : null

  return [
    event.city ?? venue?.city,
    event.venue_name ?? venue?.place_name,
    event.venue_address ?? venue?.address_line,
    event.postal_code ?? venue?.postal_code,
  ]
    .map((item) => normalizeDisplayString(item))
    .filter(Boolean)
}

export function buildEventMetaItems({ event, locale, t }) {
  if (!event) {
    return []
  }

  const costValue = event.has_cost && event.entry_fee != null
    ? formatCurrency(event.entry_fee, locale, event.currency || 'EUR')
    : t('events.detail_page.common.free')

  return [
    { label: t('events.detail_page.meta.type'), value: event.category_name },
    { label: t('events.detail_page.meta.date'), value: formatLongDate(event.start_datetime, locale) },
    { label: t('events.detail_page.meta.cost'), value: costValue },
    { label: t('events.detail_page.meta.time'), value: formatTime(event.start_datetime, locale) },
  ]
}

function translateDeliveryMode(mode, t) {
  if (mode === 'online') {
    return t('events.create_page.delivery_modes.online')
  }
  if (mode === 'in_person') {
    return t('events.create_page.delivery_modes.in_person')
  }
  return mode || null
}

function translateRecurrence(frequency, t) {
  if (frequency === 'daily') {
    return t('events.create_page.ui.daily')
  }
  if (frequency === 'weekly') {
    return t('events.create_page.ui.weekly')
  }
  if (frequency === 'monthly') {
    return t('events.create_page.ui.monthly')
  }
  return frequency || null
}

function translateStatus(status, t) {
  const key = {
    draft: 'events.detail_page.status.draft',
    pending_review: 'events.detail_page.status.pending_review',
    rejected: 'events.detail_page.status.rejected',
    published: 'events.detail_page.status.published',
  }[String(status ?? '')]

  return key ? t(key) : status || null
}

function translateScheduleMode(mode, t) {
  const key = {
    fixed: 'events.detail_page.schedule_modes.fixed',
    recurring: 'events.detail_page.schedule_modes.recurring',
    availability: 'events.detail_page.schedule_modes.availability',
  }[String(mode ?? '')]

  return key ? t(key) : mode || null
}

function formatExtraOccurrences(occurrences, locale) {
  if (!Array.isArray(occurrences)) {
    return null
  }

  const extra = occurrences.filter((item) => !item?.is_primary)
  if (extra.length === 0) {
    return null
  }

  const lines = extra.map((item) => {
    const start = formatDateTime(item?.starts_at, locale)
    const end = formatTime(item?.ends_at, locale)
    return [start, end].filter(Boolean).join(' – ')
  }).filter(Boolean)

  return lines.length ? lines.join('\n') : null
}

function formatAvailabilityOptions(options, locale) {
  if (!Array.isArray(options) || options.length === 0) {
    return null
  }

  const lines = options.map((option, index) => {
    const start = formatDateTime(option?.starts_at, locale)
    const end = formatTime(option?.ends_at, locale)
    const note = normalizeDisplayString(option?.description)
    const parts = [start, end].filter(Boolean).join(' – ')
    const label = note ? `${parts} (${note})` : parts
    return label || `${index + 1}`
  }).filter(Boolean)

  return lines.length ? lines.join('\n') : null
}

export function buildEventDetailSections({ event, locale, t }) {
  if (!event) {
    return []
  }

  const hasValue = (value) => {
    if (value === null || value === undefined || value === '') {
      return false
    }
    return !(Array.isArray(value) && value.length === 0)
  }

  const recurrenceEnabled = Boolean(event.recurrence) || hasValue(event.recurrence_type)
  const costValue = event.has_cost && event.entry_fee != null
    ? formatCurrency(event.entry_fee, locale, event.currency || 'EUR')
    : t('events.detail_page.common.free')

  const sections = [
    {
      id: 'when',
      icon: 'when',
      title: t('events.detail_page.sections.when.title'),
      description: t('events.detail_page.sections.when.description'),
      items: [
        { label: t('events.detail_page.sections.when.event_date'), value: formatLongDate(event.start_datetime, locale) },
        { label: t('events.detail_page.sections.when.start_time'), value: formatTime(event.start_datetime, locale) },
        { label: t('events.detail_page.sections.when.end_time'), value: formatTime(event.end_datetime, locale) },
        { label: t('events.detail_page.sections.when.estimated_duration'), value: hasValue(event.duration_hours) ? `${event.duration_hours} h` : null },
        { label: t('events.detail_page.sections.when.registration_closes'), value: formatDateTime(event.registration_deadline, locale) },
        { label: t('events.detail_page.sections.when.invites_close'), value: formatDateTime(event.availability_deadline, locale) },
        { label: t('events.detail_page.sections.when.other_dates'), value: formatExtraOccurrences(event.occurrences, locale) },
      ],
    },
    {
      id: 'where',
      icon: 'where',
      title: t('events.detail_page.sections.where.title'),
      description: t('events.detail_page.sections.where.description'),
      items: [
        { label: t('events.detail_page.sections.where.city'), value: event.city },
        { label: t('events.detail_page.sections.where.location'), value: event.venue_name },
        { label: t('events.detail_page.sections.where.address'), value: event.venue_address },
        { label: t('events.detail_page.sections.where.postal_code'), value: event.postal_code },
        { label: t('events.detail_page.sections.where.country'), value: event.country_code },
        { label: t('events.detail_page.sections.where.delivery_mode'), value: translateDeliveryMode(event.delivery_mode, t) },
        { label: t('events.detail_page.sections.where.online_event'), value: event.is_online ? t('events.detail_page.common.yes') : null },
        { label: t('events.detail_page.sections.where.online_link'), value: event.is_online ? event.online_url : null },
        { label: t('events.detail_page.sections.where.timezone'), value: event.timezone },
      ],
    },
    {
      id: 'participation',
      icon: 'participation',
      title: t('events.detail_page.sections.participation.title'),
      description: t('events.detail_page.sections.participation.description'),
      items: [
        { label: t('events.detail_page.sections.participation.cost'), value: costValue },
        { label: t('events.detail_page.sections.participation.available_slots'), value: event.audience_capacity_enabled ? event.max_participants : null },
        { label: t('events.detail_page.sections.participation.registrations'), value: event.registration_deadline ? (event.is_registration_open ? t('events.detail_page.common.open') : t('events.detail_page.common.closed')) : null },
        { label: t('events.detail_page.sections.participation.public_invites'), value: event.public_invite_enabled ? t('events.detail_page.common.active') : null },
        { label: t('events.detail_page.sections.participation.capacity'), value: event.audience_capacity_enabled ? (event.is_full ? t('events.detail_page.sections.participation.capacity_full') : t('events.detail_page.sections.participation.capacity_available')) : null },
        { label: t('events.detail_page.sections.participation.confirmed_participants'), value: event.accepted_participants_count > 0 || event.audience_capacity_enabled ? event.accepted_participants_count : null },
      ],
    },
    {
      id: 'useful',
      icon: 'useful',
      title: t('events.detail_page.sections.useful.title'),
      description: t('events.detail_page.sections.useful.description'),
      items: [
        { label: t('events.detail_page.sections.useful.event_type'), value: event.category_name },
        { label: t('events.detail_page.sections.useful.subtitle'), value: event.subtitle },
        { label: t('events.detail_page.sections.useful.language'), value: event.language },
        { label: t('events.detail_page.sections.useful.requirements'), value: event.requirements },
        { label: t('events.detail_page.sections.useful.organizer_notes'), value: event.availability_instructions },
        { label: t('events.detail_page.sections.useful.organizer'), value: event.organizer_name },
        { label: t('events.detail_page.sections.useful.festival'), value: event.festival_title },
        { label: t('events.detail_page.sections.useful.promo_video'), value: event.promo_video_url },
        { label: t('events.detail_page.sections.useful.schedule_mode'), value: translateScheduleMode(event.schedule_mode, t) },
        { label: t('events.detail_page.sections.useful.visibility'), value: typeof event.is_public === 'boolean' ? (event.is_public ? t('events.create_page.visibility.public') : t('events.create_page.visibility.private')) : null },
        { label: t('events.detail_page.sections.useful.status'), value: translateStatus(event.status, t) },
        { label: t('events.detail_page.sections.useful.published_at'), value: formatDateTime(event.published_at, locale) },
      ],
    },
    recurrenceEnabled
      ? {
          id: 'recurrence',
          icon: 'when',
          title: t('events.detail_page.sections.recurrence.title'),
          description: t('events.detail_page.sections.recurrence.description'),
          items: [
            { label: t('events.detail_page.sections.recurrence.type'), value: translateRecurrence(event.recurrence_type, t) },
            { label: t('events.detail_page.sections.recurrence.interval'), value: event.recurrence_interval },
            { label: t('events.detail_page.sections.recurrence.occurrences'), value: event.recurrence_count },
            { label: t('events.detail_page.sections.recurrence.ends_at'), value: formatDateTime(event.recurrence_ends_at, locale) },
          ],
        }
      : null,
    Array.isArray(event.availability_options) && event.availability_options.length > 0
      ? {
          id: 'availability',
          icon: 'when',
          title: t('events.detail_page.sections.availability.title'),
          description: t('events.detail_page.sections.availability.description'),
          items: [
            { label: t('events.detail_page.sections.availability.options'), value: formatAvailabilityOptions(event.availability_options, locale) },
          ],
        }
      : null,
  ]

  return sections
    .filter(Boolean)
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => hasValue(item.value)),
    }))
    .filter((section) => section.items.length > 0)
}
