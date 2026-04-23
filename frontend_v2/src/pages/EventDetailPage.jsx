import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import L from 'leaflet'
import { MapContainer, Marker, TileLayer, useMapEvents } from 'react-leaflet'
import { useParams } from 'react-router-dom'
import { authFetch } from '../app/api/authFetch.js'
import { resolvePublicUrl } from '../app/api/url.js'
import MainLayout from '../components/layout/MainLayout.jsx'
import PillButton from '../components/shared/PillButton.jsx'
import HomeDashboardPromo from '../components/home/HomeDashboardPromo.jsx'
import {
  ArticleIcon,
  CalendarIcon,
  CommunityFriendsIcon,
  EventLocationIcon,
} from '../components/icons/ActionIcons.jsx'
import styles from './EventDetailPage.module.css'
import UpcomingEventSlider from '../components/events/UpcomingEventSlider.jsx'


function isValidNumber(value) {
  return typeof value === 'number' && Number.isFinite(value)
}

function normalizeString(value) {
  const text = String(value ?? '').trim()
  return text !== '' ? text : null
}

function formatDateTime(value) {
  if (!value) {
    return null
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }

  return new Intl.DateTimeFormat('it-IT', {
    dateStyle: 'long',
    timeStyle: 'short',
  }).format(date)
}

function formatDate(value) {
  if (!value) {
    return null
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }

  return new Intl.DateTimeFormat('it-IT', {
    dateStyle: 'long',
  }).format(date)
}

function formatTime(value) {
  if (!value) {
    return null
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }

  return new Intl.DateTimeFormat('it-IT', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function formatMoney(value) {
  if (value === null || value === undefined || value === '') {
    return null
  }

  const numericValue = Number(value)
  if (!Number.isFinite(numericValue)) {
    return null
  }

  return new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR',
  }).format(numericValue)
}

function formatValue(value) {
  if (value === null || value === undefined || value === '') {
    return '-'
  }

  if (Array.isArray(value)) {
    const values = value
      .map((item) => normalizeString(item))
      .filter(Boolean)
      .join(', ')
    return values || '-'
  }

  if (typeof value === 'boolean') {
    return value ? 'Si' : 'No'
  }

  return String(value)
}

const DEFAULT_CENTER = { lat: 41.9028, lng: 12.4964 }
const createMarkerSvg = (fill) => `
<svg viewBox="0 0 38 54" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <path d="M0 0V40.589H17.1945L37.9553 54V40.589V13.4055V0H0Z" fill="${fill}"/>
</svg>
`

function DetailSectionIcon({ type, className }) {
  if (type === 'where') {
    return <EventLocationIcon className={className} />
  }

  if (type === 'participation') {
    return <CommunityFriendsIcon className={className} />
  }

  if (type === 'useful') {
    return <ArticleIcon className={className} />
  }

  return <CalendarIcon className={className} />
}

function MapWheelZoomGuard() {
  const wheelUnlockTimerRef = useRef(null)
  const wheelZoomActiveRef = useRef(false)

  const clearWheelUnlockTimer = useCallback(() => {
    if (wheelUnlockTimerRef.current) {
      window.clearTimeout(wheelUnlockTimerRef.current)
      wheelUnlockTimerRef.current = null
    }
  }, [])

  const map = useMapEvents({
    click() {
      keepWheelTemporarilyEnabled()
    },
    mousedown() {
      keepWheelTemporarilyEnabled()
    },
    touchstart() {
      keepWheelTemporarilyEnabled()
    },
    dragstart() {
      keepWheelTemporarilyEnabled()
    },
    drag() {
      keepWheelTemporarilyEnabled()
    },
    dragend() {
      keepWheelTemporarilyEnabled()
    },
  })

  const disableWheelZoom = useCallback(() => {
    clearWheelUnlockTimer()
    wheelZoomActiveRef.current = false
    map.scrollWheelZoom.disable()
  }, [clearWheelUnlockTimer, map])

  const keepWheelTemporarilyEnabled = useCallback(() => {
    wheelZoomActiveRef.current = true
    map.scrollWheelZoom.enable()
    clearWheelUnlockTimer()
    wheelUnlockTimerRef.current = window.setTimeout(() => {
      wheelZoomActiveRef.current = false
      map.scrollWheelZoom.disable()
      wheelUnlockTimerRef.current = null
    }, 1400)
  }, [clearWheelUnlockTimer, map])

  useEffect(() => {
    const container = map.getContainer()
    const handleWheel = () => {
      // Refresh lock only if the user already activated map-wheel mode explicitly.
      if (!wheelZoomActiveRef.current) {
        return
      }
      keepWheelTemporarilyEnabled()
    }

    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        disableWheelZoom()
      }
    }

    disableWheelZoom()
    container.addEventListener('wheel', handleWheel, { passive: true })
    container.addEventListener('mouseleave', disableWheelZoom)
    window.addEventListener('blur', disableWheelZoom)
    window.addEventListener('keydown', handleKeyDown)

    return () => {
      clearWheelUnlockTimer()
      container.removeEventListener('wheel', handleWheel)
      container.removeEventListener('mouseleave', disableWheelZoom)
      window.removeEventListener('blur', disableWheelZoom)
      window.removeEventListener('keydown', handleKeyDown)
    }
  }, [clearWheelUnlockTimer, disableWheelZoom, keepWheelTemporarilyEnabled, map])

  return null
}

function EventDetailContent({ t, locale, token, user, isAuthenticated }) {
  const [dashboardWidgets, setDashboardWidgets] = useState([])
  const { eventId } = useParams()
  const tr = useCallback(
    (key, fallback) => {
      const translated = t(key)
      return translated === key ? fallback : translated
    },
    [t],
  )
  const [viewState, setViewState] = useState({
    status: 'loading',
    message: '',
    data: null,
  })

  const dashboardMetrics = ['poems', 'events', 'views', 'likes'].map((id) => {
    const item = dashboardWidgets.find((widget) => widget.id === id)
    const value = item?.value ?? 0

    return {
      id,
      value: typeof value === 'number' ? new Intl.NumberFormat(locale).format(value) : String(value),
      label: t(`home.dashboard.${id}`),
    }
  })

  useEffect(() => {
    const controller = new AbortController()
    let cancelled = false

    async function loadEventDetail() {
      const slug = String(eventId ?? '').trim()
      if (!slug) {
        if (!cancelled) {
          setViewState({
            status: 'invalid',
            message: tr('events.detail_page.messages.invalid_identifier', 'Invalid event identifier.'),
            data: null,
          })
        }
        return
      }

      try {
        const response = await authFetch(`/api/v1/events/${encodeURIComponent(slug)}`, {
          token,
          signal: controller.signal,
        })
        let payload = null

        try {
          payload = await response.json()
        } catch {
          payload = null
        }

        if (cancelled) {
          return
        }

        if (response.ok) {
          setViewState({
            status: 'ready',
            message: '',
            data: payload?.data ?? null,
          })
          return
        }

        const fallbackMessage = tr('events.detail_page.messages.unable_to_load', 'Unable to load event details.')
        const message = typeof payload?.message === 'string' ? payload.message : fallbackMessage

        if (response.status === 401) {
          setViewState({
            status: 'unauthorized',
            message,
            data: null,
          })
          return
        }

        if (response.status === 404) {
          setViewState({
            status: 'not_found',
            message,
            data: null,
          })
          return
        }

        setViewState({
          status: 'error',
          message,
          data: null,
        })
      } catch (error) {
        if (error?.name === 'AbortError' || cancelled) {
          return
        }

        setViewState({
          status: 'error',
          message: tr('events.detail_page.messages.unable_to_load', 'Unable to load event details.'),
          data: null,
        })
      }
    }

    loadEventDetail()

    return () => {
      cancelled = true
      controller.abort()
    }
  }, [eventId, token, tr])

  useEffect(() => {
    const controller = new AbortController()

    if (!isAuthenticated || !user || !token) {
      return () => controller.abort()
    }

    async function loadDashboardWidgets() {
      try {
        const response = await authFetch(`/api/v1/me/dashboard-widgets?locale=${encodeURIComponent(locale)}`, {
          token,
          signal: controller.signal,
        })

        if (!response.ok) {
          return
        }

        const payload = await response.json()
        const nextWidgets = payload?.data?.widgets
        if (Array.isArray(nextWidgets)) {
          setDashboardWidgets(nextWidgets)
        }
      } catch (error) {
        if (error?.name !== 'AbortError') {
          console.error('Unable to load dashboard widgets on event detail page:', error)
        }
      }
    }

    loadDashboardWidgets()
    return () => controller.abort()
  }, [isAuthenticated, locale, token, user])

  const event = viewState.data?.event ?? null
  const computed = viewState.data?.computed ?? null

  const locationLines = useMemo(() => {
    if (!event) {
      return []
    }

    return [event.city, event.venue_name, event.venue_address]
      .map((item) => normalizeString(item))
      .filter(Boolean)
  }, [event])

  const hasMapCoordinates = useMemo(() => {
    if (!event) {
      return false
    }

    return isValidNumber(Number(event.latitude)) && isValidNumber(Number(event.longitude))
  }, [event])

  const mapCenter = useMemo(() => {
    if (hasMapCoordinates) {
      return {
        lat: Number(event.latitude),
        lng: Number(event.longitude),
      }
    }

    return DEFAULT_CENTER
  }, [event, hasMapCoordinates])

  const eventMarkerIcon = useMemo(() => {
    return L.divIcon({
      className: styles.mapMarkerIconRoot,
      html: `<span class="${styles.mapMarkerIconSvgWrap}">${createMarkerSvg('#000000')}</span>`,
      iconSize: [38, 54],
      iconAnchor: [38, 54],
    })
  }, [])

  const metaItems = useMemo(() => {
    if (!event) {
      return []
    }

    return [
      { label: tr('events.detail_page.meta.type', 'Type'), value: computed?.category_display_name ?? event.category },
      { label: tr('events.detail_page.meta.date', 'Date'), value: formatDate(event.start_datetime) },
      { label: tr('events.detail_page.meta.cost', 'Cost'), value: formatMoney(event.entry_fee) },
      { label: tr('events.detail_page.meta.time', 'Time'), value: formatTime(event.start_datetime) },
    ]
  }, [computed, event, tr])

  const detailSections = useMemo(() => {
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
      Boolean(event.is_recurring) ||
      hasValue(event.recurrence_type) ||
      hasValue(event.recurrence_count) ||
      hasValue(event.recurrence_interval) ||
      hasValue(event.recurrence_weekdays) ||
      hasValue(event.recurrence_monthday)

    const sections = [
      {
        id: 'when',
        icon: 'when',
        title: tr('events.detail_page.sections.when.title', 'When'),
        description: tr('events.detail_page.sections.when.description', 'Date, times and useful deadlines'),
        items: [
          { label: tr('events.detail_page.sections.when.event_date', 'Event date'), value: formatDate(event.start_datetime) },
          { label: tr('events.detail_page.sections.when.start_time', 'Start time'), value: formatTime(event.start_datetime) },
          { label: tr('events.detail_page.sections.when.end_time', 'End time'), value: formatTime(event.end_datetime) },
          { label: tr('events.detail_page.sections.when.estimated_duration', 'Estimated duration'), value: hasValue(computed?.duration_hours) ? `${computed.duration_hours} h` : null },
          { label: tr('events.detail_page.sections.when.registration_closes', 'Registration closes'), value: formatDateTime(event.registration_deadline) },
          { label: tr('events.detail_page.sections.when.invites_close', 'Invites close'), value: formatDateTime(event.invitation_deadline) },
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
          { label: tr('events.detail_page.sections.participation.cost', 'Cost'), value: formatMoney(event.entry_fee) },
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
  }, [computed, event, tr])

  return (
    <main className={styles.page}>
      <section className={styles.detailContainer}>
        {viewState.status === 'loading' ? <p className={styles.stateMessage}>{tr('events.detail_page.messages.loading', 'Loading event details...')}</p> : null}
        {viewState.status === 'invalid' ? <p className={styles.stateMessage}>{viewState.message}</p> : null}
        {viewState.status === 'unauthorized' ? <p className={styles.stateMessage}>{viewState.message}</p> : null}
        {viewState.status === 'not_found' ? <p className={styles.stateMessage}>{viewState.message}</p> : null}
        {viewState.status === 'error' ? <p className={styles.stateMessage}>{viewState.message}</p> : null}

        {viewState.status === 'ready' && event ? (
          <section className={styles.frame}>
            <div className={styles.top}>
              <div className={styles.posterWrap}>
                {event.image_url ? (
                  <img src={event.image_url} alt={event.title || tr('events.poster_alt', 'Event poster')} className={styles.poster} />
                ) : (
                  <div className={styles.posterFallback}>{tr('events.detail_page.messages.no_poster', 'No poster')}</div>
                )}
              </div>

              <div className={styles.topContent}>
                <div>
                  <h1 className={styles.title}>{event.title || `${tr('events.event', 'event')} ${eventId}`}</h1>
                  <p className={styles.description}>{event.description || tr('events.detail_page.messages.no_description', 'No description available.')}</p>
                </div>

                <PillButton
                  label={event.is_online && event.online_url ? tr('events.detail_page.actions.join_online', 'Join online') : tr('events.book', 'Book')}
                  href={event.is_online && event.online_url ? event.online_url : undefined}
                  className={styles.bookButton}
                />
              </div>
            </div>

            <div className={styles.middle}>
              <div className={styles.mapWrap}>
                <MapContainer
                  center={[mapCenter.lat, mapCenter.lng]}
                  zoom={hasMapCoordinates ? 11 : 6}
                  className={styles.mapLeaflet}
                  attributionControl={false}
                  scrollWheelZoom={false}
                >
                  <TileLayer url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png" />
                  <MapWheelZoomGuard />
                  {hasMapCoordinates ? (
                    <Marker position={[mapCenter.lat, mapCenter.lng]} icon={eventMarkerIcon} />
                  ) : null}
                </MapContainer>
              </div>

              <div className={styles.locationBlock}>
                <span className={styles.locationIconWrap} aria-hidden="true">
                  <EventLocationIcon className={styles.locationIcon} />
                </span>
                <div className={styles.locationText}>
                  <p className={styles.locationHeading}>{tr('events.city', 'City')}</p>
                  {locationLines.length > 0 ? (
                    locationLines.map((line) => (
                      <p key={line} className={styles.locationLine}>
                        {line}
                      </p>
                    ))
                  ) : (
                    <p className={styles.locationLine}>{tr('events.detail_page.messages.location_unavailable', 'Location information not available')}</p>
                  )}
                </div>
              </div>
            </div>

            <div className={styles.metaGrid}>
              {metaItems.map((item) => (
                <article key={item.label} className={styles.metaCard}>
                  <p className={styles.metaLabel}>{item.label}</p>
                  <p className={styles.metaValue}>{formatValue(item.value)}</p>
                </article>
              ))}
            </div>

            <section className={styles.extraSection}>
              <div className={styles.extraHeader}>
                <h2 className={styles.extraTitle}>{tr('events.detail_page.details_title', 'Useful information')}</h2>
              </div>

              <div className={styles.extraSectionsFlow}>
                {detailSections.map((section) => (
                  <section key={section.id} className={styles.detailSectionBlock}>
                    <header className={styles.detailSectionHead}>
                      <span className={styles.detailSectionIconWrap} aria-hidden="true">
                        <DetailSectionIcon type={section.icon} className={styles.detailSectionIcon} />
                      </span>

                      <div className={styles.detailSectionIntro}>
                        <h3 className={styles.detailSectionTitle}>{section.title}</h3>
                        <p className={styles.detailSectionDescription}>{section.description}</p>
                      </div>
                    </header>

                    <dl className={styles.detailSectionBody}>
                      {section.items.map((item) => (
                        <div key={`${section.id}-${item.label}`} className={styles.detailFact}>
                          <dt className={styles.detailFactLabel}>{item.label}</dt>
                          <dd className={styles.detailFactValue}>{formatValue(item.value)}</dd>
                        </div>
                      ))}
                    </dl>
                  </section>
                ))}
              </div>
            </section>
          </section>
        ) : null}
      </section>

      <section className={styles.upcomingSection}>
        <UpcomingEventSlider />
      </section>

      {isAuthenticated && user ? (
        <section className={styles.dashboardSection}>
          <HomeDashboardPromo
            title={t('home.dashboard.title')}
            userName={user?.display_name || user?.name || t('home.dashboard.profile_fallback')}
            avatarSrc={resolvePublicUrl(user?.profile_photo_url ?? user?.profile_photo)}
            metrics={dashboardMetrics}
          />
        </section>
      ) : null}

    </main>
  )
}

export default function EventDetailPage() {
  return (
    <MainLayout themeTone="event">
      {({ t, locale, token, user, isAuthenticated }) => (
        <EventDetailContent
          t={t}
          locale={locale}
          token={token}
          user={user}
          isAuthenticated={isAuthenticated}
        />
      )}
    </MainLayout>
  )
}
