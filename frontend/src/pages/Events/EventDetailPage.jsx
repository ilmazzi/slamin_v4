import { lazy, Suspense, useCallback, useEffect, useMemo, useRef, useState } from 'react'
import L from 'leaflet'
import { MapContainer, Marker, useMapEvents } from 'react-leaflet'
import OsmTileLayer from '../../app/maps/OsmTileLayer.jsx'
import { useLocation, useParams } from 'react-router-dom'
import { formatLocaleNumber } from '../../app/formatters/number.js'
import { useDashboardWidgets } from '../../app/hooks/useDashboardWidgets.js'
import { getEventDetail } from '../../app/api/events.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import {
  buildEventDetailSections,
  buildEventLocationLines,
  buildEventMetaItems,
  flattenEventForDetail,
  formatDisplayValue,
  hasEventCoordinates,
  isEventOwner,
} from '../../app/utils/eventDetail.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PillButton from '../../components/shared/PillButton.jsx'
import SocialActions from '../../components/shared/SocialActions.jsx'
import CommentsThread from '../../components/shared/CommentsThread.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { createEventReport } from '../../app/api/reports.js'
import { scheduleScrollToComments } from '../../app/utils/scrollToComments.js'
import HomeDashboardPromo from '../../components/home/HomeDashboardPromo.jsx'
import EventConfirmedPeople from '../../components/gigs/EventConfirmedPeople.jsx'
import EventOrganizer from '../../components/events/EventOrganizer.jsx'
import EventGigsHighlight from '../../components/gigs/EventGigsHighlight.jsx'
import {
  ArticleIcon,
  CalendarIcon,
  CommunityFriendsIcon,
  EventLocationIcon,
} from '../../components/icons/ActionIcons.jsx'
import styles from './EventDetailPage.module.css'

const UpcomingEventSlider = lazy(() => import('../../components/events/UpcomingEventSlider.jsx'))


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
  const { eventId } = useParams()
  const location = useLocation()
  const commentsRef = useRef(null)
  const [viewState, setViewState] = useState({
    status: 'loading',
    message: '',
    data: null,
  })
  const invalidMessage = t('events.detail_page.messages.invalid_identifier')
  const fallbackMessage = t('events.detail_page.messages.unable_to_load')

  useEffect(() => {
    const controller = new AbortController()
    let cancelled = false

    async function loadEvent() {
      const safeSlug = String(eventId ?? '').trim()
      if (!safeSlug) {
        if (!cancelled) {
          setViewState({
            status: 'invalid',
            message: invalidMessage,
            data: null,
          })
        }
        return
      }

      setViewState({
        status: 'loading',
        message: '',
        data: null,
      })

      try {
        const result = await getEventDetail(safeSlug, {
          token,
          signal: controller.signal,
        })

        if (cancelled) {
          return
        }

        if (result.ok) {
          setViewState({
            status: 'ready',
            message: '',
            data: result.data ?? null,
          })
          return
        }

        const message =
          typeof result.payload?.message === 'string'
            ? result.payload.message
            : typeof result.payload?.errors?.message === 'string'
              ? result.payload.errors.message
              : fallbackMessage

        if (result.status === 401) {
          setViewState({ status: 'unauthorized', message, data: null })
          return
        }

        if (result.status === 404) {
          setViewState({ status: 'not_found', message, data: null })
          return
        }

        setViewState({ status: 'error', message, data: null })
      } catch (error) {
        if (error?.name === 'AbortError' || cancelled) {
          return
        }

        setViewState({
          status: 'error',
          message: fallbackMessage,
          data: null,
        })
      }
    }

    loadEvent()
    return () => {
      cancelled = true
      controller.abort()
    }
  }, [eventId, fallbackMessage, invalidMessage, token])

  const { widgets: dashboardWidgets } = useDashboardWidgets({
    token,
    locale,
    enabled: isAuthenticated && Boolean(user) && Boolean(token),
  })

  const dashboardMetrics = ['poems', 'events', 'views', 'likes'].map((id) => {
    const item = dashboardWidgets.find((widget) => widget.id === id)
    const value = item?.value ?? 0

    return {
      id,
      value: typeof value === 'number' ? formatLocaleNumber(value, locale) : String(value),
      label: t(`home.dashboard.${id}`),
    }
  })

  const event = useMemo(
    () => flattenEventForDetail(viewState.data?.event ?? null),
    [viewState.data?.event],
  )

  useEffect(() => {
    if (location.hash !== '#comments' && !location.state?.focusComments) {
      return
    }
    if (!event?.id || !commentsRef.current) {
      return
    }

    const cancel = scheduleScrollToComments({ behavior: 'smooth' })

    return cancel
  }, [location.hash, location.state, event?.id])

  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    contentType: 'event',
    knownItems: event ? [event] : [],
    shareCopiedMessage: t('events.ui.share_copied'),
    shareSentMessage: t('events.ui.share_sent'),
    shareFailedMessage: t('events.ui.share_failed'),
    reportSubmittedMessage: t('events.ui.report_submitted'),
    reportFailedMessage: t('common.no_results'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id ?? event?.id ?? null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createEventReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })
  const coverImageUrl = resolvePublicUrl(event?.cover_image_url || event?.cover_image_path || event?.image_url || '')

  const locationLines = useMemo(() => {
    return buildEventLocationLines(event)
  }, [event])

  const hasMapCoordinates = useMemo(() => {
    return hasEventCoordinates(event)
  }, [event])

  const mapCenter = useMemo(() => {
    if (hasMapCoordinates) {
      const latValue = event?.latitude ?? event?.venue?.latitude
      const lngValue = event?.longitude ?? event?.venue?.longitude
      return {
        lat: Number(latValue),
        lng: Number(lngValue),
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
    return buildEventMetaItems({ event, locale, t })
  }, [event, locale, t])

  const detailSections = useMemo(() => {
    return buildEventDetailSections({ event, locale, t })
  }, [event, locale, t])

  const isOwner = useMemo(() => isEventOwner(user, event), [event, user])

  return (
    <main className={styles.page}>
      <section className={styles.detailContainer}>
        {viewState.status === 'loading' ? <p className={styles.stateMessage}>{t('events.detail_page.messages.loading')}</p> : null}
        {viewState.status === 'invalid' ? <p className={styles.stateMessage}>{viewState.message}</p> : null}
        {viewState.status === 'unauthorized' ? <p className={styles.stateMessage}>{viewState.message}</p> : null}
        {viewState.status === 'not_found' ? <p className={styles.stateMessage}>{viewState.message}</p> : null}
        {viewState.status === 'error' ? <p className={styles.stateMessage}>{viewState.message}</p> : null}

        {viewState.status === 'ready' && event ? (
          <section className={styles.frame}>
            <div className={styles.top}>
              <div className={styles.posterWrap}>
                {coverImageUrl ? (
                  <img src={coverImageUrl} alt={event.title || t('events.poster_alt')} className={styles.poster} />
                ) : (
                  <div className={styles.posterFallback}>{t('events.detail_page.messages.no_poster')}</div>
                )}
              </div>

              <div className={styles.topContent}>
                <div>
                  <h1 className={styles.title}>{event.title || `${t('events.event')} ${eventId}`}</h1>
                  {event.subtitle ? <p className={styles.subtitle}>{event.subtitle}</p> : null}
                  <p className={styles.description}>{event.description || t('events.detail_page.messages.no_description')}</p>
                  <EventOrganizer author={event.author} isOwner={isOwner} token={token} t={t} />
                </div>

                <div className={styles.heroActions}>
                  <SocialActions
                    target={event}
                    contentType="event"
                    apiBasePath="/events"
                    publicBasePath="/events"
                    variant="list"
                    showBookmark
                    metrics={{
                      views: event?.views_count ?? 0,
                      likes: event?.likes_count ?? 0,
                      comments: event?.comments_count ?? 0,
                    }}
                    metricsOrder={['views', 'likes', 'comments']}
                    shareLabel={t('events.ui.share')}
                    reportLabel={t('events.ui.report')}
                    bookmarkLabel={event?.is_bookmarked ? t('events.unsave_event') : t('events.save_event')}
                    onComments={() => commentsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
                    className={styles.social}
                    metricsClassName={styles.socialMetrics}
                    metricClassName={styles.socialMetric}
                    buttonsClassName={styles.socialButtons}
                  />
                  {isOwner ? (
                    <PillButton
                      label={t('events.detail_page.actions.edit')}
                      href={`/events/${event.slug || eventId}/edit`}
                      className={styles.bookButton}
                      icon="arrow"
                    />
                  ) : null}
                </div>
              </div>
            </div>

            <EventGigsHighlight eventId={event.id} token={token} t={t} locale={locale} />
            <EventConfirmedPeople eventId={event.id} token={token} t={t} />

            <div className={styles.middle}>
              <div className={styles.mapWrap}>
                <MapContainer
                  center={[mapCenter.lat, mapCenter.lng]}
                  zoom={hasMapCoordinates ? 11 : 6}
                  className={styles.mapLeaflet}
                  scrollWheelZoom={false}
                >
                  <OsmTileLayer />
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
                  <p className={styles.locationHeading}>{t('events.city')}</p>
                  {locationLines.length > 0 ? (
                    locationLines.map((line) => (
                      <p key={line} className={styles.locationLine}>
                        {line}
                      </p>
                    ))
                  ) : (
                    <p className={styles.locationLine}>{t('events.detail_page.messages.location_unavailable')}</p>
                  )}
                </div>
              </div>
            </div>

            <div className={styles.metaGrid}>
              {metaItems.map((item) => (
                <article key={item.label} className={styles.metaCard}>
                  <p className={styles.metaLabel}>{item.label}</p>
                  <p className={styles.metaValue}>{formatDisplayValue(item.value, {
                    yes: t('events.detail_page.common.yes'),
                    no: t('events.detail_page.common.no'),
                  })}</p>
                </article>
              ))}
            </div>

            <section className={styles.extraSection}>
              <div className={styles.extraHeader}>
                <h2 className={styles.extraTitle}>{t('events.detail_page.details_title')}</h2>
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
                          <dd className={styles.detailFactValue}>{formatDisplayValue(item.value, {
                            yes: t('events.detail_page.common.yes'),
                            no: t('events.detail_page.common.no'),
                          })}</dd>
                        </div>
                      ))}
                    </dl>
                  </section>
                ))}
              </div>
            </section>
          </section>
        ) : null}

        {viewState.status === 'ready' && event ? (
          <div ref={commentsRef} id="comments" className={styles.commentsBand}>
            <CommentsThread contentType="event" contentId={event.id} />
          </div>
        ) : null}
      </section>

      <section className={styles.upcomingSection}>
        <Suspense fallback={null}>
          <UpcomingEventSlider />
        </Suspense>
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

      <ContentReportModal
        key={reportModalItem?.id ?? 'event-detail-report-closed'}
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? event?.title ?? t('events.event_title')}
        title={t('events.ui.report_title')}
        description={t('events.ui.report_description')}
        cancelLabel={t('events.ui.report_cancel')}
        confirmLabel={t('events.ui.report_confirm')}
        isSubmitting={isSubmittingReport}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />

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
