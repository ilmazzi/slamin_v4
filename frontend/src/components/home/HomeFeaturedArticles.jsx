import { useState } from 'react'
import { listFeaturedArticles } from '../../app/api/articles.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import ArticleTeaserCard from '../shared/ArticleTeaserCard.jsx'
import SectionHeading from './SectionHeading.jsx'
import styles from './HomeFeaturedArticles.module.css'

const FEATURED_ARTICLES_PER_PAGE = 12

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

export default function HomeFeaturedArticles() {
  const { t, locale } = useI18n()
  const [slideIndex, setSlideIndex] = useState(0)

  const { items: articles, isLoading, error } = usePaginatedApiList({
    fetcher: listFeaturedArticles,
    params: {
      page: 1,
      perPage: FEATURED_ARTICLES_PER_PAGE,
    },
    deps: [],
  })

  if (isLoading || error || !articles.length) {
    return null
  }

  const maxIndex = Math.max(0, articles.length - 1)
  const currentIndex = Math.min(slideIndex, maxIndex)
  const canNavigate = maxIndex > 0

  return (
    <section className={styles.section} aria-label={t('home.featured_articles.title')}>
      <div className={styles.headingWrap}>
        <SectionHeading
          accent={t('home.featured_articles.title')}
          subtitle={t('home.featured_articles.subtitle')}
          align="left"
        />
      </div>

      <div className={styles.slider}>
        {canNavigate ? (
          <button
            type="button"
            className={`${styles.navButton} ${styles.prev}`}
            aria-label={t('home.featured_articles.prev_aria')}
            onClick={() => setSlideIndex((prev) => (prev <= 0 ? maxIndex : prev - 1))}
          >
            <ChevronIcon direction="prev" />
          </button>
        ) : null}

        <div className={styles.viewport}>
          <div
            className={styles.track}
            style={{
              width: `${articles.length * 100}%`,
              transform: `translateX(-${(currentIndex / articles.length) * 100}%)`,
            }}
          >
            {articles.map((article) => (
              <div
                key={article.id ?? article.slug}
                className={styles.slide}
                style={{ width: `${100 / articles.length}%` }}
              >
                <ArticleTeaserCard article={article} locale={locale} t={t} />
              </div>
            ))}
          </div>
        </div>

        {canNavigate ? (
          <button
            type="button"
            className={`${styles.navButton} ${styles.next}`}
            aria-label={t('home.featured_articles.next_aria')}
            onClick={() => setSlideIndex((prev) => (prev >= maxIndex ? 0 : prev + 1))}
          >
            <ChevronIcon direction="next" />
          </button>
        ) : null}
      </div>
    </section>
  )
}
