import { createElement, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  BookOpen,
  CalendarDays,
  Images,
  Newspaper,
  Users,
} from 'lucide-react'
import { getPersonalStatistics } from '../../app/api/statistics.js'
import { formatLocaleNumber } from '../../app/formatters/number.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import {
  mediaCommentsDesktopIcon,
  mediaLikeDesktopIcon,
  mediaViewsDesktopIcon,
} from '../../assets/home/media/index.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './PersonalStatisticsPage.module.css'

const METRICS = [
  { key: 'views_received', iconSrc: mediaViewsDesktopIcon },
  { key: 'likes_received', iconSrc: mediaLikeDesktopIcon },
  { key: 'comments_received', iconSrc: mediaCommentsDesktopIcon },
  { key: 'followers', icon: Users },
]

const PUBLICATIONS = [
  { key: 'poems', icon: BookOpen },
  { key: 'articles', icon: Newspaper },
  { key: 'events', icon: CalendarDays },
  { key: 'media', icon: Images },
]

function Ranking({ title, items, t, locale }) {
  return (
    <section className={styles.ranking}>
      <h2>{title}</h2>
      {items.length ? (
        <ol className={styles.rankingList}>
          {items.map((item, index) => (
            <li key={`${item.type}-${item.id}`} className={styles.rankingItem}>
              <span className={styles.position}>{index + 1}</span>
              <div className={styles.rankingContent}>
                <span className={styles.contentType}>
                  {t(`statistics.content_types.${item.type}`)}
                </span>
                <Link to={item.url} className={styles.contentTitle}>
                  {item.title || t('statistics.untitled')}
                </Link>
              </div>
              <div className={styles.contentMetrics} aria-label={t('statistics.item_metrics')}>
                <span title={t('statistics.metrics.views_received')}>
                  <img src={mediaViewsDesktopIcon} alt="" aria-hidden="true" />
                  {formatLocaleNumber(item.views_count, locale)}
                </span>
                <span title={t('statistics.metrics.likes_received')}>
                  <img src={mediaLikeDesktopIcon} alt="" aria-hidden="true" />
                  {formatLocaleNumber(item.likes_count, locale)}
                </span>
                <span title={t('statistics.metrics.comments_received')}>
                  <img src={mediaCommentsDesktopIcon} alt="" aria-hidden="true" />
                  {formatLocaleNumber(item.comments_count, locale)}
                </span>
              </div>
            </li>
          ))}
        </ol>
      ) : (
        <p className={styles.empty}>{t('statistics.empty')}</p>
      )}
    </section>
  )
}

export default function PersonalStatisticsPage({ user }) {
  const { token } = useAuth()
  const { t, locale } = useI18n()
  const [statistics, setStatistics] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    if (!token) {
      return undefined
    }

    const controller = new AbortController()

    async function loadStatistics() {
      setIsLoading(true)
      setError('')

      try {
        const data = await getPersonalStatistics({ token, signal: controller.signal })
        setStatistics(data)
      } catch (loadError) {
        if (loadError?.name !== 'AbortError') {
          setError(t('statistics.load_error'))
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      }
    }

    loadStatistics()
    return () => controller.abort()
  }, [reloadKey, t, token])

  return (
    <MainLayout variant="auth" user={user} mainClassName={styles.main}>
      <div className={styles.page}>
        <Link to="/dashboard" className={styles.backLink}>
          ← {t('statistics.back')}
        </Link>

        <header className={styles.header}>
          <p className={styles.eyebrow}>{t('statistics.private_label')}</p>
          <h1>{t('statistics.title')}</h1>
          <p>{t('statistics.subtitle')}</p>
        </header>

        {isLoading ? (
          <div className={styles.loading} role="status">
            <span className={styles.spinner} aria-hidden="true" />
            {t('statistics.loading')}
          </div>
        ) : null}

        {error ? (
          <div className={styles.error} role="alert">
            <p>{error}</p>
            <button type="button" onClick={() => setReloadKey((value) => value + 1)}>
              {t('statistics.retry')}
            </button>
          </div>
        ) : null}

        {statistics && !isLoading ? (
          <>
            <section className={styles.metricsGrid} aria-label={t('statistics.metrics_title')}>
              {METRICS.map(({ key, icon, iconSrc }) => (
                <article key={key} className={styles.metricCard}>
                  <span className={styles.metricIcon}>
                    {iconSrc ? (
                      <img src={iconSrc} alt="" aria-hidden="true" />
                    ) : (
                      createElement(icon, { 'aria-hidden': true })
                    )}
                  </span>
                  <strong>{formatLocaleNumber(statistics.totals?.[key] ?? 0, locale)}</strong>
                  <span>{t(`statistics.metrics.${key}`)}</span>
                </article>
              ))}
            </section>

            <section className={styles.publications}>
              <div>
                <h2>{t('statistics.publications_title')}</h2>
                <p>{t('statistics.publications_subtitle')}</p>
              </div>
              <div className={styles.publicationsGrid}>
                {PUBLICATIONS.map(({ key, icon }) => (
                  <article key={key} className={styles.publicationCard}>
                    {createElement(icon, { 'aria-hidden': true })}
                    <span>{t(`statistics.publications.${key}`)}</span>
                    <strong>{formatLocaleNumber(statistics.publications?.[key] ?? 0, locale)}</strong>
                  </article>
                ))}
              </div>
            </section>

            <div className={styles.rankingsGrid}>
              <Ranking
                title={t('statistics.top_views')}
                items={statistics.top_by_views ?? []}
                t={t}
                locale={locale}
              />
              <Ranking
                title={t('statistics.top_likes')}
                items={statistics.top_by_likes ?? []}
                t={t}
                locale={locale}
              />
            </div>
          </>
        ) : null}
      </div>
    </MainLayout>
  )
}
