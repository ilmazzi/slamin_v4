import { useEffect, useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { listModerationReports } from '../../app/api/moderation.js'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginationUi } from '../../app/hooks/usePaginationUi.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import { REPORT_STATUSES, REPORT_TYPES, reportableTitle, reporterLabel } from './moderationConfig.js'
import styles from './Moderation.module.css'

export default function ModerationReportsPage() {
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const [items, setItems] = useState([])
  const [meta, setMeta] = useState({})
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(true)

  const status = searchParams.get('status') || 'open'
  const reportableType = searchParams.get('type') || ''
  const page = Math.max(1, Number(searchParams.get('page') || 1))

  const pagination = usePaginationUi({
    meta,
    fallbackPage: page,
    fallbackPerPage: 20,
    itemCount: items.length,
    t,
  })

  const statusLabel = useMemo(() => ({
    open: t('moderation.ui.status_open'),
    in_review: t('moderation.ui.status_in_review'),
    resolved: t('moderation.ui.status_resolved'),
    dismissed: t('moderation.ui.status_dismissed'),
  }), [t])

  useEffect(() => {
    if (!token) {
      return undefined
    }

    const controller = new AbortController()

    async function load() {
      setIsLoading(true)
      setError('')
      try {
        const result = await listModerationReports({
          token,
          signal: controller.signal,
          page,
          perPage: 20,
          status,
          reportableType,
        })
        setItems(result.items)
        setMeta(result.meta)
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
  }, [token, page, status, reportableType, t])

  const updateParams = (next) => {
    const params = new URLSearchParams(searchParams)
    Object.entries(next).forEach(([key, value]) => {
      if (value) {
        params.set(key, String(value))
      } else {
        params.delete(key)
      }
    })
    setSearchParams(params)
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/moderation/dashboard">{t('moderation.ui.back_dashboard')}</Link>
          </p>
          <h1 className={styles.title}>{t('moderation.ui.queue_reports')}</h1>
        </header>

        <div className={styles.toolbar}>
          <label className={styles.field}>
            <span>{t('moderation.ui.status')}</span>
            <select value={status} onChange={(event) => updateParams({ status: event.target.value, page: 1 })}>
              {REPORT_STATUSES.map((value) => (
                <option key={value} value={value}>{statusLabel[value]}</option>
              ))}
            </select>
          </label>
          <label className={styles.field}>
            <span>{t('moderation.ui.report_type')}</span>
            <select
              value={reportableType}
              onChange={(event) => updateParams({ type: event.target.value, page: 1 })}
            >
              <option value="">{t('moderation.ui.type_all')}</option>
              {REPORT_TYPES.map((value) => (
                <option key={value} value={value}>{t(`report.types.${value}`)}</option>
              ))}
            </select>
          </label>
        </div>

        {isLoading ? <p className={styles.status}>{t('moderation.ui.loading')}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}
        {!isLoading && items.length === 0 ? <p className={styles.status}>{t('moderation.ui.empty')}</p> : null}

        {items.length > 0 ? (
          <table className={styles.table}>
            <thead>
              <tr>
                <th>{t('moderation.ui.content')}</th>
                <th>{t('moderation.ui.reason')}</th>
                <th>{t('moderation.ui.reporter')}</th>
                <th>{t('moderation.ui.status')}</th>
                <th>{t('moderation.ui.updated')}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.id}>
                  <td>
                    <Link to={`/moderation/reports/${item.id}`}>
                      {reportableTitle(item.reportable, t('moderation.ui.untitled'))}
                    </Link>
                    <p className={styles.preview}>
                      {item.reportable?.type ? t(`report.types.${item.reportable.type}`) : ''}
                    </p>
                  </td>
                  <td>{t(`report.ui.reasons.${item.reason}`, item.reason)}</td>
                  <td>{reporterLabel(item)}</td>
                  <td>
                    <span className={`${styles.badge} ${styles[item.status] || ''}`}>
                      {statusLabel[item.status] || item.status}
                    </span>
                  </td>
                  <td>{formatShortPublishedDate(item.created_at, locale)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : null}

        <Pagination
          currentPage={pagination.currentPage}
          lastPage={pagination.lastPage}
          onPageChange={(nextPage) => updateParams({ page: nextPage })}
          infoText={pagination.paginationInfo}
          prevAriaLabel={pagination.prevPaginationLabel}
          nextAriaLabel={pagination.nextPaginationLabel}
        />
      </section>
    </MainLayout>
  )
}
