import { useEffect, useMemo, useRef, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import L from 'leaflet'
import { MapContainer, Marker, useMap } from 'react-leaflet'
import OsmTileLayer from '../../app/maps/OsmTileLayer.jsx'
import { createEvent, getEventDetail, listEventCategories, listRecentEventPlaces, searchEventInviteUsers, updateEvent } from '../../app/api/events.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { mapEventToFormValues } from '../../app/utils/eventForm.js'
import { isEventOwner } from '../../app/utils/eventDetail.js'
import { emptyAvailability, emptyGig, emptyInvite } from '../../app/utils/eventFormDefaults.js'
import { gigOfferFlags, gigToFormRow, snapshotGigRows, syncEventGigs } from '../../app/utils/eventGigForm.js'
import { listEventGigs } from '../../app/api/gigs.js'
import { EventLocationIcon } from '../icons/ActionIcons.jsx'
import styles from './EventCreateForm.module.css'

const createMarkerSvg = (fill) => `
<svg viewBox="0 0 38 54" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <path d="M0 0V40.589H17.1945L37.9553 54V40.589V13.4055V0H0Z" fill="${fill}"/>
</svg>
`

function MapAutoCenter({ lat, lng, zoom = 14 }) {
  const map = useMap()

  useEffect(() => {
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return
    map.flyTo([lat, lng], zoom, { animate: true, duration: 0.6 })
  }, [lat, lng, map, zoom])

  return null
}

function splitDateTimeParts(value) {
  if (!value) {
    return { date: '', time: '' }
  }

  const [date = '', time = ''] = String(value).split('T')
  return { date, time: time.slice(0, 5) }
}

function mergeDateTimeParts(datePart, timePart) {
  if (!datePart && !timePart) {
    return ''
  }

  const safeDate = datePart || ''
  const safeTime = timePart || '00:00'
  return `${safeDate}T${safeTime}`
}

const PREFILL_START_TIME = '21:00'

function prefilledStartsAt(dateParam) {
  return /^\d{4}-\d{2}-\d{2}$/.test(dateParam || '') ? mergeDateTimeParts(dateParam, PREFILL_START_TIME) : ''
}

function currentLocalDateTime() {
  const now = new Date()
  const local = new Date(now.getTime() - now.getTimezoneOffset() * 60_000)
  return local.toISOString().slice(0, 16)
}

function Field({ label, children, full = false, hint = '' }) {
  return (
    <label className={`${styles.field} ${full ? styles.full : ''}`}>
      <span className={styles.fieldLabel}>{label}</span>
      {hint ? <span className={styles.fieldHint}>{hint}</span> : null}
      {children}
    </label>
  )
}

function DateTimeField({ label, value, onChange, required = false, t }) {
  const parts = splitDateTimeParts(value)
  const dateRequired = required || Boolean(parts.time)
  const timeRequired = required || Boolean(parts.date)

  const setPart = (part, nextValue) => {
    const nextDate = part === 'date' ? nextValue : parts.date
    const nextTime = part === 'time' ? nextValue : parts.time
    onChange(nextDate || nextTime ? `${nextDate}T${nextTime}` : '')
  }

  return (
    <div className={styles.dateTimeField} role="group" aria-label={label}>
      <span className={styles.fieldLabel}>{label}</span>
      <div className={styles.dateTimeParts}>
        <label className={styles.field}>
          <span className={styles.fieldHint}>{t('events.date')}</span>
          <input
            className={styles.input}
            type="date"
            value={parts.date}
            onChange={(event) => setPart('date', event.target.value)}
            required={dateRequired}
          />
        </label>
        <label className={styles.field}>
          <span className={styles.fieldHint}>{t('events.time')}</span>
          <input
            className={styles.input}
            type="time"
            step="900"
            value={parts.time}
            onChange={(event) => setPart('time', event.target.value)}
            required={timeRequired}
          />
        </label>
      </div>
    </div>
  )
}

function Toggle({ checked, onChange }) {
    return (
      <button
        type="button"
        className={`${styles.toggle} ${checked ? styles.toggleOn : ''}`}
        onClick={() => onChange(!checked)}
        aria-pressed={checked}
      >
        <span className={styles.toggleKnob} />
      </button>
    )
  }

function ChoiceButton({ active, children, onClick }) {
  return (
    <button
      type="button"
      className={`${styles.choiceButton} ${active ? styles.choiceButtonActive : ''}`}
      onClick={onClick}
    >
      {children}
    </button>
  )
}

function PublishActions({ status, saving, disabled, t }) {
  const options = [
    { value: 'published', title: t('events.create_page.ui.publish_now'), sub: t('events.create_page.ui.publish_now_sub') },
    { value: 'draft', title: t('events.create_page.save_draft'), sub: t('events.create_page.ui.save_draft_sub') },
  ]

  return (
    <div className={styles.publishRow}>
      {options.map((option) => (
        <button
          key={option.value}
          className={`${styles.choiceButton} ${status === option.value ? styles.choiceButtonActive : ''}`}
          type="submit"
          name="status"
          value={option.value}
          disabled={disabled}
        >
          <span className={styles.choiceTitle}>{saving ? t('events.create_page.saving') : option.title}</span>
          <span className={styles.choiceSub}>{option.sub}</span>
        </button>
      ))}
    </div>
  )
}

function SummaryCard({ title, items }) {
  return (
    <div className={styles.summaryCard}>
      <div className={styles.summaryTitle}>{title}</div>
      <div className={`${styles.summaryGrid} ${items.length === 2 ? styles.summaryGridTwo : ''}`}>
        {items.map((item) => (
          <div key={item.label} className={styles.summaryItem}>
            <div className={styles.summaryLabel}>{item.label}</div>
            <div className={styles.summaryValue}>{item.value || ' '}</div>
          </div>
        ))}
      </div>
    </div>
  )
}

function AccordionSection({ id, title, isOpen, onToggle, children }) {
  return (
    <section className={`${styles.accordion} ${isOpen ? styles.accordionOpen : ''}`} data-accordion-id={id}>
      <button
        type="button"
        className={styles.accordionHeader}
        onClick={() => onToggle(id)}
        aria-expanded={isOpen}
        aria-controls={`event-section-${id}`}
      >
        <span>{title}</span>
        <span className={styles.accordionChevron} aria-hidden="true">⌄</span>
      </button>
      <div
        id={`event-section-${id}`}
        className={styles.accordionClip}
        onFocusCapture={() => {
          if (!isOpen) onToggle(id, true)
        }}
      >
        <div className={styles.accordionContent}>{children}</div>
      </div>
    </section>
  )
}

export default function EventCreateForm({ eventSlug = null }) {
  const { token, user } = useAuth()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const { t } = useI18n()
  const isEdit = Boolean(eventSlug)
  const [eventRecordId, setEventRecordId] = useState(null)
  const [categories, setCategories] = useState([])
  const [recentPlaces, setRecentPlaces] = useState([])
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)
  const [loadingEvent, setLoadingEvent] = useState(isEdit)
  const [geoStatus, setGeoStatus] = useState({ loading: false, error: '' })
  const [showPlaceSuggestions, setShowPlaceSuggestions] = useState(false)
  const [showEmailInvites, setShowEmailInvites] = useState(false)
  const [showAudienceEmailInvites, setShowAudienceEmailInvites] = useState(false)
  const [audienceUserQuery, setAudienceUserQuery] = useState('')
  const [audienceUserResults, setAudienceUserResults] = useState([])
  const [audienceSearchLoading, setAudienceSearchLoading] = useState(false)
  const [showGigs, setShowGigs] = useState(false)
  const [registrationDeadlineEnabled, setRegistrationDeadlineEnabled] = useState(false)
  const [openSections, setOpenSections] = useState(() => new Set(['basic']))
  const gigSnapshotRef = useRef(new Map())
  const deletedGigIdsRef = useRef(new Set())
  const lastGeocodeKeyRef = useRef('')
  const geocodeTimerRef = useRef(null)
  const geocodeAbortRef = useRef(null)
  const [v, setV] = useState(() => ({
    title: '',
    subtitle: '',
    description: '',
    requirements: '',
    event_category_id: '',
    festival_event_id: '',
    schedule_mode: 'fixed',
    delivery_mode: 'in_person',
    has_cost: false,
    price: '',
    currency: 'EUR',
    promo_video_url: '',
    registration_deadline: '',
    public_invite_enabled: false,
    audience_capacity_enabled: false,
    audience_capacity: '',
    language: 'it',
    status: 'published',
    is_public: true,
    occurrence: { starts_at: isEdit ? '' : prefilledStartsAt(searchParams.get('date')), ends_at: '', timezone: 'Europe/Rome' },
    recurrence: { frequency: 'weekly', interval: 1, occurrences_count: 2 },
    availability_poll: { response_deadline: '', instructions: '' },
    availability_options: [emptyAvailability],
    venue: {
      place_name: '',
      country_code: 'IT',
      city: '',
      postal_code: '',
      address_line: '',
      latitude: '',
      longitude: '',
    },
    online: { meeting_url: '', timezone: 'Europe/Rome' },
    group_ids_text: '',
    audience_invites: [],
    participant_invites: [emptyInvite],
    gigs: [emptyGig],
    cover_image: null,
  }))

  function toggleSection(id, forceOpen = false) {
    setOpenSections((current) => {
      const next = new Set(current)
      if (forceOpen) {
        next.add(id)
      } else if (next.has(id)) {
        next.delete(id)
      } else {
        next.add(id)
      }
      return next
    })
  }

  useEffect(() => {
    listEventCategories().then((r) => setCategories(r.categories)).catch(() => {})
  }, [])

  useEffect(() => {
    if (!token) return
    listRecentEventPlaces({ token }).then((r) => setRecentPlaces(r.places)).catch(() => {})
  }, [token])

  useEffect(() => {
    const query = audienceUserQuery.trim()
    if (!token || query.length < 2) {
      setAudienceUserResults([])
      setAudienceSearchLoading(false)
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(async () => {
      setAudienceSearchLoading(true)
      try {
        const result = await searchEventInviteUsers(query, { token, signal: controller.signal })
        setAudienceUserResults(result.users)
      } catch (searchError) {
        if (searchError?.name !== 'AbortError') setAudienceUserResults([])
      } finally {
        if (!controller.signal.aborted) setAudienceSearchLoading(false)
      }
    }, 300)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [audienceUserQuery, token])

  useEffect(() => {
    if (!isEdit || !eventSlug) {
      return undefined
    }

    const controller = new AbortController()
    let cancelled = false

    async function loadEvent() {
      setLoadingEvent(true)
      setError('')

      try {
        const result = await getEventDetail(eventSlug, {
          token,
          signal: controller.signal,
        })

        if (cancelled) {
          return
        }

        if (!result.ok || !result.data?.event) {
          setError(t('events.edit_page.load_error'))
          return
        }

        const loaded = result.data.event
        if (user && !isEventOwner(user, loaded)) {
          navigate(`/events/${loaded.slug || eventSlug}`, { replace: true })
          return
        }

        const eventGigs = await listEventGigs(loaded.id, { token, signal: controller.signal })
        if (cancelled) {
          return
        }
        const gigRows = eventGigs.map(gigToFormRow)
        gigSnapshotRef.current = snapshotGigRows(gigRows)
        deletedGigIdsRef.current = new Set()

        const formValues = mapEventToFormValues(loaded)
        formValues.gigs = gigRows.length ? gigRows : [{ ...emptyGig }]
        setEventRecordId(loaded.id)
        setV(formValues)
        setShowGigs(gigRows.length > 0)
        setRegistrationDeadlineEnabled(Boolean(formValues.registration_deadline))
        setShowEmailInvites(
          (formValues.participant_invites || []).some((invite) => invite.name || invite.email || invite.user_id),
        )
        setShowAudienceEmailInvites(
          (formValues.audience_invites || []).some((invite) => invite.invite_type === 'external'),
        )
      } catch (loadError) {
        if (loadError?.name === 'AbortError' || cancelled) {
          return
        }
        setError(t('events.edit_page.load_error'))
      } finally {
        if (!cancelled) {
          setLoadingEvent(false)
        }
      }
    }

    loadEvent()
    return () => {
      cancelled = true
      controller.abort()
    }
  }, [eventSlug, isEdit, navigate, t, token, user])

  const hasSavedGigs = v.gigs.some((gig) => Boolean(gig.id))
  const gigsPanelVisible = showGigs || hasSavedGigs
  const isRecurring = v.schedule_mode === 'recurring'
  const isAvailability = v.schedule_mode === 'availability'
  const isInPerson = v.delivery_mode === 'in_person'
  const isOnline = v.delivery_mode === 'online'
  const placeQuery = String(v.venue.place_name || '').trim().toLowerCase()
  const matchingRecentPlaces = placeQuery
    ? recentPlaces
        .filter((place) => [place.place_name, place.city, place.address_line]
          .some((value) => String(value || '').toLowerCase().includes(placeQuery)))
        .slice(0, 6)
    : []
  const latRaw = String(v.venue.latitude ?? '').trim()
  const lonRaw = String(v.venue.longitude ?? '').trim()
  const latNum = latRaw ? Number(latRaw) : NaN
  const lonNum = lonRaw ? Number(lonRaw) : NaN
  const hasCoords = Boolean(latRaw && lonRaw && Number.isFinite(latNum) && Number.isFinite(lonNum))
  const mapCenter = hasCoords ? { lat: latNum, lng: lonNum } : { lat: 41.9028, lng: 12.4964 }
  const eventMarkerIcon = useMemo(
    () =>
      L.divIcon({
        className: styles.mapMarkerIconRoot,
        html: `<span class="${styles.mapMarkerIconSvgWrap}">${createMarkerSvg('var(--color-black)')}</span>`,
      }),
    [],
  )

  useEffect(() => {
    if (!isInPerson) {
      return
    }

    const placeName = String(v.venue.place_name ?? '').trim()
    const addressLine = String(v.venue.address_line ?? '').trim()
    const postalCode = String(v.venue.postal_code ?? '').trim()
    const city = String(v.venue.city ?? '').trim()
    const rawCountry = String(v.venue.country_code ?? '').trim()
    const normalizedCountry = (() => {
      if (!rawCountry) return 'IT'
      const upper = rawCountry.toUpperCase()
      if (upper.length === 2) return upper
      if (rawCountry.toLowerCase() === 'italia') return 'IT'
      if (rawCountry.toLowerCase() === 'italy') return 'IT'
      return 'IT'
    })()

    const hasEnoughLocationInfo = Boolean(city && (addressLine || placeName || postalCode))
    if (!hasEnoughLocationInfo) {
      setGeoStatus((prev) => (prev.loading || prev.error ? { loading: false, error: '' } : prev))
      return
    }

    const queryKey = [placeName, addressLine, postalCode, city, normalizedCountry].join('|')
    if (queryKey === lastGeocodeKeyRef.current) {
      return
    }

    if (geocodeTimerRef.current) {
      window.clearTimeout(geocodeTimerRef.current)
      geocodeTimerRef.current = null
    }

    geocodeTimerRef.current = window.setTimeout(async () => {
      lastGeocodeKeyRef.current = queryKey
      if (geocodeAbortRef.current) {
        geocodeAbortRef.current.abort()
      }
      const controller = new AbortController()
      geocodeAbortRef.current = controller

      setGeoStatus({ loading: true, error: '' })

      try {
        const url = new URL('https://nominatim.openstreetmap.org/search')
        url.searchParams.set('format', 'jsonv2')
        url.searchParams.set('limit', '1')
        url.searchParams.set('addressdetails', '0')
        url.searchParams.set('countrycodes', normalizedCountry.toLowerCase())
        // Prefer structured fields for higher hit rate.
        if (addressLine) url.searchParams.set('street', addressLine)
        if (city) url.searchParams.set('city', city)
        if (postalCode) url.searchParams.set('postalcode', postalCode)
        // If the user provides a venue name, keep it as a fallback hint.
        if (placeName && !addressLine) url.searchParams.set('q', `${placeName}, ${city}`)

        const resp = await fetch(url.toString(), {
          signal: controller.signal,
          headers: {
            Accept: 'application/json',
          },
        })

        if (!resp.ok) {
          throw new Error('Geocoding failed')
        }

        const results = await resp.json()
        const first = Array.isArray(results) ? results[0] : null
        const nextLat = first?.lat ? Number(first.lat) : NaN
        const nextLon = first?.lon ? Number(first.lon) : NaN
        if (!Number.isFinite(nextLat) || !Number.isFinite(nextLon)) {
          setGeoStatus({ loading: false, error: t('events.create_page.ui.geocoding_not_found') })
          return
        }

        setV((prev) => ({
          ...prev,
          venue: {
            ...prev.venue,
            latitude: String(nextLat),
            longitude: String(nextLon),
          },
        }))
        setGeoStatus({ loading: false, error: '' })
      } catch (err) {
        if (err?.name === 'AbortError') {
          return
        }
        setGeoStatus({ loading: false, error: t('events.create_page.ui.geocoding_error') })
      }
    }, 650)

    return () => {
      if (geocodeTimerRef.current) {
        window.clearTimeout(geocodeTimerRef.current)
        geocodeTimerRef.current = null
      }
      if (geocodeAbortRef.current) {
        geocodeAbortRef.current.abort()
        geocodeAbortRef.current = null
      }
    }
  }, [
    isInPerson,
    v.venue.place_name,
    v.venue.address_line,
    v.venue.postal_code,
    v.venue.city,
    v.venue.country_code,
    t,
  ])

  function setField(event) {
    const { name, value, type, checked, files } = event.target
    if (name === 'audience_capacity') {
      setV((prev) => ({
        ...prev,
        audience_capacity: value,
        audience_capacity_enabled: Boolean(String(value).trim()),
      }))
      return
    }

    setV((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : type === 'file' ? (files?.[0] ?? null) : value,
    }))
  }

  function setNested(key, event) {
    const { name, value } = event.target
    setV((prev) => ({ ...prev, [key]: { ...prev[key], [name]: value } }))
  }

  function setRow(key, index, field, value) {
    setV((prev) => {
      const next = [...prev[key]]
      next[index] = { ...next[index], [field]: value }
      return { ...prev, [key]: next }
    })
  }

  function addGigRow() {
    setV((prev) => ({ ...prev, gigs: [...prev.gigs, { ...emptyGig }] }))
  }

  function removeGigRow(index) {
    const row = v.gigs[index]
    if (row?.id) {
      const count = Number(row.applications_count || 0)
      const message = count > 0
        ? t('events.edit_page.gig_delete_confirm_applications', { count })
        : t('events.edit_page.gig_delete_confirm')
      if (!window.confirm(message)) {
        return
      }
      deletedGigIdsRef.current.add(row.id)
    }
    const next = v.gigs.filter((_, i) => i !== index)
    if (next.length === 0) {
      setShowGigs(false)
    }
    setV((prev) => ({ ...prev, gigs: next.length ? next : [{ ...emptyGig }] }))
  }

  function setRecurringMode(checked) {
    setV((prev) => ({
      ...prev,
      schedule_mode: checked ? 'recurring' : 'fixed',
      recurrence: checked && Number(prev.recurrence.occurrences_count) < 2
        ? { ...prev.recurrence, occurrences_count: 2 }
        : prev.recurrence,
    }))
  }

  function setAvailabilityMode(checked) {
    setV((prev) => ({
      ...prev,
      schedule_mode: checked ? 'availability' : 'fixed',
    }))
  }

  function removeAvailabilityOption(index) {
    setV((prev) => {
      if (prev.availability_options.length <= 1) return prev
      return {
        ...prev,
        availability_options: prev.availability_options.filter((_, optionIndex) => optionIndex !== index),
      }
    })
  }

  function parseIdList(text) {
    return String(text || '')
      .split(',')
      .map((item) => Number(item.trim()))
      .filter((item) => Number.isInteger(item) && item > 0)
  }

  function applyRecentPlace(place) {
    if (!place) return
    setV((prev) => ({
      ...prev,
      venue: {
        ...prev.venue,
        place_name: place.place_name ?? '',
        country_code: place.country_code ?? 'IT',
        city: place.city ?? '',
        postal_code: place.postal_code ?? '',
        address_line: place.address_line ?? '',
        latitude: place.latitude ?? '',
        longitude: place.longitude ?? '',
      },
    }))
    setShowPlaceSuggestions(false)
  }

  function normalizeCountryCode(raw) {
    const value = String(raw ?? '').trim()
    if (!value) return 'IT'
    const upper = value.toUpperCase()
    if (upper.length === 2) return upper
    if (value.toLowerCase() === 'italia') return 'IT'
    if (value.toLowerCase() === 'italy') return 'IT'
    return 'IT'
  }

  function selectAudienceUser(selectedUser) {
    setV((prev) => {
      const alreadySelected = prev.audience_invites.some(
        (invite) => invite.invite_type === 'internal' && Number(invite.user_id) === Number(selectedUser.id),
      )
      if (alreadySelected) return prev

      return {
        ...prev,
        audience_invites: [
          ...prev.audience_invites,
          {
            invite_type: 'internal',
            user_id: selectedUser.id,
            name: selectedUser.display_name || selectedUser.nickname,
            nickname: selectedUser.nickname,
            avatar_url: selectedUser.avatar_url,
            email: null,
            status: 'pending',
          },
        ],
      }
    })
    setAudienceUserQuery('')
    setAudienceUserResults([])
  }

  function removeAudienceInvite(index) {
    setV((prev) => ({
      ...prev,
      audience_invites: prev.audience_invites.filter((_, inviteIndex) => inviteIndex !== index),
    }))
  }

  function addAudienceEmailInvite() {
    setV((prev) => ({
      ...prev,
      audience_invites: [
        ...prev.audience_invites,
        { invite_type: 'external', user_id: null, name: '', email: '', status: 'pending' },
      ],
    }))
  }

  const payload = useMemo(
    () => ({
      ...v,
      occurrence: {
        ...v.occurrence,
        ends_at: v.occurrence?.ends_at ? v.occurrence.ends_at : null,
      },
      event_category_id: v.event_category_id || null,
      festival_event_id: v.festival_event_id || null,
      registration_deadline: registrationDeadlineEnabled ? v.registration_deadline || null : null,
      price: v.has_cost ? Number(v.price || 0) : null,
      audience_capacity: v.audience_capacity_enabled ? Number(v.audience_capacity || 0) : null,
      recurrence: isRecurring
        ? {
            ...v.recurrence,
            interval: Number(v.recurrence.interval || 1),
            occurrences_count: Number(v.recurrence.occurrences_count || 1),
          }
        : null,
      availability_poll: isAvailability ? v.availability_poll : null,
      availability_options: isAvailability ? v.availability_options : [],
      venue: isInPerson
        ? {
            ...v.venue,
            country_code: normalizeCountryCode(v.venue.country_code),
            latitude: v.venue.latitude ? Number(v.venue.latitude) : null,
            longitude: v.venue.longitude ? Number(v.venue.longitude) : null,
          }
        : null,
      online: isOnline ? v.online : null,
      group_ids: parseIdList(v.group_ids_text),
      audience_invites: v.audience_invites
        .filter((invite) => invite.invite_type === 'internal' || showAudienceEmailInvites)
        .filter((invite) => invite.invite_type === 'internal'
          ? Boolean(invite.user_id)
          : Boolean(String(invite.name || '').trim() && String(invite.email || '').trim()))
        .map((invite) => ({
          invite_type: invite.invite_type,
          user_id: invite.invite_type === 'internal' ? Number(invite.user_id) : null,
          name: invite.invite_type === 'external' ? invite.name : null,
          email: invite.invite_type === 'external' ? invite.email : null,
          status: invite.status || 'pending',
        })),
      participant_invites: v.participant_invites
        .filter((invite) => {
          if (!invite) return false
          if (!invite.role) return false
          const type = String(invite.invite_type ?? 'external')
          if (type === 'internal') {
            return Boolean(String(invite.user_id ?? '').trim())
          }
          const email = String(invite.email ?? '').trim()
          const name = String(invite.name ?? '').trim()
          return Boolean(email && name)
        })
        .map((invite) => ({
          invite_type: invite.invite_type,
          role: invite.role,
          user_id: invite.invite_type === 'internal' && invite.user_id ? Number(invite.user_id) : null,
          name: invite.invite_type === 'external' ? invite.name : null,
          email: invite.invite_type === 'external' ? invite.email : null,
          status: invite.status || 'pending',
        })),
      gigs: isEdit || !showGigs
        ? []
        : v.gigs.map((gig) => ({
            ...gig,
            ...gigOfferFlags(gig),
            quantity: Number(gig.quantity || 1),
            deadline: gig?.deadline ? gig.deadline : null,
          })),
    }),
    [v, isEdit, isRecurring, isAvailability, isInPerson, isOnline, showGigs, showAudienceEmailInvites, registrationDeadlineEnabled],
  )

  async function submit(event) {
    event.preventDefault()
    setSaving(true)
    setError('')
    const requestedStatus = event.nativeEvent?.submitter?.value
    const hasRequestedStatus = requestedStatus === 'published' || requestedStatus === 'draft'
    const submissionPayload = hasRequestedStatus ? { ...payload, status: requestedStatus } : payload
    if (hasRequestedStatus) {
      setV((prev) => ({ ...prev, status: requestedStatus }))
    }

    try {
      if (isEdit) {
        if (!eventRecordId) {
          throw new Error(t('events.edit_page.save_error'))
        }
        const updated = await updateEvent(eventRecordId, submissionPayload, { token })
        const gigSync = await syncEventGigs({
          eventId: eventRecordId,
          rows: gigsPanelVisible ? v.gigs : [],
          snapshot: gigSnapshotRef.current,
          deletedIds: deletedGigIdsRef.current,
          token,
        })
        if (gigSync.error) {
          if (gigsPanelVisible) {
            setV((prev) => ({ ...prev, gigs: gigSync.rows }))
          }
          setError(t('events.edit_page.gigs_save_error', { message: gigSync.error.message || '' }))
          return
        }
        const nextId = updated?.slug ?? eventSlug
        navigate(nextId ? `/events/${nextId}` : '/events')
        return
      }

      const created = await createEvent(submissionPayload, { token })
      const nextId = created?.slug ?? created?.id
      navigate(nextId ? `/events/${nextId}` : '/events')
    } catch (err) {
      setError(err?.message || (isEdit ? t('events.edit_page.save_error') : 'Errore creazione evento'))
    } finally {
      setSaving(false)
    }
  }

  const submitDisabled = saving || loadingEvent || (isEdit && !eventRecordId)
  const categoryName = categories.find((item) => String(item.id) === String(v.event_category_id))?.name || ''
  const registrationParts = splitDateTimeParts(v.registration_deadline)

  function setRegistrationDate(nextDate) {
    setV((prev) => ({
      ...prev,
      registration_deadline: mergeDateTimeParts(nextDate, splitDateTimeParts(prev.registration_deadline).time),
    }))
  }

  function setRegistrationTime(nextTime) {
    setV((prev) => ({
      ...prev,
      registration_deadline: mergeDateTimeParts(splitDateTimeParts(prev.registration_deadline).date, nextTime),
    }))
  }

  return (
    <form
      className={styles.form}
      onSubmit={submit}
      onInvalid={(event) => {
        const sectionId = event.target.closest?.('[data-accordion-id]')?.dataset?.accordionId
        if (sectionId) toggleSection(sectionId, true)
      }}
    >
      {error ? <p className={styles.error}>{error}</p> : null}
      {loadingEvent ? <p className={styles.error}>{t('events.detail_page.messages.loading')}</p> : null}

      <section className={styles.hero}>
        <h1 className={styles.heroTitle}>{isEdit ? t('events.edit_page.hero_title') : t('events.create_page.hero_title')}</h1>
        <p className={styles.heroLead}>
          {t('events.create_page.summary_hint')}
        </p>
      </section>

      <AccordionSection
        id="basic"
        title={t('events.create_page.summary.basic_title')}
        isOpen={openSections.has('basic')}
        onToggle={toggleSection}
      >
      <section className={styles.sectionBlock}>
        <div className={styles.twoCols}>
          <Field label={`${t('events.create_page.fields.title')} *`}>
            <input
              className={styles.input}
              name="title"
              placeholder={t('events.create_page.placeholders.title')}
              value={v.title}
              onChange={setField}
              required
            />
          </Field>
          <Field label={t('events.create_page.fields.subtitle')} >
            <input
              className={styles.input}
              name="subtitle"
              placeholder={t('events.create_page.placeholders.subtitle')}
              value={v.subtitle}
              onChange={setField}
            />
          </Field>
        </div>

        <div className={styles.twoColsTight}>
          <Field label={`${t('events.create_page.fields.category')} *`}>
            <select className={styles.input} name="event_category_id" value={v.event_category_id} onChange={setField}>
              <option value="">{t('events.create_page.select_placeholder')}</option>
              {categories.map((item) => (
                <option key={item.id} value={item.id}>
                  {item.name}
                </option>
              ))}
            </select>
          </Field>
          <div>
            <div className={styles.fieldLabel}>{t('events.create_page.fields.public_event')} *</div>
            <div className={styles.choiceGroup}>
              <ChoiceButton active={v.is_public} onClick={() => setV((prev) => ({ ...prev, is_public: true }))}>
                {t('events.create_page.visibility.public')}
              </ChoiceButton>
              <ChoiceButton active={!v.is_public} onClick={() => setV((prev) => ({ ...prev, is_public: false }))}>
                {t('events.create_page.visibility.private')}
              </ChoiceButton>
            </div>
          </div>
        </div>

        <Field label={`${t('events.create_page.fields.description')} *`} full>
          <textarea
            className={styles.textarea}
            name="description"
            placeholder={t('events.create_page.placeholders.description')}
            value={v.description}
            onChange={setField}
            required
          />
        </Field>

        <Field label={t('events.create_page.fields.requirements')} full>
          <textarea
            className={styles.textareaSmall}
            name="requirements"
            placeholder={t('events.create_page.placeholders.requirements')}
            value={v.requirements}
            onChange={setField}
          />
        </Field>
      </section>
      </AccordionSection>

      <AccordionSection
        id="schedule"
        title={t('events.create_page.summary.schedule_title')}
        isOpen={openSections.has('schedule')}
        onToggle={toggleSection}
      >
      <section className={styles.sectionBlock}>
        {!isAvailability ? (
          <>
            <div className={styles.twoCols}>
              <DateTimeField
                label={t('events.create_page.ui.starts_at')}
                value={v.occurrence.starts_at}
                onChange={(value) => setV((prev) => ({
                  ...prev,
                  occurrence: { ...prev.occurrence, starts_at: value },
                }))}
                required
                t={t}
              />
              <DateTimeField
                label={t('events.create_page.ui.ends_at').replace(/\s*\*+\s*$/, '')}
                value={v.occurrence.ends_at}
                onChange={(value) => setV((prev) => ({
                  ...prev,
                  occurrence: { ...prev.occurrence, ends_at: value },
                }))}
                t={t}
              />
            </div>

            <div>
              <div className={styles.toggleRow}>
                <span>{t('events.create_page.fields.is_recurring')}</span>
                <Toggle checked={isRecurring} onChange={setRecurringMode} />
              </div>
              <p className={styles.microCopy}>{t('events.create_page.ui.recurrence_hint')}</p>
            </div>

            {isRecurring ? (
              <div className={styles.conditionalPanel}>
                <h3 className={styles.conditionalTitle}>{t('events.create_page.ui.recurrence_settings')}</h3>
                <div className={styles.threeCols}>
                  <Field label={t('events.create_page.fields.recurrence_type')}>
                    <select className={styles.input} name="frequency" value={v.recurrence.frequency} onChange={(event) => setNested('recurrence', event)}>
                      <option value="daily">{t('events.create_page.ui.daily')}</option>
                      <option value="weekly">{t('events.create_page.ui.weekly')}</option>
                      <option value="monthly">{t('events.create_page.ui.monthly')}</option>
                    </select>
                  </Field>
                  <Field label={t('events.create_page.fields.recurrence_interval')}>
                    <input className={styles.input} name="interval" type="number" min="1" value={v.recurrence.interval} onChange={(event) => setNested('recurrence', event)} />
                  </Field>
                  <Field label={t('events.create_page.fields.recurrence_count')}>
                    <input className={styles.input} name="occurrences_count" type="number" min="1" value={v.recurrence.occurrences_count} onChange={(event) => setNested('recurrence', event)} />
                  </Field>
                </div>
              </div>
            ) : null}
          </>
        ) : null}

        <div>
          <div className={styles.toggleRow}>
            <span>{t('events.create_page.ui.availability_toggle')}</span>
            <Toggle checked={isAvailability} onChange={setAvailabilityMode} />
          </div>
          <p className={styles.microCopy}>{t('events.create_page.ui.availability_hint')}</p>
        </div>

        {isAvailability ? (
          <div className={styles.conditionalPanel}>
            <div>
              <h3 className={styles.conditionalTitle}>{t('events.create_page.ui.availability_setup_title')}</h3>
              <p className={styles.microCopy}>{t('events.create_page.ui.availability_setup_hint')}</p>
            </div>

            <div className={styles.twoCols}>
              <DateTimeField
                label={`${t('events.create_page.fields.availability_deadline')} *`}
                value={v.availability_poll.response_deadline}
                onChange={(value) => setV((prev) => ({
                  ...prev,
                  availability_poll: { ...prev.availability_poll, response_deadline: value },
                }))}
                required
                t={t}
              />
              <Field label={t('events.create_page.ui.instructions')}>
                <input className={styles.input} name="instructions" value={v.availability_poll.instructions} onChange={(event) => setNested('availability_poll', event)} placeholder={t('events.create_page.ui.instructions_placeholder')} />
              </Field>
            </div>

            <div className={styles.optionGroupHeader}>
              <div>
                <h3 className={styles.conditionalTitle}>{t('events.create_page.ui.proposed_dates_title')}</h3>
                <p className={styles.microCopy}>{t('events.create_page.ui.proposed_dates_hint')}</p>
              </div>
              <button type="button" className={styles.addInline} onClick={() => setV((prev) => ({ ...prev, availability_options: [...prev.availability_options, { ...emptyAvailability }] }))}>
                <span className={styles.addCircle}>+</span>
                <span>{t('events.create_page.ui.add_options')}</span>
              </button>
            </div>

            {v.availability_options.map((option, index) => (
              <div key={`availability-${index}`} className={styles.optionCard}>
                <div className={styles.optionCardHeader}>
                  <strong>{t('events.create_page.ui.option_n', { n: index + 1 })}</strong>
                  <button
                    type="button"
                    className={styles.removeOption}
                    onClick={() => removeAvailabilityOption(index)}
                    disabled={v.availability_options.length === 1}
                    title={v.availability_options.length === 1 ? t('events.create_page.ui.cannot_remove_last_option') : undefined}
                  >
                    {t('events.create_page.ui.remove_option')}
                  </button>
                </div>
                <div className={styles.twoCols}>
                  <DateTimeField
                    label={`${t('events.create_page.ui.option_datetime')} *`}
                    value={option.starts_at}
                    onChange={(value) => setRow('availability_options', index, 'starts_at', value)}
                    required
                    t={t}
                  />
                  <Field label={t('events.create_page.ui.option_description')}>
                    <input className={styles.input} value={option.description} onChange={(event) => setRow('availability_options', index, 'description', event.target.value)} placeholder={t('events.create_page.ui.option_description_placeholder')} />
                  </Field>
                </div>
              </div>
            ))}
          </div>
        ) : null}

        <div className={styles.choiceGroup}>
          <ChoiceButton active={isInPerson} onClick={() => setV((prev) => ({ ...prev, delivery_mode: 'in_person' }))}>
            {t('events.create_page.delivery_modes.in_person')}
          </ChoiceButton>
          <ChoiceButton active={isOnline} onClick={() => setV((prev) => ({ ...prev, delivery_mode: 'online' }))}>
            {t('events.create_page.delivery_modes.online')}
          </ChoiceButton>
        </div>

        {isInPerson ? (
          <>
            <div className={styles.threeColsPlace}>
              <Field label={t('events.create_page.ui.place_name')}>
                <div className={styles.placeCombobox}>
                  <input
                    className={styles.input}
                    name="place_name"
                    placeholder={t('events.create_page.ui.place_name_placeholder')}
                    value={v.venue.place_name}
                    onChange={(event) => {
                      setNested('venue', event)
                      setShowPlaceSuggestions(true)
                    }}
                    onFocus={() => setShowPlaceSuggestions(true)}
                    onBlur={() => setShowPlaceSuggestions(false)}
                    autoComplete="off"
                    aria-autocomplete="list"
                    aria-expanded={showPlaceSuggestions && matchingRecentPlaces.length > 0}
                    required
                  />
                  {showPlaceSuggestions && matchingRecentPlaces.length > 0 ? (
                    <div className={styles.placeSuggestions} role="listbox">
                      {matchingRecentPlaces.map((place, index) => (
                        <button
                          type="button"
                          role="option"
                          className={styles.placeSuggestion}
                          key={`${place.place_name}-${place.city}-${index}`}
                          onMouseDown={(event) => event.preventDefault()}
                          onClick={() => applyRecentPlace(place)}
                        >
                          <strong>{place.place_name}</strong>
                          <span>{[place.address_line, place.city].filter(Boolean).join(' · ')}</span>
                        </button>
                      ))}
                    </div>
                  ) : null}
                </div>
              </Field>
              <Field label={t('events.create_page.ui.country_required')}>
                <input className={styles.input} name="country_code" placeholder="IT" value={v.venue.country_code} onChange={(event) => setNested('venue', event)} required />
              </Field>
              <Field label={t('events.create_page.ui.city_required')}>
                <input className={styles.input} name="city" placeholder={t('events.create_page.ui.city_placeholder')} value={v.venue.city} onChange={(event) => setNested('venue', event)} required />
              </Field>
            </div>
            <div className={styles.twoCols}>
              <Field label={t('events.create_page.ui.postal_required')}>
                <input className={styles.input} name="postal_code" placeholder={t('events.create_page.fields.postcode')} value={v.venue.postal_code} onChange={(event) => setNested('venue', event)} />
              </Field>
              <Field label={t('events.create_page.ui.address_required')}>
                <input className={styles.input} name="address_line" placeholder={t('events.create_page.fields.venue_address')} value={v.venue.address_line} onChange={(event) => setNested('venue', event)} />
              </Field>
            </div>
            <Field label={t('events.create_page.ui.map')} full>
              <div className={styles.mapPanel}>
                <div className={styles.mapBadge} aria-hidden="true">
                  <EventLocationIcon className={styles.mapBadgeIcon} />
                </div>
                <div className={styles.cleanMap}>
                  <MapContainer
                    center={[mapCenter.lat, mapCenter.lng]}
                    zoom={hasCoords ? 14 : 6}
                    className={styles.mapLeaflet}
                    scrollWheelZoom={false}
                  >
                    <OsmTileLayer />
                    {hasCoords ? <MapAutoCenter lat={mapCenter.lat} lng={mapCenter.lng} zoom={14} /> : null}
                    {hasCoords ? <Marker position={[mapCenter.lat, mapCenter.lng]} icon={eventMarkerIcon} /> : null}
                  </MapContainer>

                  {!hasCoords ? (
                    <div className={styles.mapPlaceholderText}>
                      {geoStatus.loading
                        ? t('events.create_page.ui.geocoding')
                        : geoStatus.error
                          ? geoStatus.error
                          : t('events.create_page.ui.map_need_place')}
                    </div>
                  ) : null}
                </div>
              </div>
            </Field>
            <div className={styles.twoCols}>
              <input className={styles.input} name="latitude" placeholder={t('events.create_page.fields.latitude')} value={v.venue.latitude} onChange={(event) => setNested('venue', event)} />
              <input className={styles.input} name="longitude" placeholder={t('events.create_page.fields.longitude')} value={v.venue.longitude} onChange={(event) => setNested('venue', event)} />
            </div>
          </>
        ) : null}

        {isOnline ? (
          <div className={styles.twoCols}>
            <Field label={t('events.create_page.ui.meeting_link')}>
              <input className={styles.input} name="meeting_url" value={v.online.meeting_url} onChange={(event) => setNested('online', event)} />
            </Field>
            <Field label={t('events.create_page.ui.timezone_required')}>
              <input className={styles.input} name="timezone" value={v.online.timezone} onChange={(event) => setNested('online', event)} />
            </Field>
          </div>
        ) : null}
      </section>
      </AccordionSection>

      <AccordionSection
        id="media"
        title={t('events.create_page.summary.tickets_title')}
        isOpen={openSections.has('media')}
        onToggle={toggleSection}
      >
      <section className={styles.sectionBlock}>
        <div className={styles.twoColsTight}>
          <Field label={t('events.create_page.ui.media')}>
            <div className={styles.mediaBox}>
              <input className={styles.fileInput} name="cover_image" type="file" accept="image/*" onChange={setField} />
            </div>
            <span className={styles.fieldHint}>{t('events.create_page.ui.media_hint')}</span>
          </Field>

          <div>
            <div className={styles.fieldLabel}>{t('events.create_page.ui.tickets')}</div>
            <div className={styles.choiceGroup}>
              <ChoiceButton active={!v.has_cost} onClick={() => setV((prev) => ({ ...prev, has_cost: false }))}>
                {t('events.create_page.ui.free_event')}
              </ChoiceButton>
              <ChoiceButton active={v.has_cost} onClick={() => setV((prev) => ({ ...prev, has_cost: true }))}>
                {t('events.create_page.ui.paid_event')}
              </ChoiceButton>
            </div>
            {v.has_cost ? (
              <div className={styles.twoCols}>
                <input className={styles.input} name="price" placeholder={t('events.create_page.fields.price')} value={v.price} onChange={setField} />
                <select className={styles.input} name="currency" value={v.currency} onChange={setField}>
                  <option value="EUR">{t('events.create_page.ui.currency')}</option>
                  <option value="EUR">EUR</option>
                  <option value="USD">USD</option>
                </select>
              </div>
            ) : null}
          </div>
        </div>

        <Field label={t('events.create_page.ui.linked_to_group')} full>
          <input className={styles.input} name="group_ids_text" placeholder={t('events.create_page.ui.group_placeholder')} value={v.group_ids_text} onChange={setField} />
        </Field>

        <Field label={t('events.create_page.ui.part_of_festival')} full>
          <input className={styles.input} name="festival_event_id" placeholder={t('events.create_page.ui.festival_placeholder')} value={v.festival_event_id} onChange={setField} />
        </Field>
      </section>
      </AccordionSection>

      <AccordionSection
        id="participants"
        title={t('events.create_page.ui.invite_participants')}
        isOpen={openSections.has('participants')}
        onToggle={toggleSection}
      >
      <section className={styles.sectionBlock}>
        <Field label={t('events.create_page.ui.invite_participants')} full>
          <input className={styles.input} placeholder={t('events.create_page.ui.invite_search_placeholder')} readOnly />
        </Field>

        <div>
          <div className={styles.toggleRow}>
            <span>{t('events.create_page.ui.invite_by_email_toggle')}</span>
            <Toggle checked={showEmailInvites} onChange={setShowEmailInvites} />
          </div>
          <p className={styles.microCopy}>{t('events.create_page.ui.invite_by_email_copy')}</p>
        </div>

        {showEmailInvites ? (
          <div className={styles.conditionalPanel}>
            <div className={styles.inviteTitle}>{t('events.create_page.ui.invite_by_email_title')}</div>
            {v.participant_invites.map((invite, index) => (
              <div key={`invite-${index}`} className={styles.inviteRow}>
                <input className={styles.input} placeholder={t('events.create_page.ui.name_optional')} value={invite.name} onChange={(event) => setRow('participant_invites', index, 'name', event.target.value)} />
                <input className={styles.input} placeholder={t('events.create_page.ui.email_required')} value={invite.email} onChange={(event) => setRow('participant_invites', index, 'email', event.target.value)} />
                <select className={styles.input} value={invite.role} onChange={(event) => setRow('participant_invites', index, 'role', event.target.value)}>
                  <option value="artist">{t('events.create_page.ui.role_artist')}</option>
                  <option value="organizer">{t('events.create_page.ui.role_organizer')}</option>
                  <option value="host">{t('events.create_page.ui.role_host')}</option>
                  <option value="technical_support">{t('events.create_page.ui.role_technical_support')}</option>
                </select>
              </div>
            ))}

            <button type="button" className={styles.mailButton} onClick={() => setV((prev) => ({ ...prev, participant_invites: [...prev.participant_invites, { ...emptyInvite }] }))}>
              {t('events.create_page.ui.invite_via_email')}
            </button>
          </div>
        ) : null}

        {hasSavedGigs ? null : (
          <div>
            <div className={styles.toggleRow}>
              <span>{t('events.create_page.ui.add_gigs')}</span>
              <Toggle checked={showGigs} onChange={setShowGigs} />
            </div>
            <p className={styles.microCopy}>{t('events.create_page.ui.add_gigs_hint')}</p>
          </div>
        )}

        {gigsPanelVisible ? (
          <div className={styles.conditionalPanel}>
            <div className={styles.gigHeader}>
              <div className={styles.fieldLabel}>{t('events.create_page.ui.add_gigs')}</div>
            </div>
            {v.gigs.map((gig, index) => (
              <div key={gig.id ? `gig-${gig.id}` : `gig-new-${index}`} className={styles.gigCard}>
                <div className={styles.gigTopRow}>
                  <div className={styles.gigRowTitle}>
                    {t('events.create_page.ui.gig_n', { n: index + 1 })}
                    {gig.id ? (
                      <Link to={`/gigs/${gig.id}`} className={styles.gigManageLink}>
                        {t('events.edit_page.gig_manage')}
                      </Link>
                    ) : null}
                  </div>
                  <div className={styles.gigRowActions}>
                    <button type="button" className={styles.bigPlus} onClick={addGigRow} aria-label={t('events.create_page.ui.add_gig')}>
                      +
                    </button>
                    <button
                      type="button"
                      className={styles.removeCircle}
                      onClick={() => removeGigRow(index)}
                      disabled={v.gigs.length === 1 && !gig.id}
                      aria-label={t('events.create_page.ui.remove_gig')}
                      title={v.gigs.length === 1 && !gig.id ? t('events.create_page.ui.cannot_remove_last') : t('events.create_page.ui.remove_gig')}
                    >
                      ×
                    </button>
                  </div>
                </div>

            <div className={styles.gigFields}>
              <select
                className={styles.input}
                value={gig.type}
                onChange={(event) => setRow('gigs', index, 'type', event.target.value)}
              >
                <option value="poet_artist">{t('events.create_page.ui.gig_type_poet')}</option>
                <option value="mc_host">{t('events.create_page.ui.gig_type_host')}</option>
                <option value="technical_support">{t('events.create_page.ui.gig_type_tech')}</option>
                <option value="volunteer">{t('events.create_page.ui.gig_type_volunteer')}</option>
              </select>

              <input
                className={styles.input}
                type="number"
                min="1"
                placeholder={t('events.create_page.ui.quantity')}
                value={gig.quantity}
                onChange={(event) => setRow('gigs', index, 'quantity', event.target.value)}
              />

              <select
                className={styles.input}
                value={gig.status}
                onChange={(event) => setRow('gigs', index, 'status', event.target.value)}
              >
                <option value="draft">{t('events.create_page.ui.status_draft')}</option>
                <option value="open">{t('events.create_page.ui.status_open')}</option>
                <option value="closed">{t('events.create_page.ui.status_closed')}</option>
                <option value="cancelled">{t('events.create_page.ui.status_cancelled')}</option>
              </select>

              <input
                className={styles.input}
                placeholder={t('events.create_page.ui.language')}
                value={gig.language}
                onChange={(event) => setRow('gigs', index, 'language', event.target.value)}
              />
            </div>

            <p className={styles.microCopy}>{t('events.create_page.ui.gig_offers_hint')}</p>

            <div className={styles.offerBox}>
              <span className={styles.checkInline}>{t('events.create_page.fields.gig_cachet')}</span>
              <div className={styles.twoCols}>
                <input
                  className={styles.input}
                  placeholder={t('events.create_page.ui.amount')}
                  value={gig.proposed_price}
                  onChange={(event) => setRow('gigs', index, 'proposed_price', event.target.value)}
                />
                <select
                  className={styles.input}
                  value={gig.currency}
                  onChange={(event) => setRow('gigs', index, 'currency', event.target.value)}
                >
                  <option value="EUR">EUR (€)</option>
                  <option value="USD">USD ($)</option>
                </select>
              </div>
            </div>

            <div className={styles.offerBox}>
              <span className={styles.checkInline}>{t('events.create_page.ui.travel_expenses')}</span>
              <div className={styles.twoCols}>
                <input
                  className={styles.input}
                  placeholder={t('events.create_page.ui.max_coverage')}
                  value={gig.travel_expenses_max}
                  onChange={(event) => setRow('gigs', index, 'travel_expenses_max', event.target.value)}
                />
                <select
                  className={styles.input}
                  value={gig.currency}
                  onChange={(event) => setRow('gigs', index, 'currency', event.target.value)}
                >
                  <option value="EUR">EUR (€)</option>
                  <option value="USD">USD ($)</option>
                </select>
              </div>
            </div>

            <div className={styles.offerBox}>
              <span className={styles.checkInline}>{t('events.create_page.ui.food_and_lodging')}</span>
              <textarea
                className={styles.textareaInline}
                placeholder={t('events.create_page.ui.lodging_details')}
                value={gig.accommodation_details}
                onChange={(event) => setRow('gigs', index, 'accommodation_details', event.target.value)}
              />
            </div>
              </div>
            ))}
          </div>
        ) : null}

        <div className={styles.toggleRow}>
          <span>{t('events.create_page.ui.registration_deadline')}</span>
          <Toggle
            checked={registrationDeadlineEnabled}
            onChange={(checked) => {
              setRegistrationDeadlineEnabled(checked)
              if (checked) {
                setV((prev) => ({
                  ...prev,
                  registration_deadline: prev.registration_deadline
                    || prev.occurrence.starts_at
                    || prev.availability_poll.response_deadline
                    || currentLocalDateTime(),
                }))
              }
            }}
          />
        </div>
        <p className={styles.microCopy}>{t('events.create_page.ui.registration_deadline_hint')}</p>

        {registrationDeadlineEnabled ? (
          <div className={styles.twoCols}>
            <input className={styles.input} type="date" value={registrationParts.date} onChange={(event) => setRegistrationDate(event.target.value)} />
            <input className={styles.input} type="time" value={registrationParts.time} onChange={(event) => setRegistrationTime(event.target.value)} placeholder={t('events.create_page.ui.time')} />
          </div>
        ) : null}

        <Field label={t('events.create_page.ui.invite_audience')} full>
          <div className={styles.userCombobox}>
            <input
              className={styles.input}
              value={audienceUserQuery}
              onChange={(event) => setAudienceUserQuery(event.target.value)}
              placeholder={t('events.create_page.ui.audience_search_placeholder')}
              autoComplete="off"
            />
            {audienceUserQuery.trim().length >= 2 ? (
              <div className={styles.userSuggestions}>
                {audienceSearchLoading ? (
                  <div className={styles.userSuggestionStatus}>{t('events.create_page.ui.searching_users')}</div>
                ) : audienceUserResults.length ? (
                  audienceUserResults.map((result) => (
                    <button
                      type="button"
                      key={result.id}
                      className={styles.userSuggestion}
                      onClick={() => selectAudienceUser(result)}
                    >
                      <span>{result.display_name || result.nickname}</span>
                      {result.display_name && result.nickname ? <small>@{result.nickname}</small> : null}
                    </button>
                  ))
                ) : (
                  <div className={styles.userSuggestionStatus}>{t('events.create_page.ui.no_users_found')}</div>
                )}
              </div>
            ) : null}
          </div>
        </Field>

        {v.audience_invites.some((invite) => invite.invite_type === 'internal') ? (
          <div className={styles.selectedUsers}>
            {v.audience_invites.map((invite, index) => invite.invite_type === 'internal' ? (
              <div className={styles.selectedUser} key={`audience-user-${invite.user_id}`}>
                <span>{invite.name || invite.nickname || `#${invite.user_id}`}</span>
                <button type="button" onClick={() => removeAudienceInvite(index)} aria-label={t('events.create_page.ui.remove_invitee')}>×</button>
              </div>
            ) : null)}
          </div>
        ) : null}

        <div>
          <div className={styles.toggleRow}>
            <span>{t('events.create_page.ui.invite_audience_by_email')}</span>
            <Toggle checked={showAudienceEmailInvites} onChange={setShowAudienceEmailInvites} />
          </div>
          <p className={styles.microCopy}>{t('events.create_page.ui.invite_audience_by_email_copy')}</p>
        </div>

        {showAudienceEmailInvites ? (
          <div className={styles.conditionalPanel}>
            {v.audience_invites.map((invite, index) => invite.invite_type === 'external' ? (
              <div key={`audience-email-${index}`} className={styles.inviteRowWithRemove}>
                <input className={styles.input} placeholder={t('events.create_page.ui.name_required')} value={invite.name || ''} onChange={(event) => setRow('audience_invites', index, 'name', event.target.value)} />
                <input className={styles.input} placeholder={t('events.create_page.ui.email_required')} value={invite.email || ''} onChange={(event) => setRow('audience_invites', index, 'email', event.target.value)} />
                <button type="button" className={styles.removeCircle} onClick={() => removeAudienceInvite(index)} aria-label={t('events.create_page.ui.remove_invitee')}>×</button>
              </div>
            ) : null)}
            <button type="button" className={styles.mailButton} onClick={addAudienceEmailInvite}>
              {t('events.create_page.ui.add_audience_email')}
            </button>
          </div>
        ) : null}

        <Field label={t('events.create_page.ui.capacity_limit')} full>
          <input className={styles.input} type="number" min="1" name="audience_capacity" value={v.audience_capacity} onChange={setField} />
        </Field>

        <div className={styles.fieldLabel}>{t('events.create_page.ui.publication_status')}</div>
        <PublishActions status={v.status} saving={saving} disabled={submitDisabled} t={t} />
      </section>
      </AccordionSection>

      <AccordionSection
        id="confirmation"
        title={t('events.create_page.summary_title')}
        isOpen={openSections.has('confirmation')}
        onToggle={toggleSection}
      >
      <section className={styles.confirmSection}>
        <h2 className={styles.confirmTitle}>{t('events.create_page.summary_title')}</h2>
        <p className={styles.confirmSub}>{t('events.create_page.summary_hint')}</p>

        <SummaryCard
          title={t('events.create_page.summary.basic_title')}
          items={[
            { label: t('events.create_page.fields.title'), value: v.title },
            { label: t('events.create_page.fields.category'), value: categoryName },
            { label: t('events.create_page.fields.public_event'), value: v.is_public ? t('events.create_page.visibility.public') : t('events.create_page.visibility.private') },
          ]}
        />
        <SummaryCard
          title={t('events.create_page.summary.schedule_title')}
          items={[
            { label: t('events.create_page.fields.start_datetime'), value: v.occurrence.starts_at },
            { label: t('events.create_page.fields.end_datetime'), value: v.occurrence.ends_at },
            { label: t('events.create_page.summary.delivery_mode'), value: isInPerson ? t('events.create_page.delivery_modes.in_person') : t('events.create_page.delivery_modes.online') },
          ]}
        />
        <SummaryCard
          title={t('events.create_page.summary.tickets_title')}
          items={[
            { label: t('events.create_page.summary.tickets_type'), value: v.has_cost ? t('events.create_page.summary.paid') : t('events.create_page.free') },
            { label: t('events.create_page.fields.price'), value: v.has_cost ? `${v.price || '-'} ${v.currency}` : '-' },
          ]}
        />
        <SummaryCard
          title={t('events.create_page.ui.recurrence')}
          items={[
            { label: t('events.create_page.fields.recurrence_type'), value: isRecurring ? v.recurrence.frequency : '-' },
            { label: t('events.create_page.ui.every'), value: isRecurring ? String(v.recurrence.interval) : '-' },
          ]}
        />
        <SummaryCard
          title={t('events.create_page.ui.availability_options')}
          items={v.availability_options.slice(0, 2).map((item, index) => ({
            label: t('events.create_page.ui.option_n', { n: index + 1 }),
            value: item.starts_at || '-',
          }))}
        />
        <SummaryCard
          title={t('events.create_page.ui.settings')}
          items={[
            { label: t('events.create_page.ui.max_participants'), value: v.audience_capacity },
            { label: t('events.create_page.fields.status'), value: v.status === 'published' ? t('events.create_page.ui.status_active') : t('events.create_page.ui.status_draft') },
          ]}
        />

        <div className={styles.readyBox}>
          <div className={styles.readyTitle}>{t('events.create_page.ui.ready_title')}</div>
          <div className={styles.readyText}>{t('events.create_page.ui.ready_text')}</div>
        </div>

        <PublishActions status={v.status} saving={saving} disabled={submitDisabled} t={t} />
      </section>
      </AccordionSection>
    </form>
  )
}
