import { useEffect, useMemo, useState } from 'react'
import { getApiUrl } from '../../app/api/url.js'
import { readJsonSafe } from '../../app/api/slaminApi.js'
import { normalizeEventForMap } from '../../app/api/eventMappers.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import SlideCard from '../shared/SlideCard.jsx'
import styles from './UpcomingSlider.module.css'

const DESKTOP_BREAKPOINT = 1024
const UPCOMING_PER_PAGE = 9

export default function UpcomingEventSlider() {
  const { t, locale } = useI18n()
  const [events, setEvents] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [slideStart, setSlideStart] = useState(0)
  const [isDesktop, setIsDesktop] = useState(
    typeof window !== 'undefined' ? window.innerWidth >= DESKTOP_BREAKPOINT : true,
  )
  const AUTO_SLIDE_MS = 4500
  const [isPaused, setIsPaused] = useState(false)

  useEffect(() => {
    const onResize = () => setIsDesktop(window.innerWidth >= DESKTOP_BREAKPOINT)
    window.addEventListener('resize', onResize)
    return () => window.removeEventListener('resize', onResize)
  }, [])

  useEffect(() => {
    const controller = new AbortController()

    async function loadUpcoming() {
      setIsLoading(true)
      setError('')

      try {
        const params = new URLSearchParams()
        params.set('per_page', String(UPCOMING_PER_PAGE))
        params.set('page', '1')
        const response = await fetch(getApiUrl(`/api/v1/events?${params.toString()}`), {
          signal: controller.signal,
          headers: { Accept: 'application/json' },
        })

        if (!response.ok) {
          throw new Error(`Upcoming fetch failed: ${response.status}`)
        }

        const payload = await readJsonSafe(response)
        const raw = Array.isArray(payload?.data) ? payload.data : []
        const normalized = raw
          .map((e) => normalizeEventForMap(e))
          .filter((e) => e != null)
        setEvents(normalized)
      } catch (err) {
        if (err?.name !== 'AbortError') {
          setError(t('common.no_results'))
          setEvents([])
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      }
    }

    loadUpcoming()
    return () => controller.abort()
  }, [t])

  const cardsPerView = isDesktop ? 3 : 1
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

  const mappedEvents = useMemo(() => {
    return events.map((item) => {
      const dateLabel = item?.start_datetime
        ? new Date(item.start_datetime).toLocaleDateString(locale || 'it-IT')
        : '—'

      return {
        id: item.id,
        slug: item.slug,
        title: item.title || t('events.event_title'),
        description: item.subtitle || item.description || t('events.event_description'),
        city: item.city || '—',
        dateLabel,
        imageUrl: item.image_url,
      }
    })
  }, [events, locale, t])

  return (
    <section className={styles.section}>
      <div className={styles.titleRow}>
        <h3>
          <em>{t('events.upcoming')}</em> {t('events.events')}
        </h3>
      </div>

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
                  <SlideCard
                    title={item.title}
                    description={item.description}
                    city={item.city}
                    dateLabel={item.dateLabel}
                    imageUrl={item.imageUrl}
                    href={item.slug ? `/events/${encodeURIComponent(item.slug)}` : '/events'}
                  />
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
