import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getModerationDashboard } from '../../app/api/moderation.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { QUEUE_TYPES } from './moderationConfig.js'
import styles from './Moderation.module.css'

export default function ModerationDashboardPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const [stats, setStats] = useState(null)
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    if (!token) {
      return undefined
    }

    const controller = new AbortController()

    async function load() {
      setIsLoading(true)
      setError('')
      try {
        const nextStats = await getModerationDashboard({ token, signal: controller.signal })
        setStats(nextStats)
      } catch (loadError) {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('moderation.ui.load_error'))
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      }
    }

    load()
    return () => controller.abort()
  }, [token, t])

  const pending = (key) => stats?.[key]?.pending_review_count ?? 0

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/admin">{t('moderation.ui.back')}</Link>
          </p>
          <h1 className={styles.title}>{t('moderation.ui.title')}</h1>
          <p className={styles.subtitle}>{t('moderation.ui.subtitle')}</p>
        </header>

        {isLoading ? <p className={styles.status}>{t('moderation.ui.loading')}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        <div className={styles.stats}>
          <div className={styles.stat}>
            <span className={styles.statValue}>{pending('poems')}</span>
            <span className={styles.statLabel}>{t('moderation.ui.pending_poems')}</span>
          </div>
          <div className={styles.stat}>
            <span className={styles.statValue}>{pending('articles')}</span>
            <span className={styles.statLabel}>{t('moderation.ui.pending_articles')}</span>
          </div>
          <div className={styles.stat}>
            <span className={styles.statValue}>{pending('events')}</span>
            <span className={styles.statLabel}>{t('moderation.ui.pending_events')}</span>
          </div>
          <div className={styles.stat}>
            <span className={styles.statValue}>{stats?.reports?.open_count ?? 0}</span>
            <span className={styles.statLabel}>{t('moderation.ui.open_reports')}</span>
          </div>
          <div className={styles.stat}>
            <span className={styles.statValue}>{stats?.approved_today_count ?? 0}</span>
            <span className={styles.statLabel}>{t('moderation.ui.approved_today')}</span>
          </div>
          <div className={styles.stat}>
            <span className={styles.statValue}>{stats?.rejected_today_count ?? 0}</span>
            <span className={styles.statLabel}>{t('moderation.ui.rejected_today')}</span>
          </div>
        </div>

        <div className={styles.grid}>
          {Object.entries(QUEUE_TYPES).map(([type, config]) => (
            <Link key={type} to={`/moderation/${type}?status=pending_review`} className={styles.card}>
              <h2>{t(config.labelKey)}</h2>
              <p>{t('moderation.ui.pending_count', { count: pending(config.pendingKey) })}</p>
              <span className={styles.openLink}>{t('moderation.ui.open')}</span>
            </Link>
          ))}
          <Link to="/moderation/reports?status=open" className={styles.card}>
            <h2>{t('moderation.ui.queue_reports')}</h2>
            <p>{t('moderation.ui.pending_count', { count: stats?.reports?.open_count ?? 0 })}</p>
            <span className={styles.openLink}>{t('moderation.ui.open')}</span>
          </Link>
        </div>
      </section>
    </MainLayout>
  )
}
