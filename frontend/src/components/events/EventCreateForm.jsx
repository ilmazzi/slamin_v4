import { useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import L from 'leaflet'
import { MapContainer, Marker, useMap } from 'react-leaflet'
import OsmTileLayer from '../../app/maps/OsmTileLayer.jsx'
import { createEvent, getEventDetail, listEventCategories, listRecentEventPlaces, updateEvent } from '../../app/api/events.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { mapEventToFormValues } from '../../app/utils/eventForm.js'
import { isEventOwner } from '../../app/utils/eventDetail.js'
import { emptyAvailability, emptyGig, emptyInvite } from '../../app/utils/eventFormDefaults.js'
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

function Field({ label, children, full = false, hint = '' }) {
  return (
    <label className={`${styles.field} ${full ? styles.full : ''}`}>
      <span className={styles.fieldLabel}>{label}</span>
      {hint ? <span className={styles.fieldHint}>{hint}</span> : null}
      {children}
    </label>
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

export default function EventCreateForm({ eventSlug = null }) {
  const { token, user } = useAuth()
  const navigate = useNavigate()
  const { t } = useI18n()
  const isEdit = Boolean(eventSlug)
  const [eventRecordId, setEventRecordId] = useState(null)
  const [categories, setCategories] = useState([])
  const [recentPlaces, setRecentPlaces] = useState([])
  const [error, setError] = useState('')
  const [saving, setSaving] = useState(false)
  const [loadingEvent, setLoadingEvent] = useState(isEdit)
  const [geoStatus, setGeoStatus] = useState({ loading: false, error: '' })
  const lastGeocodeKeyRef = useRef('')
  const geocodeTimerRef = useRef(null)
  const geocodeAbortRef = useRef(null)
  const [v, setV] = useState({
    title: '',
    subtitle: '',
    description: '',
    requirements: '',
    event_category_id: '',
    festival_event_id: '',
    schedule_mode: 'fixed',
    delivery_mode: 'in_person',
    has_cost: true,
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
    occurrence: { starts_at: '', ends_at: '', timezone: 'Europe/Rome' },
    recurrence: { frequency: 'weekly', interval: 1, occurrences_count: 1 },
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
    audience_invite_user_ids_text: '',
    participant_invites: [emptyInvite],
    gigs: [emptyGig],
    cover_image: null,
  })

  useEffect(() => {
    listEventCategories().then((r) => setCategories(r.categories)).catch(() => {})
  }, [])

  useEffect(() => {
    if (!token) return
    listRecentEventPlaces({ token }).then((r) => setRecentPlaces(r.places)).catch(() => {})
  }, [token])

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

        setEventRecordId(loaded.id)
        setV(mapEventToFormValues(loaded))
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

  const isRecurring = v.schedule_mode === 'recurring'
  const isAvailability = v.schedule_mode === 'availability'
  const isInPerson = v.delivery_mode === 'in_person'
  const isOnline = v.delivery_mode === 'online'
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
    setV((prev) => {
      const next = prev.gigs.filter((_, i) => i !== index)
      return { ...prev, gigs: next.length ? next : [{ ...emptyGig }] }
    })
  }

  function parseIdList(text) {
    return String(text || '')
      .split(',')
      .map((item) => Number(item.trim()))
      .filter((item) => Number.isInteger(item) && item > 0)
  }

  function applyRecentPlace(raw) {
    if (!raw) return
    const place = JSON.parse(raw)
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

  const payload = useMemo(
    () => ({
      ...v,
      occurrence: {
        ...v.occurrence,
        ends_at: v.occurrence?.ends_at ? v.occurrence.ends_at : null,
      },
      event_category_id: v.event_category_id || null,
      festival_event_id: v.festival_event_id || null,
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
      audience_invite_user_ids: parseIdList(v.audience_invite_user_ids_text),
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
      gigs: isEdit
        ? []
        : v.gigs.map((gig) => ({
            ...gig,
            quantity: Number(gig.quantity || 1),
            deadline: gig?.deadline ? gig.deadline : null,
          })),
    }),
    [v, isEdit, isRecurring, isAvailability, isInPerson, isOnline],
  )

  async function submit(event) {
    event.preventDefault()
    setSaving(true)
    setError('')

    try {
      if (isEdit) {
        if (!eventRecordId) {
          throw new Error(t('events.edit_page.save_error'))
        }
        const updated = await updateEvent(eventRecordId, payload, { token })
        const nextId = updated?.slug ?? eventSlug
        navigate(nextId ? `/events/${nextId}` : '/events')
        return
      }

      const created = await createEvent(payload, { token })
      const nextId = created?.slug ?? created?.id
      navigate(nextId ? `/events/${nextId}` : '/events')
    } catch (err) {
      setError(err?.message || (isEdit ? t('events.edit_page.save_error') : 'Errore creazione evento'))
    } finally {
      setSaving(false)
    }
  }

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
    <form className={styles.form} onSubmit={submit}>
      {error ? <p className={styles.error}>{error}</p> : null}
      {loadingEvent ? <p className={styles.error}>{t('events.detail_page.messages.loading')}</p> : null}

      <section className={styles.hero}>
        <h1 className={styles.heroTitle}>{isEdit ? t('events.edit_page.hero_title') : t('events.create_page.hero_title')}</h1>
        <p className={styles.heroLead}>
          {t('events.create_page.summary_hint')}
        </p>
      </section>

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

        <Field label={`${t('events.create_page.fields.requirements')} *`} full>
          <textarea
            className={styles.textareaSmall}
            name="requirements"
            placeholder={t('events.create_page.placeholders.requirements')}
            value={v.requirements}
            onChange={setField}
          />
        </Field>
      </section>

      <div className={styles.separator} />
      <section className={styles.sectionBlock}>
        <div className={styles.twoCols}>
          <Field label={t('events.create_page.ui.starts_at')}>
            <input className={styles.input} type="datetime-local" name="starts_at" value={v.occurrence.starts_at} onChange={(event) => setNested('occurrence', event)} required />
          </Field>
          <Field label={t('events.create_page.ui.ends_at')}>
            <input className={styles.input} type="datetime-local" name="ends_at" value={v.occurrence.ends_at} onChange={(event) => setNested('occurrence', event)} />
          </Field>
        </div>

        <div className={styles.toggleRow}>
          <span>{t('events.create_page.fields.is_recurring')}</span>
          <Toggle checked={isRecurring} onChange={(checked) => setV((prev) => ({ ...prev, schedule_mode: checked ? 'recurring' : 'fixed' }))} />
        </div>

        <div className={styles.threeCols}>
          <select className={styles.input} name="frequency" value={v.recurrence.frequency} onChange={(event) => setNested('recurrence', event)} disabled={!isRecurring}>
            <option value="daily">{t('events.create_page.ui.recurrence_frequency_placeholder')}</option>
            <option value="daily">{t('events.create_page.ui.daily')}</option>
            <option value="weekly">{t('events.create_page.ui.weekly')}</option>
            <option value="monthly">{t('events.create_page.ui.monthly')}</option>
          </select>
          <input className={styles.input} name="interval" type="number" min="1" value={v.recurrence.interval} onChange={(event) => setNested('recurrence', event)} disabled={!isRecurring} placeholder={t('events.create_page.ui.interval_placeholder')} />
          <input className={styles.input} name="occurrences_count" type="number" min="1" value={v.recurrence.occurrences_count} onChange={(event) => setNested('recurrence', event)} disabled={!isRecurring} placeholder={t('events.create_page.ui.occurrences_placeholder')} />
        </div>

        <div className={styles.toggleRow}>
          <span>{t('events.create_page.fields.is_availability_based')}</span>
          <Toggle checked={isAvailability} onChange={(checked) => setV((prev) => ({ ...prev, schedule_mode: checked ? 'availability' : 'fixed' }))} />
        </div>
        <p className={styles.microCopy}>{t('events.create_page.ui.availability_hint')}</p>

        <div className={styles.twoCols}>
          <Field label={t('events.create_page.fields.availability_deadline')}>
            <input className={styles.input} type="datetime-local" name="response_deadline" value={v.availability_poll.response_deadline} onChange={(event) => setNested('availability_poll', event)} disabled={!isAvailability} />
          </Field>
          <Field label={t('events.create_page.ui.instructions')}>
            <input className={styles.input} name="instructions" value={v.availability_poll.instructions} onChange={(event) => setNested('availability_poll', event)} disabled={!isAvailability} placeholder={t('events.create_page.ui.instructions_placeholder')} />
          </Field>
        </div>

        <div className={styles.availabilityHeader}>
          <button type="button" className={styles.addInline} onClick={() => setV((prev) => ({ ...prev, availability_options: [...prev.availability_options, { ...emptyAvailability }] }))}>
            <span className={styles.addCircle}>+</span>
            <span>{t('events.create_page.ui.add_options')}</span>
          </button>
        </div>

        {v.availability_options.map((option, index) => (
          <div key={`availability-${index}`} className={styles.twoCols}>
            <input className={styles.input} type="datetime-local" value={option.starts_at} onChange={(event) => setRow('availability_options', index, 'starts_at', event.target.value)} disabled={!isAvailability} />
            <input className={styles.input} value={option.description} onChange={(event) => setRow('availability_options', index, 'description', event.target.value)} disabled={!isAvailability} placeholder={t('events.create_page.ui.option_description_placeholder')} />
          </div>
        ))}

        <div className={styles.twoColsTight}>
          <div className={styles.choiceGroup}>
            <ChoiceButton active={isInPerson} onClick={() => setV((prev) => ({ ...prev, delivery_mode: 'in_person' }))}>
              {t('events.create_page.delivery_modes.in_person')}
            </ChoiceButton>
            <ChoiceButton active={isOnline} onClick={() => setV((prev) => ({ ...prev, delivery_mode: 'online' }))}>
              {t('events.create_page.delivery_modes.online')}
            </ChoiceButton>
          </div>
          <Field label={t('events.create_page.ui.recent_places')}>
            <select className={styles.input} onChange={(event) => applyRecentPlace(event.target.value)} defaultValue="">
              <option value="">{t('events.create_page.ui.select_recent_place')}</option>
              {recentPlaces.map((place, index) => (
                <option key={`${place.place_name}-${index}`} value={JSON.stringify(place)}>
                  {[place.place_name, place.city].filter(Boolean).join(' - ')}
                </option>
              ))}
            </select>
          </Field>
        </div>

        {isInPerson ? (
          <>
            <div className={styles.threeColsPlace}>
              <Field label={t('events.create_page.ui.place_name')}>
                <input className={styles.input} name="place_name" placeholder={t('events.create_page.ui.place_name_placeholder')} value={v.venue.place_name} onChange={(event) => setNested('venue', event)} required />
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

      <div className={styles.separator} />
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
              <ChoiceButton active={v.has_cost} onClick={() => setV((prev) => ({ ...prev, has_cost: true }))}>
                {t('events.create_page.ui.paid_event')}
              </ChoiceButton>
              <ChoiceButton active={!v.has_cost} onClick={() => setV((prev) => ({ ...prev, has_cost: false }))}>
                {t('events.create_page.ui.free_event')}
              </ChoiceButton>
            </div>
            <div className={styles.twoCols}>
              <input className={styles.input} name="price" placeholder={t('events.create_page.fields.price')} value={v.price} onChange={setField} disabled={!v.has_cost} />
              <select className={styles.input} name="currency" value={v.currency} onChange={setField} disabled={!v.has_cost}>
                <option value="EUR">{t('events.create_page.ui.currency')}</option>
                <option value="EUR">EUR</option>
                <option value="USD">USD</option>
              </select>
            </div>
          </div>
        </div>

        <Field label={t('events.create_page.ui.linked_to_group')} full>
          <input className={styles.input} name="group_ids_text" placeholder={t('events.create_page.ui.group_placeholder')} value={v.group_ids_text} onChange={setField} />
        </Field>

        <Field label={t('events.create_page.ui.part_of_festival')} full>
          <input className={styles.input} name="festival_event_id" placeholder={t('events.create_page.ui.festival_placeholder')} value={v.festival_event_id} onChange={setField} />
        </Field>
      </section>

      <div className={styles.separator} />
      <section className={styles.sectionBlock}>
        <Field label={t('events.create_page.ui.invite_participants')} full>
          <input className={styles.input} placeholder={t('events.create_page.ui.invite_search_placeholder')} readOnly />
        </Field>

        <div className={styles.inviteTitle}>{t('events.create_page.ui.invite_by_email_title')}</div>
        <p className={styles.microCopy}>{t('events.create_page.ui.invite_by_email_copy')}</p>

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

        {isEdit ? null : (
          <>
        <div className={styles.gigHeader}>
          <div className={styles.fieldLabel}>{t('events.create_page.ui.add_gigs')}</div>
        </div>
        {v.gigs.map((gig, index) => (
          <div key={`gig-${index}`} className={styles.gigCard}>
            <div className={styles.gigTopRow}>
              <div className={styles.gigRowTitle}>{t('events.create_page.ui.gig_n', { n: index + 1 })}</div>
              <div className={styles.gigRowActions}>
                <button type="button" className={styles.bigPlus} onClick={addGigRow} aria-label={t('events.create_page.ui.add_gig')}>
                  +
                </button>
                <button
                  type="button"
                  className={styles.removeCircle}
                  onClick={() => removeGigRow(index)}
                  disabled={v.gigs.length === 1}
                  aria-label={t('events.create_page.ui.remove_gig')}
                  title={v.gigs.length === 1 ? t('events.create_page.ui.cannot_remove_last') : t('events.create_page.ui.remove_gig')}
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

            <div className={styles.offerBox}>
              <label className={styles.checkInline}>
                <input
                  type="checkbox"
                  checked={Boolean(gig.cachet_enabled)}
                  onChange={(event) => setRow('gigs', index, 'cachet_enabled', event.target.checked)}
                />
                <span>{t('events.create_page.fields.gig_cachet')}</span>
              </label>
              <div className={styles.twoCols}>
                <input
                  className={styles.input}
                  placeholder={t('events.create_page.ui.amount')}
                  value={gig.proposed_price}
                  onChange={(event) => setRow('gigs', index, 'proposed_price', event.target.value)}
                  disabled={!gig.cachet_enabled}
                />
                <select
                  className={styles.input}
                  value={gig.currency}
                  onChange={(event) => setRow('gigs', index, 'currency', event.target.value)}
                  disabled={!gig.cachet_enabled}
                >
                  <option value="EUR">EUR (€)</option>
                  <option value="USD">USD ($)</option>
                </select>
              </div>
            </div>

            <div className={styles.offerBox}>
              <label className={styles.checkInline}>
                <input
                  type="checkbox"
                  checked={Boolean(gig.travel_expenses_enabled)}
                  onChange={(event) => setRow('gigs', index, 'travel_expenses_enabled', event.target.checked)}
                />
                <span>{t('events.create_page.ui.travel_expenses')}</span>
              </label>
              <div className={styles.twoCols}>
                <input
                  className={styles.input}
                  placeholder={t('events.create_page.ui.max_coverage')}
                  value={gig.travel_expenses_max}
                  onChange={(event) => setRow('gigs', index, 'travel_expenses_max', event.target.value)}
                  disabled={!gig.travel_expenses_enabled}
                />
                <select
                  className={styles.input}
                  value={gig.currency}
                  onChange={(event) => setRow('gigs', index, 'currency', event.target.value)}
                  disabled={!gig.travel_expenses_enabled}
                >
                  <option value="EUR">EUR (€)</option>
                  <option value="USD">USD ($)</option>
                </select>
              </div>
            </div>

            <div className={styles.offerBox}>
              <label className={styles.checkInline}>
                <input
                  type="checkbox"
                  checked={Boolean(gig.accommodation_enabled)}
                  onChange={(event) => {
                    setRow('gigs', index, 'accommodation_enabled', event.target.checked)
                    setRow('gigs', index, 'hospitality_required', event.target.checked)
                  }}
                />
                <span>{t('events.create_page.ui.food_and_lodging')}</span>
              </label>
              <textarea
                className={styles.textareaInline}
                placeholder={t('events.create_page.ui.lodging_details')}
                value={gig.accommodation_details}
                onChange={(event) => setRow('gigs', index, 'accommodation_details', event.target.value)}
                disabled={!gig.accommodation_enabled}
              />
            </div>
          </div>
        ))}
          </>
        )}

        <div className={styles.toggleRow}>
          <span>{t('events.create_page.ui.registration_deadline')}</span>
          <Toggle checked={Boolean(v.registration_deadline)} onChange={(checked) => setV((prev) => ({ ...prev, registration_deadline: checked ? prev.registration_deadline || prev.occurrence.starts_at : '' }))} />
        </div>
        <p className={styles.microCopy}>{t('events.create_page.ui.registration_deadline_hint')}</p>

        <div className={styles.twoCols}>
          <input className={styles.input} type="date" value={registrationParts.date} onChange={(event) => setRegistrationDate(event.target.value)} />
          <input className={styles.input} type="time" value={registrationParts.time} onChange={(event) => setRegistrationTime(event.target.value)} placeholder={t('events.create_page.ui.time')} />
        </div>

        <Field label={t('events.create_page.ui.invite_audience')} full>
          <input className={styles.input} name="audience_capacity" placeholder={t('events.create_page.ui.capacity_limit')} value={v.audience_capacity} onChange={setField} />
        </Field>

        <div className={styles.fieldLabel}>{t('events.create_page.ui.publication_status')}</div>
        <div className={styles.publishRow}>
          <ChoiceButton active={v.status === 'published'} onClick={() => setV((prev) => ({ ...prev, status: 'published' }))}>
            <span className={styles.choiceTitle}>{t('events.create_page.ui.publish_now')}</span>
            <span className={styles.choiceSub}>{t('events.create_page.ui.publish_now_sub')}</span>
          </ChoiceButton>
          <ChoiceButton active={v.status === 'draft'} onClick={() => setV((prev) => ({ ...prev, status: 'draft' }))}>
            <span className={styles.choiceTitle}>{t('events.create_page.save_draft')}</span>
            <span className={styles.choiceSub}>{t('events.create_page.ui.save_draft_sub')}</span>
          </ChoiceButton>
        </div>
      </section>

      <div className={styles.separator} />
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

        <button className={styles.submit} type="submit" disabled={saving || loadingEvent || (isEdit && !eventRecordId)}>
          {saving
            ? (isEdit ? t('events.edit_page.saving') : t('events.create_page.saving'))
            : (isEdit ? t('events.edit_page.save') : t('events.create_page.publish'))}
        </button>
      </section>
    </form>
  )
}
