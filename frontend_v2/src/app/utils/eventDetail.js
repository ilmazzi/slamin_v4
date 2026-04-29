import { formatDateTime, formatLongDate, formatTime } from '../formatters/date.js'
import { formatCurrency } from '../formatters/number.js'

export function normalizeDisplayString(value) {
  const text = String(value ?? '').trim()
  return text !== '' ? text : null
}

export function formatDisplayValue(value) {
  if (value === null || value === undefined || value === '') {
    return '-'
  }

  if (Array.isArray(value)) {
    const values = value
      .map((item) => normalizeDisplayString(item))
      .filter(Boolean)
      .join(', ')
    return values || '-'
  }

  if (typeof value === 'boolean') {
    return value ? 'Si' : 'No'
  }

  return String(value)
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
  ]
    .map((item) => normalizeDisplayString(item))
    .filter(Boolean)
}

export function buildEventMetaItems({ event, computed, locale, tr }) {
  if (!event) {
    return []
  }

  return [
    { label: tr('events.detail_page.meta.type', 'Type'), value: computed?.category_display_name ?? event.category },
    { label: tr('events.detail_page.meta.date', 'Date'), value: formatLongDate(event.start_datetime, locale) },
    { label: tr('events.detail_page.meta.cost', 'Cost'), value: formatCurrency(event.entry_fee, locale) },
    { label: tr('events.detail_page.meta.time', 'Time'), value: formatTime(event.start_datetime, locale) },
  ]
}

export function buildEventDetailSections({ event, computed, locale, tr }) {
  if (!event) {
    return []
  }

  const hasValue = (value) => {
    if (value === null || value === undefined || value === '') {
      return false
    }
    return !(Array.isArray(value) && value.length === 0)
  }

  const recurrenceEnabled =
    Boolean(event.is_recurring)
    || hasValue(event.recurrence_type)
    || hasValue(event.recurrence_count)
    || hasValue(event.recurrence_interval)
    || hasValue(event.recurrence_weekdays)
    || hasValue(event.recurrence_monthday)

  const sections = [
    {
      id: 'when',
      icon: 'when',
      title: tr('events.detail_page.sections.when.title', 'When'),
      description: tr('events.detail_page.sections.when.description', 'Date, times and useful deadlines'),
      items: [
        { label: tr('events.detail_page.sections.when.event_date', 'Event date'), value: formatLongDate(event.start_datetime, locale) },
        { label: tr('events.detail_page.sections.when.start_time', 'Start time'), value: formatTime(event.start_datetime, locale) },
        { label: tr('events.detail_page.sections.when.end_time', 'End time'), value: formatTime(event.end_datetime, locale) },
        { label: tr('events.detail_page.sections.when.estimated_duration', 'Estimated duration'), value: hasValue(computed?.duration_hours) ? `${computed.duration_hours} h` : null },
        { label: tr('events.detail_page.sections.when.registration_closes', 'Registration closes'), value: formatDateTime(event.registration_deadline, locale) },
        { label: tr('events.detail_page.sections.when.invites_close', 'Invites close'), value: formatDateTime(event.invitation_deadline, locale) },
      ],
    },
    {
      id: 'where',
      icon: 'where',
      title: tr('events.detail_page.sections.where.title', 'Where'),
      description: tr('events.detail_page.sections.where.description', 'Location and access mode'),
      items: [
        { label: tr('events.detail_page.sections.where.city', 'City'), value: event.city },
        { label: tr('events.detail_page.sections.where.location', 'Location'), value: event.venue_name },
        { label: tr('events.detail_page.sections.where.address', 'Address'), value: event.venue_address },
        { label: tr('events.detail_page.sections.where.online_event', 'Online event'), value: event.is_online ? tr('events.detail_page.common.yes', 'Yes') : null },
        { label: tr('events.detail_page.sections.where.online_link', 'Online link'), value: event.is_online ? event.online_url : null },
        { label: tr('events.detail_page.sections.where.timezone', 'Timezone'), value: event.timezone },
      ],
    },
    {
      id: 'participation',
      icon: 'participation',
      title: tr('events.detail_page.sections.participation.title', 'Participation'),
      description: tr('events.detail_page.sections.participation.description', 'How to join, costs and availability'),
      items: [
        { label: tr('events.detail_page.sections.participation.cost', 'Cost'), value: formatCurrency(event.entry_fee, locale) },
        { label: tr('events.detail_page.sections.participation.available_slots', 'Available slots'), value: event.max_participants },
        { label: tr('events.detail_page.sections.participation.registrations', 'Registrations'), value: computed?.is_registration_open ? tr('events.detail_page.common.open', 'Open') : tr('events.detail_page.common.closed', 'Closed') },
        { label: tr('events.detail_page.sections.participation.applications', 'Applications'), value: event.allow_requests ? tr('events.detail_page.common.active', 'Active') : null },
        { label: tr('events.detail_page.sections.participation.capacity', 'Capacity'), value: computed?.is_full ? tr('events.detail_page.sections.participation.capacity_full', 'No slots left') : tr('events.detail_page.sections.participation.capacity_available', 'Slots available') },
        { label: tr('events.detail_page.sections.participation.confirmed_participants', 'Confirmed participants'), value: computed?.accepted_participants_count },
      ],
    },
    {
      id: 'useful',
      icon: 'useful',
      title: tr('events.detail_page.sections.useful.title', 'Useful info'),
      description: tr('events.detail_page.sections.useful.description', 'Additional event details'),
      items: [
        { label: tr('events.detail_page.sections.useful.event_type', 'Event type'), value: computed?.category_display_name ?? event.category },
        { label: tr('events.detail_page.sections.useful.subtitle', 'Subtitle'), value: event.subtitle },
        { label: tr('events.detail_page.sections.useful.tags', 'Tags'), value: event.tags },
        { label: tr('events.detail_page.sections.useful.required_roles', 'Required roles'), value: event.gig_positions },
        { label: tr('events.detail_page.sections.useful.organizer_notes', 'Organizer notes'), value: event.availability_instructions },
      ],
    },
    recurrenceEnabled
      ? {
          id: 'recurrence',
          icon: 'when',
          title: tr('events.detail_page.sections.recurrence.title', 'Recurrence'),
          description: tr('events.detail_page.sections.recurrence.description', 'Shown only for recurring events'),
          items: [
            { label: tr('events.detail_page.sections.recurrence.type', 'Recurrence type'), value: event.recurrence_type },
            { label: tr('events.detail_page.sections.recurrence.interval', 'Interval'), value: event.recurrence_interval },
            { label: tr('events.detail_page.sections.recurrence.occurrences', 'Occurrences'), value: event.recurrence_count },
            { label: tr('events.detail_page.sections.recurrence.days', 'Days'), value: event.recurrence_weekdays },
            { label: tr('events.detail_page.sections.recurrence.day_of_month', 'Day of month'), value: event.recurrence_monthday },
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

