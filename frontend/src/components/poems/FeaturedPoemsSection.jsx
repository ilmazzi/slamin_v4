import { useState } from 'react'
import { listFeaturedPoems } from '../../app/api/poems.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useMediaQuery } from '../../app/hooks/useMediaQuery.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import PoemCatalogCard from './PoemCatalogCard.jsx'
import styles from './FeaturedPoemSection.module.css'

const FEATURED_POEMS_PER_PAGE = 9

function ChevronIcon({ direction }) {
  const isPrev = direction === 'prev'
  return (
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d={isPrev ? 'M15 5L8 12L15 19' : 'M9 5L16 12L9 19'}
        stroke="currentColor"
        strokeWidth="2.4"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  )
}

export default function FeaturedPoemsSection() {
  const { t, locale } = useI18n()
  const isDesktop = useMediaQuery('(min-width: 1024px)')
  const [slideStart, setSlideStart] = useState(0)

  const {
    items: poems,
    isLoading,
    error,
  } = usePaginatedApiList({
    fetcher: listFeaturedPoems,
    params: {
      page: 1,
      perPage: FEATURED_POEMS_PER_PAGE,
    },
    deps: [],
  })

  const cardsPerView = Math.min(isDesktop ? 3 : 1, Math.max(1, poems.length))
  const maxSlideStart = Math.max(0, poems.length - cardsPerView)
  const clampedSlideStart = Math.min(slideStart, maxSlideStart)

  if (isLoading) {
    return (
      <section className={styles.section} aria-label={t('poems.featured.title')}>
        <p className={styles.stateText}>{t('common.loading')}</p>
      </section>
    )
  }

  if (error) {
    return (
      <section className={styles.section} aria-label={t('poems.featured.title')}>
        <p className={styles.stateText}>{error}</p>
      </section>
    )
  }

  if (!poems.length) {
    return null
  }

  const canNavigate = maxSlideStart > 0

  return (
    <section className={styles.section} aria-label={t('poems.featured.title')}>
      {canNavigate ? (
        <button
          type="button"
          className={`${styles.navButton} ${styles.prev}`}
          aria-label={t('poems.featured.prev_slide')}
          onClick={() => setSlideStart((prev) => (prev <= 0 ? maxSlideStart : prev - 1))}
        >
          <ChevronIcon direction="prev" />
        </button>
      ) : null}

      <div className={styles.viewport}>
        <div
          className={styles.track}
          style={{
            width: `${(poems.length / cardsPerView) * 100}%`,
            transform: `translateX(-${(clampedSlideStart / poems.length) * 100}%)`,
          }}
        >
          {poems.map((poem) => (
            <div
              key={poem.id ?? poem.slug}
              className={styles.slide}
              style={{ width: `${100 / poems.length}%` }}
            >
              <PoemCatalogCard poem={poem} locale={locale} t={t} />
            </div>
          ))}
        </div>
      </div>

      {canNavigate ? (
        <button
          type="button"
          className={`${styles.navButton} ${styles.next}`}
          aria-label={t('poems.featured.next_slide')}
          onClick={() => setSlideStart((prev) => (prev >= maxSlideStart ? 0 : prev + 1))}
        >
          <ChevronIcon direction="next" />
        </button>
      ) : null}
    </section>
  )
}
