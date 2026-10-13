import { useEffect, useRef, useState } from 'react'
import MediaCard from './MediaCard.jsx'
import SectionHeading from './SectionHeading.jsx'
import styles from './HomeMedia.module.css'

const AUTOSLIDE_INTERVAL_MS = 5000
const AUTOSLIDE_RESUME_DELAY_MS = 7000

export default function HomeMedia({
  headingAccent,
  headingSubtitle,
  items,
  title,
  subtitle,
  playAria,
  openAria,
  shareLabel,
  reportLabel,
  prevAria,
  nextAria,
}) {
  const trackRef = useRef(null)
  const autoplayTimeoutRef = useRef(null)
  const isPausedRef = useRef(false)
  const resumeAtRef = useRef(0)
  const [canScrollPrev, setCanScrollPrev] = useState(false)
  const [canScrollNext, setCanScrollNext] = useState(items.length > 1)

  useEffect(() => {
    const track = trackRef.current

    if (!track) {
      return undefined
    }

    const updateScrollState = () => {
      const maxScrollLeft = track.scrollWidth - track.clientWidth

      setCanScrollPrev(track.scrollLeft > 4)
      setCanScrollNext(maxScrollLeft - track.scrollLeft > 4)
    }

    updateScrollState()
    track.addEventListener('scroll', updateScrollState, { passive: true })
    window.addEventListener('resize', updateScrollState)

    return () => {
      track.removeEventListener('scroll', updateScrollState)
      window.removeEventListener('resize', updateScrollState)
    }
  }, [items])

  useEffect(() => {
    return () => {
      if (autoplayTimeoutRef.current) {
        window.clearTimeout(autoplayTimeoutRef.current)
      }
    }
  }, [])

  const getCardStep = () => {
    const track = trackRef.current

    if (!track) {
      return 0
    }

    const firstCard = track.querySelector('[data-media-card]')
    const gap =
      Number.parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '0') || 0
    const cardWidth = firstCard?.getBoundingClientRect().width || 0

    return cardWidth + gap
  }

  const scrollByOneCard = (direction) => {
    const track = trackRef.current

    if (!track) {
      return
    }

    const amount = getCardStep()

    track.scrollBy({
      left: direction * amount,
      behavior: 'smooth',
    })
  }

  const markUserInteraction = () => {
    resumeAtRef.current = Date.now() + AUTOSLIDE_RESUME_DELAY_MS
  }

  useEffect(() => {
    const track = trackRef.current

    if (!track || items.length <= 1) {
      return undefined
    }

    const mediaQuery =
      typeof window !== 'undefined' && typeof window.matchMedia === 'function'
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : null

    const tick = () => {
      if (!trackRef.current) {
        return
      }

      if (mediaQuery?.matches || isPausedRef.current || Date.now() < resumeAtRef.current) {
        autoplayTimeoutRef.current = window.setTimeout(tick, AUTOSLIDE_INTERVAL_MS)
        return
      }

      const trackNode = trackRef.current
      const amount = getCardStep()

      if (!amount) {
        autoplayTimeoutRef.current = window.setTimeout(tick, AUTOSLIDE_INTERVAL_MS)
        return
      }

      const maxScrollLeft = trackNode.scrollWidth - trackNode.clientWidth
      const nextLeft = trackNode.scrollLeft + amount

      trackNode.scrollTo({
        left: nextLeft >= maxScrollLeft - 4 ? 0 : nextLeft,
        behavior: 'smooth',
      })

      autoplayTimeoutRef.current = window.setTimeout(tick, AUTOSLIDE_INTERVAL_MS)
    }

    const handlePointerEnter = () => {
      isPausedRef.current = true
    }

    const handlePointerLeave = () => {
      isPausedRef.current = false
    }

    const handleFocusIn = () => {
      isPausedRef.current = true
    }

    const handleFocusOut = () => {
      isPausedRef.current = false
      markUserInteraction()
    }

    const handleTouchStart = () => {
      isPausedRef.current = true
      markUserInteraction()
    }

    const handleTouchEnd = () => {
      isPausedRef.current = false
      markUserInteraction()
    }

    track.addEventListener('pointerenter', handlePointerEnter)
    track.addEventListener('pointerleave', handlePointerLeave)
    track.addEventListener('focusin', handleFocusIn)
    track.addEventListener('focusout', handleFocusOut)
    track.addEventListener('touchstart', handleTouchStart, { passive: true })
    track.addEventListener('touchend', handleTouchEnd, { passive: true })

    autoplayTimeoutRef.current = window.setTimeout(tick, AUTOSLIDE_INTERVAL_MS)

    return () => {
      track.removeEventListener('pointerenter', handlePointerEnter)
      track.removeEventListener('pointerleave', handlePointerLeave)
      track.removeEventListener('focusin', handleFocusIn)
      track.removeEventListener('focusout', handleFocusOut)
      track.removeEventListener('touchstart', handleTouchStart)
      track.removeEventListener('touchend', handleTouchEnd)

      if (autoplayTimeoutRef.current) {
        window.clearTimeout(autoplayTimeoutRef.current)
      }
    }
  }, [items])

  return (
    <>
      <section className={styles.headingWrap}>
        <SectionHeading accent={headingAccent} title={headingSubtitle} />
      </section>

      <section className={styles.section}>
        <button
          type="button"
          className={styles.arrow}
          aria-label={prevAria}
          onClick={() => {
            markUserInteraction()
            scrollByOneCard(-1)
          }}
          disabled={!canScrollPrev}
        >
          ‹
        </button>

        <div ref={trackRef} className={styles.track}>
          {items.map((item) => (
            <MediaCard
              key={item.id}
              title={item.title || title}
              subtitle={item.subtitle || subtitle}
              tone={item.tone}
              type={item.type}
              href={item.href}
              mediaUrl={item.media_url}
              coverUrl={item.cover_url}
              views={item.stats?.views ?? item.views ?? 0}
              likes={item.stats?.likes ?? item.likes ?? 0}
              comments={item.stats?.comments ?? item.comments ?? 0}
              playAria={playAria}
              openAria={openAria}
              shareLabel={shareLabel}
              reportLabel={reportLabel}
              item={Number.isFinite(Number(item.id)) ? item : null}
            />
          ))}
        </div>

        <button
          type="button"
          className={styles.arrow}
          aria-label={nextAria}
          onClick={() => {
            markUserInteraction()
            scrollByOneCard(1)
          }}
          disabled={!canScrollNext}
        >
          ›
        </button>
      </section>
    </>
  )
}
