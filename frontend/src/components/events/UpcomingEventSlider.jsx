import { useEffect, useMemo, useState } from 'react'
import { listEvents } from '../../app/api/events.js'
import { normalizeEventsForUi } from '../../app/normalizers/event.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import SlideCard from '../shared/SlideCard.jsx'
import { eventSlideCardProps } from './eventSlideCardProps.js'
import styles from './UpcomingSlider.module.css'

const DESKTOP_BREAKPOINT = 1024
const TABLET_BREAKPOINT = 640
const UPCOMING_PER_PAGE = 9

const resolveCardsPerView = (width) => {
  if (width >= DESKTOP_BREAKPOINT) return 3
  if (width >= TABLET_BREAKPOINT) return 2
  return 1
}

export default function UpcomingEventSlider({
  events: providedEvents = null,
  isLoading: providedLoading = false,
  error: providedError = '',
  showHeading = true,
}) {
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const hasProvidedEvents = Array.isArray(providedEvents)
  const [fetchedEvents, setFetchedEvents] = useState([])
  const [fetchLoading, setFetchLoading] = useState(!hasProvidedEvents)
  const [fetchError, setFetchError] = useState('')
  const [slideStart, setSlideStart] = useState(0)
  const [cardsPerView, setCardsPerView] = useState(
    typeof window !== 'undefined' ? resolveCardsPerView(window.innerWidth) : 3,
  )
  const AUTO_SLIDE_MS = 4500
  const [isPaused, setIsPaused] = useState(false)

  useEffect(() => {
    const onResize = () => setCardsPerView(resolveCardsPerView(window.innerWidth))
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [])

  useEffect(() => {
    if (hasProvidedEvents) return undefined

    const controller = new AbortController()

    async function loadUpcoming() {
      setFetchLoading(true)
      setFetchError('')

      try {
        const { items: raw } = await listEvents(
          { perPage: UPCOMING_PER_PAGE, page: 1, token },
          { signal: controller.signal },
        )
        const normalized = normalizeEventsForUi(raw)
        setFetchedEvents(normalized)
      } catch (err) {
        if (err?.name !== 'AbortError') {
          setFetchError(t('common.no_results'))
          setFetchedEvents([])
        }
      } finally {
        if (!controller.signal.aborted) {
          setFetchLoading(false)
        }
      }
    }

    loadUpcoming()
    return () => controller.abort()
  }, [hasProvidedEvents, t, token])

  const events = hasProvidedEvents ? providedEvents : fetchedEvents
  const isLoading = hasProvidedEvents ? providedLoading : fetchLoading
  const error = hasProvidedEvents ? providedError : fetchError

  const maxSlideStart = Math.max(0, events.length - cardsPerView)
  const clampedSlideStart = Math.min(slideStart, maxSlideStart)

  useEffect(() => {
    if (isPaused) {
      return
    }
    if (isLoading || error) {
      return
    }
    if (maxSlideStart <= 0) {
      return
    }
    const timer = window.setInterval(() => {
      setSlideStart((prev) => (prev >= maxSlideStart ? 0 : prev + 1))
    }, AUTO_SLIDE_MS)
    return () => window.clearInterval(timer)
  }, [isPaused, isLoading, error, maxSlideStart])

  useEffect(() => {
    setSlideStart((prev) => Math.min(prev, maxSlideStart))
  }, [maxSlideStart])

  const mappedEvents = useMemo(
    () => events.map((item) => ({
      id: item.instance_id ?? String(item.id),
      props: eventSlideCardProps(item, { t, locale }),
    })),
    [events, locale, t],
  )

  return (
    <section className={styles.section}>
      {showHeading ? (
        <div className={styles.titleRow}>
          <h3>
            <em>{t('events.upcoming')}</em> {t('events.events')}
          </h3>
        </div>
      ) : null}

      <div className={styles.sliderRow}>
        <button
          type="button"
          className={styles.arrow}
          aria-label={t('events.carousel_prev')}
          disabled={clampedSlideStart === 0}
          onClick={() => setSlideStart((prev) => Math.max(0, prev - 1))}
        >
          ‹
        </button>

        <div
          className={styles.viewport}
          onMouseEnter={() => setIsPaused(true)}
          onMouseLeave={() => setIsPaused(false)}
          onFocusCapture={() => setIsPaused(true)}
          onBlurCapture={() => setIsPaused(false)}
        >
          {isLoading ? <p className={styles.stateText}>{t('common.loading')}</p> : null}
          {!isLoading && error ? <p className={styles.stateText}>{error}</p> : null}
          {!isLoading && !error && mappedEvents.length === 0 ? (
            <p className={styles.stateText}>{t('common.no_results')}</p>
          ) : null}

          {!isLoading && !error && mappedEvents.length > 0 ? (
            <div
              className={styles.track}
              style={{
                transform: `translateX(-${clampedSlideStart * (100 / cardsPerView)}%)`,
              }}
            >
              {mappedEvents.map((item) => (
                <div key={item.id} className={styles.slide}>
                  <SlideCard {...item.props} />
                </div>
              ))}
            </div>
          ) : null}
        </div>

        <button
          type="button"
          className={styles.arrow}
          aria-label={t('events.carousel_next')}
          disabled={clampedSlideStart >= maxSlideStart}
          onClick={() => setSlideStart((prev) => Math.min(maxSlideStart, prev + 1))}
        >
          ›
        </button>
      </div>
    </section>
  )
}
