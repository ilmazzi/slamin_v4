import { emptyAvailability, emptyGig, emptyInvite } from './eventFormDefaults.js'

export function toDatetimeLocalValue(value) {
  if (!value) {
    return ''
  }

  const raw = String(value)
  if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(raw)) {
    return raw
  }

  const parsed = new Date(raw)
  if (Number.isNaN(parsed.getTime())) {
    return raw.slice(0, 16)
  }

  const pad = (part) => String(part).padStart(2, '0')
  return `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())}T${pad(parsed.getHours())}:${pad(parsed.getMinutes())}`
}

export function mapEventToFormValues(event) {
  const occurrence = Array.isArray(event?.occurrences)
    ? (event.occurrences.find((item) => item?.is_primary) || event.occurrences[0] || null)
    : null
  const venue = event?.venue && typeof event.venue === 'object' ? event.venue : null
  const online = event?.online && typeof event.online === 'object' ? event.online : null
  const recurrence = event?.recurrence && typeof event.recurrence === 'object' ? event.recurrence : null
  const poll = event?.availability_poll && typeof event.availability_poll === 'object' ? event.availability_poll : null
  const options = Array.isArray(event?.availability_options) ? event.availability_options : []
  const invites = Array.isArray(event?.participant_invites) ? event.participant_invites : []
  const audienceInvites = Array.isArray(event?.audience_invites) ? event.audience_invites : []
  const groupIds = Array.isArray(event?.group_ids) ? event.group_ids : []
  const status = event?.status === 'draft' || event?.status === 'rejected' ? 'draft' : 'published'

  return {
    title: event?.title ?? '',
    subtitle: event?.subtitle ?? '',
    description: event?.description ?? '',
    requirements: event?.requirements ?? '',
    event_category_id: event?.event_category_id ? String(event.event_category_id) : '',
    festival_event_id: event?.festival_event_id ? String(event.festival_event_id) : '',
    schedule_mode: event?.schedule_mode || 'fixed',
    delivery_mode: event?.delivery_mode || 'in_person',
    has_cost: Boolean(event?.has_cost),
    price: event?.price != null ? String(event.price) : '',
    currency: event?.currency || 'EUR',
    promo_video_url: event?.promo_video_url ?? '',
    registration_deadline: toDatetimeLocalValue(event?.registration_deadline),
    public_invite_enabled: Boolean(event?.public_invite_enabled),
    audience_capacity_enabled: Boolean(event?.audience_capacity_enabled),
    audience_capacity: event?.audience_capacity != null ? String(event.audience_capacity) : '',
    language: event?.language || 'it',
    status,
    is_public: event?.is_public !== false,
    occurrence: {
      starts_at: toDatetimeLocalValue(occurrence?.starts_at),
      ends_at: toDatetimeLocalValue(occurrence?.ends_at),
      timezone: occurrence?.timezone || online?.timezone || 'Europe/Rome',
    },
    recurrence: {
      frequency: recurrence?.frequency || 'weekly',
      interval: recurrence?.interval ?? 1,
      occurrences_count: recurrence?.occurrences_count ?? 1,
    },
    availability_poll: {
      response_deadline: toDatetimeLocalValue(poll?.response_deadline),
      instructions: poll?.instructions ?? '',
    },
    availability_options: options.length
      ? options.map((option) => ({
          starts_at: toDatetimeLocalValue(option?.starts_at),
          ends_at: toDatetimeLocalValue(option?.ends_at),
          timezone: option?.timezone || 'Europe/Rome',
          description: option?.description ?? '',
        }))
      : [{ ...emptyAvailability }],
    venue: {
      place_name: venue?.place_name ?? '',
      country_code: venue?.country_code || 'IT',
      city: venue?.city ?? '',
      postal_code: venue?.postal_code ?? '',
      address_line: venue?.address_line ?? '',
      latitude: venue?.latitude ?? '',
      longitude: venue?.longitude ?? '',
    },
    online: {
      meeting_url: online?.meeting_url ?? '',
      timezone: online?.timezone || occurrence?.timezone || 'Europe/Rome',
    },
    group_ids_text: groupIds.filter(Boolean).join(', '),
    audience_invites: audienceInvites.map((invite) => ({
      invite_type: invite?.invite_type || (invite?.user_id ? 'internal' : 'external'),
      user_id: invite?.user_id ? Number(invite.user_id) : null,
      name: invite?.name ?? invite?.invited_user?.display_name ?? invite?.invited_user?.nickname ?? '',
      nickname: invite?.invited_user?.nickname ?? '',
      email: invite?.email ?? '',
      status: invite?.status || 'pending',
    })),
    participant_invites: invites.length
      ? invites.map((invite) => ({
          invite_type: invite?.invite_type || (invite?.user_id ? 'internal' : 'external'),
          role: invite?.role || 'artist',
          user_id: invite?.user_id ? String(invite.user_id) : '',
          name: invite?.name ?? '',
          email: invite?.email ?? '',
          status: invite?.status || 'pending',
        }))
      : [{ ...emptyInvite }],
    gigs: [{ ...emptyGig }],
    cover_image: null,
  }
}
