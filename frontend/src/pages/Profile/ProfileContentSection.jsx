import { useEffect, useState } from 'react'
import { listArticles } from '../../app/api/articles.js'
import { listEvents } from '../../app/api/events.js'
import { listMedia } from '../../app/api/media.js'
import { listPoems } from '../../app/api/poems.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { normalizeEventForUi } from '../../app/normalizers/event.js'
import { eventSlideCardProps } from '../../components/events/eventSlideCardProps.js'
import MediaCard from '../../components/home/MediaCard.jsx'
import { mediaCardProps } from '../../components/media/mediaCardProps.js'
import PoemCatalogCard from '../../components/poems/PoemCatalogCard.jsx'
import ArticleTeaserCard from '../../components/shared/ArticleTeaserCard.jsx'
import SlideCard from '../../components/shared/SlideCard.jsx'
import dashboardStyles from '../Dashboard/DashboardPage.module.css'
import styles from './ProfileContentSection.module.css'

const SECTIONS = {
  poems: { load: listPoems, perPage: 6 },
  events: { load: listEvents, perPage: 6 },
  articles: { load: listArticles, perPage: 4 },
  media: { load: listMedia, perPage: 6 },
}

function SectionItems({ kind, items, t, locale }) {
  if (kind === 'poems') {
    return (
      <div className={styles.poemsBand}>
        <div className={styles.grid}>
          {items.map((poem) => (
            <PoemCatalogCard key={poem.id} poem={poem} locale={locale} t={t} />
          ))}
        </div>
      </div>
    )
  }

  if (kind === 'events') {
    return (
      <div className={styles.grid}>
        {items.map((event) => (
          <SlideCard key={event.id} compact {...eventSlideCardProps(normalizeEventForUi(event), { t, locale })} />
        ))}
      </div>
    )
  }

  if (kind === 'articles') {
    return (
      <div className={styles.articles}>
        {items.map((article) => (
          <ArticleTeaserCard key={article.id} article={article} locale={locale} t={t} compact />
        ))}
      </div>
    )
  }

  return (
    <div className={`${styles.grid} ${styles.mediaGrid}`}>
      {items.map((item, index) => (
        <MediaCard key={item.id} fluid {...mediaCardProps(item, index, t)} />
      ))}
    </div>
  )
}

export default function ProfileContentSection({ kind, nickname, token }) {
  const { t, locale } = useI18n()
  const [items, setItems] = useState([])
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [loadedKey, setLoadedKey] = useState('')
  const requestKey = `${kind}:${nickname}:${page}`
  const isLoading = loadedKey !== requestKey

  useEffect(() => {
    const controller = new AbortController()
    const currentKey = `${kind}:${nickname}:${page}`
    const { load, perPage } = SECTIONS[kind]

    load({ nickname, page, perPage }, { token, signal: controller.signal })
      .then(({ items: nextItems, meta }) => {
        if (controller.signal.aborted) {
          return
        }
        const list = Array.isArray(nextItems) ? nextItems : []
        setItems((current) => (page === 1 ? list : [...current, ...list]))
        setLastPage(Number(meta?.last_page ?? 1))
        setLoadedKey(currentKey)
      })
      .catch(() => {
        if (!controller.signal.aborted) {
          setLoadedKey(currentKey)
        }
      })

    return () => controller.abort()
  }, [kind, nickname, page, token])

  if (!isLoading && items.length === 0) {
    return null
  }

  return (
    <section className={styles.section} aria-labelledby={`profile-section-${kind}`}>
      <div className={dashboardStyles.recentTitleWrap}>
        <h2 id={`profile-section-${kind}`} className={dashboardStyles.recentTitle}>
          <span className={dashboardStyles.recentTitleAccent}>{t(`profile.works.${kind}`).toLowerCase()}</span>
        </h2>
      </div>

      <SectionItems kind={kind} items={items} t={t} locale={locale} />

      {page < lastPage ? (
        <div className={styles.more}>
          <button
            type="button"
            className={styles.moreButton}
            disabled={isLoading}
            onClick={() => setPage((current) => current + 1)}
          >
            {isLoading ? t('common.loading') : t('profile.page.show_more')}
          </button>
        </div>
      ) : null}
    </section>
  )
}
