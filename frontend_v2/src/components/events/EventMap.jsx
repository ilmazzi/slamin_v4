import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import L from 'leaflet'
import { MapContainer, Marker, TileLayer, useMapEvents } from 'react-leaflet'
import {
  listAllPublicEvents,
} from '../../app/api/events.js'
import { normalizeEventsForUi } from '../../app/normalizers/event.js'
import {
  DEFAULT_EVENT_MAP_CENTER,
  computeCenterFromEvents,
  matchesClientFilters,
} from '../../app/utils/event.js'
import styles from './EventMap.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { EventLocationIcon } from '../icons/ActionIcons.jsx'

const DEFAULT_CENTER = DEFAULT_EVENT_MAP_CENTER

const DESKTOP_MARKER = {
  normal: { w: 38, h: 54 },
  selected: { w: 75, h: 108 },
}

const MOBILE_MARKER = {
  normal: { w: 15, h: 21 },
  selected: { w: 30, h: 43 },
}

const createMarkerSvg = (fill) => `
<svg viewBox="0 0 38 54" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <path d="M0 0V40.589H17.1945L37.9553 54V40.589V13.4055V0H0Z" fill="${fill}"/>
</svg>
`
const createSelectedMarkerSvg = (fill) => `
<svg viewBox="0 0 75 108" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <path d="M0 0V81.1779H33.9366L74.9119 108V81.1779V26.8109V0H0Z" fill="${fill}"/>
</svg>
`
const escapeHtml = (value) =>
    String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;')
      const buildMarkerIcon = ({ selected, isMobile, details }) => {
        const fill = selected
          ? 'var(--page-hero-accent, var(--color-event))'
          : 'var(--color-black)'

        if (!selected) {
          const palette = isMobile ? MOBILE_MARKER : DESKTOP_MARKER
          const size = palette.normal

          return L.divIcon({
            className: styles.markerIconRoot,
            html: `<span class="${styles.markerSvgWrap}" style="width:${size.w}px;height:${size.h}px;">${createMarkerSvg(fill)}</span>`,
            iconSize: [size.w, size.h],
            iconAnchor: [size.w, size.h], // punta
            popupAnchor: [-Math.round(size.w * 0.45), -size.h],
          })
        }

        // marker selezionato: forma unica grande con proporzioni corrette (75:108)
        const expanded = isMobile
          ? { w: 90, h: 130, contentW: 62 }
          : { w: 150, h: 216, contentW: 104 }

        const title = escapeHtml(details?.title || 'Evento')
        const city = escapeHtml(details?.city || '')
        const date = escapeHtml(details?.datetime || '')

        return L.divIcon({
          className: styles.markerIconRoot,
          html: `
            <span class="${styles.markerExpanded}" style="width:${expanded.w}px;height:${expanded.h}px;">
              <span class="${styles.markerSelectedShape}">
                ${createSelectedMarkerSvg(fill)}
              </span>
              <span class="${styles.markerSelectedContent}" style="max-width:${expanded.contentW}px;">
                <strong class="${styles.markerCardTitle}">${title}</strong>
                ${city ? `<span class="${styles.markerCardMeta}">${city}</span>` : ''}
                ${date ? `<span class="${styles.markerCardMeta}">${date}</span>` : ''}
              </span>
            </span>
          `,
          iconSize: [expanded.w, expanded.h],
          iconAnchor: [expanded.w, expanded.h], // punta in basso a destra
        })
      }

function MapClickReset({ onReset }) {
  useMapEvents({
    click(event) {
      onReset(event)
    },
  })

  return null
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
export default function EventMap() {
  const { t } = useI18n()
  /** Elenco normalizzato (tutte le pagine API), filtrato in `events` lato client. */
  const [allEvents, setAllEvents] = useState([])
  const [center, setCenter] = useState(DEFAULT_CENTER)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [selectedId, setSelectedId] = useState(null)
  const [selectedType, setSelectedType] = useState('')
  const [searchQuery, setSearchQuery] = useState('')
  const [cityQuery, setCityQuery] = useState('')
  const [freeOnly, setFreeOnly] = useState(false)
  const [hoveredId, setHoveredId] = useState(null)
  const [isDetailOpen, setIsDetailOpen] = useState(false)
  const [isMobile, setIsMobile] = useState(
    typeof window !== 'undefined' ? window.innerWidth <= 768 : false,
  )

  const mapRef = useRef(null)
  const mapFrameRef = useRef(null)
  const detailSectionRef = useRef(null)
  const skipNextMapResetRef = useRef(false)


  useEffect(() => {
    const onResize = () => setIsMobile(window.innerWidth <= 768)
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [])

  useEffect(() => {
    const controller = new AbortController()

    async function loadMapEvents() {
      setIsLoading(true)
      setError('')

      try {
        const raw = await listAllPublicEvents({ signal: controller.signal })
        const normalized = normalizeEventsForUi(raw)
        setAllEvents(normalized)
        setCenter(computeCenterFromEvents(normalized))
      } catch (err) {
        if (err?.name !== 'AbortError') {
          setError('Impossibile caricare la mappa eventi.')
          setAllEvents([])
          setCenter(DEFAULT_CENTER)
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      }
    }

    loadMapEvents()

    return () => controller.abort()
  }, [])

  const events = useMemo(
    () =>
      allEvents.filter((e) =>
        matchesClientFilters(e, { searchQuery, cityQuery, freeOnly }),
      ),
    [allEvents, searchQuery, cityQuery, freeOnly],
  )

  const mapEvents = useMemo(() => {
    return events
      .filter((event) => {
        const lat = Number(event?.latitude)
        const lng = Number(event?.longitude)
        return Number.isFinite(lat) && Number.isFinite(lng)
      })
      .filter((event) => {
        if (!selectedType) {
          return true
        }
        return String(event?.category || '') === selectedType
      })
  }, [events, selectedType])


  const selectedEvent = useMemo(
    () => mapEvents.find((event) => event.id === selectedId) ?? null,
    [mapEvents, selectedId],
  )
  const formatEventDate = (value) => {
    if (!value) return '—'
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('it-IT')
  }

  const formatEventTime = (value) => {
    if (!value) return '—'
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
  }

  const formatEventCost = (value) => {
    if (value == null || Number(value) <= 0) return t('events.map.free_only')
    return `${Number(value).toFixed(2)} €`
  }


  const typeOptions = useMemo(() => {
    return Array.from(
      new Set(allEvents.map((e) => String(e?.category || '').trim()).filter(Boolean)),
    ).sort((a, b) => a.localeCompare(b))
  }, [allEvents])

  useEffect(() => {
    if (!mapRef.current) return
    if (selectedId !== null) return

    mapRef.current.setView([center.lat, center.lng], mapRef.current.getZoom(), { animate: false })
  }, [center, selectedId])



  const handleMarkerClick = (eventItem) => {
    const lat = Number(eventItem.latitude)
    const lng = Number(eventItem.longitude)

    setHoveredId(null)
    setSelectedId(eventItem.id)
    setIsDetailOpen(false)

    if (mapRef.current && Number.isFinite(lat) && Number.isFinite(lng)) {
        mapRef.current.panTo([lat, lng], { animate: true, duration: 0.45 })
    }

    // Ensure the map overlay card (anchored at map top) is visible.
    window.setTimeout(() => {
      const frame = mapFrameRef.current
      if (!frame) {
        return
      }
      const rect = frame.getBoundingClientRect()
      const safeTop = isMobile ? 72 : 96
      if (rect.top < safeTop) {
        const delta = rect.top - safeTop - 8
        window.scrollBy({ top: delta, behavior: 'smooth' })
      }
    }, 120)
  }

  const handleResetSelection = () => {
    if (skipNextMapResetRef.current) {
      skipNextMapResetRef.current = false
      return
    }
    setHoveredId(null)
    setSelectedId(null)
    setIsDetailOpen(false)
  }

  const handleViewDetails = () => {
    if (selectedId === null) {
      return
    }
    setIsDetailOpen(true)
    setHoveredId(null)
    setTimeout(() => {
      detailSectionRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }, 0)
  }

  const formatDateTime = (value) => {
    if (!value) return ''
    const d = new Date(value)
    if (Number.isNaN(d.getTime())) return ''
    return d.toLocaleString('it-IT', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  return (
    <section className={styles.mapSection}>
      <div className={styles.mapFrame} ref={mapFrameRef}>
        {isLoading ? <div className={styles.stateOverlay}>Caricamento mappa...</div> : null}
        {error ? <div className={styles.stateOverlay}>{error}</div> : null}

        <MapContainer
          center={[center.lat, center.lng]}
          zoom={6}
          scrollWheelZoom={false}
          className={styles.mapCanvas}
          attributionControl={false}
          whenReady={(event) => {
            mapRef.current = event.target
          }}
        >
          <TileLayer url="https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png" />

          <MapClickReset onReset={handleResetSelection} />
          <MapWheelZoomGuard />

          {mapEvents.map((eventItem) => {
           const isSelected = selectedId === eventItem.id
           const isHovered = hoveredId === eventItem.id
           const isExpanded = isHovered
           const lat = Number(eventItem.latitude)
           const lng = Number(eventItem.longitude)
           const markerDate = formatDateTime(eventItem.start_datetime)

           return (
             <Marker
             zIndexOffset={isSelected ? 5200 : isHovered ? 4800 : 0}
               key={eventItem.id}
               position={[lat, lng]}
               icon={buildMarkerIcon({
                 selected: isExpanded,
                 isMobile,
                 details: {
                   title: eventItem.title || 'Evento',
                   city: eventItem.city || eventItem.venue_name || '',
                   datetime: markerDate,
                 },
               })}
               eventHandlers={{
                 mouseover: () => {
                   if (!isMobile) {
                     setHoveredId(eventItem.id)
                   }
                 },
                 mouseout: () => {
                   if (!isMobile) {
                     setHoveredId((current) => (current === eventItem.id ? null : current))
                   }
                 },
                click: (event) => {
                  event?.originalEvent?.stopPropagation?.()
                  skipNextMapResetRef.current = true
                  handleMarkerClick(eventItem)
                },
               }}
             />
           )
          })}
        </MapContainer>

        {selectedEvent && !isDetailOpen ? (
          <div className={styles.mapOverlayCard}>
            <div className={styles.mapOverlayContent}>
              <strong className={styles.mapOverlayTitle}>{selectedEvent.title || t('events.event')}</strong>
              <span className={styles.mapOverlayCity}>{selectedEvent.city || selectedEvent.venue_name || '—'}</span>
            </div>
            <button type="button" className={styles.mapOverlayButton} onClick={handleViewDetails}>
              {t('events.map.view_details')}
            </button>
          </div>
        ) : null}

      </div>
      <div className={styles.filtersBar}>
  <input
    className={styles.filterInputSearch}
    type="text"
    placeholder={t('events.map.search_placeholder')}
    value={searchQuery}
    onChange={(e) => setSearchQuery(e.target.value)}
  />

  <div className={styles.filtersRight}>
    <input
      className={styles.filterInput}
      type="text"
      placeholder={t('events.map.city_placeholder')}
      value={cityQuery}
      onChange={(e) => setCityQuery(e.target.value)}
    />

    <select
      className={styles.filterSelect}
      value={selectedType}
      onChange={(e) => setSelectedType(e.target.value)}
    >
      <option value="">{t('events.map.types_placeholder')}</option>
      {typeOptions.map((type) => (
        <option key={type} value={type}>
          {type}
        </option>
      ))}
    </select>

    <label className={styles.filterToggle}>
      <input
        className={styles.filterToggleInput}
        type="checkbox"
        checked={freeOnly}
        onChange={(e) => setFreeOnly(e.target.checked)}
      />
      <span className={styles.filterToggleLabel}>{t('events.map.free_only')}</span>
    </label>
  </div>
</div>
{selectedEvent && isDetailOpen ? (
  <section className={styles.eventDetailSection} ref={detailSectionRef}>
    <div className={styles.eventDetailMain}>
      <div className={styles.eventDetailTop}>
        <div className={styles.eventPoster}>
          {selectedEvent.image_url ? (
            <img
              src={selectedEvent.image_url}
              alt={selectedEvent.title || t('events.poster_alt')}
              className={styles.eventPosterImage}
            />
          ) : (
            <div className={styles.eventPosterFallback}>{t('events.poster_alt')}</div>
          )}
        </div>

        <div className={styles.eventInfo}>
          <div className={styles.eventTitleBlock}>
            <h3 className={styles.eventTitle}>{selectedEvent.title || t('events.event')}</h3>
            <p className={styles.eventDescription}>{selectedEvent.description || t('events.event_description')}</p>
          </div>

          <div className={styles.eventLocationBlock}>
            <div className={styles.eventLocationText}>
              <strong>{t('events.city')}</strong>
              <span>{selectedEvent.city || '—'}</span>
              <span>{selectedEvent.venue_name || t('events.venue')}</span>
              <span>{selectedEvent.venue_address || t('events.address')}</span>
            </div>
            <EventLocationIcon className={styles.eventLocationIcon} />
          </div>
        </div>
      </div>

      <div className={styles.eventDetailBottom}>
        <div className={styles.eventDetailPolygon} />
      </div>
    </div>

    <aside className={styles.eventDetailSide}>
      <div className={styles.eventMetaList}>
        <p><strong>{t('events.type')}</strong><span>{selectedEvent.category || '—'}</span></p>
        <p><strong>{t('events.date')}</strong><span>{formatEventDate(selectedEvent.start_datetime)}</span></p>
        <p><strong>{t('events.cost')}</strong><span>{formatEventCost(selectedEvent.entry_fee)}</span></p>
        <p><strong>{t('events.time')}</strong><span>{formatEventTime(selectedEvent.start_datetime)}</span></p>
      </div>

      <button type="button" className={styles.eventBookButton}>
        <span>{t('events.book')}</span>
        <span className={styles.eventBookPlus}>+</span>
      </button>
    </aside>
  </section>
) : null}
    </section>
  )
}
