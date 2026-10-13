import { useEffect, useMemo, useState } from 'react'
import { Link, Navigate, useParams, useSearchParams } from 'react-router-dom'
import { listModerationQueue } from '../../app/api/moderation.js'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginationUi } from '../../app/hooks/usePaginationUi.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import { authorLabel, isQueueType, QUEUE_STATUSES, QUEUE_TYPES } from './moderationConfig.js'
import styles from './Moderation.module.css'

export default function ModerationQueuePage() {
  const { type } = useParams()
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const [items, setItems] = useState([])
  const [meta, setMeta] = useState({})
  const [draftQuery, setDraftQuery] = useState(searchParams.get('q') || '')
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(true)

  const config = QUEUE_TYPES[type]
  const status = searchParams.get('status') || 'pending_review'
  const query = searchParams.get('q') || ''
  const page = Math.max(1, Number(searchParams.get('page') || 1))

  const pagination = usePaginationUi({
    meta,
    fallbackPage: page,
    fallbackPerPage: 20,
    itemCount: items.length,
    t,
  })

  useEffect(() => {
    setDraftQuery(query)
  }, [query])

  useEffect(() => {
    if (!token || !config) {
      return undefined
    }

    const controller = new AbortController()

    async function load() {
      setIsLoading(true)
      setError('')
      try {
        const result = await listModerationQueue(config.apiType, {
          token,
          signal: controller.signal,
          page,
          perPage: 20,
          status,
          q: query,
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
  }, [token, config, page, status, query, t])

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

  const statusLabel = useMemo(() => ({
    pending_review: t('moderation.ui.status_pending_review'),
    rejected: t('moderation.ui.status_rejected'),
    published: t('moderation.ui.status_published'),
    draft: t('moderation.ui.status_draft'),
  }), [t])

  if (!isQueueType(type)) {
    return <Navigate to="/moderation/dashboard" replace />
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/moderation/dashboard">{t('moderation.ui.back_dashboard')}</Link>
          </p>
          <h1 className={styles.title}>{t(config.labelKey)}</h1>
        </header>

        <form
          className={styles.toolbar}
          onSubmit={(event) => {
            event.preventDefault()
            updateParams({ q: draftQuery.trim(), page: 1 })
          }}
        >
          <label className={styles.field}>
            <span>{t('moderation.ui.status')}</span>
            <select
              value={status}
              onChange={(event) => updateParams({ status: event.target.value, page: 1 })}
            >
              {QUEUE_STATUSES.map((value) => (
                <option key={value} value={value}>{statusLabel[value]}</option>
              ))}
            </select>
          </label>
          <label className={styles.fieldGrow}>
            <span>{t('moderation.ui.search')}</span>
            <input
              value={draftQuery}
              onChange={(event) => setDraftQuery(event.target.value)}
              placeholder={t('moderation.ui.search_placeholder')}
            />
          </label>
          <button type="submit">{t('moderation.ui.search_submit')}</button>
        </form>

        {isLoading ? <p className={styles.status}>{t('moderation.ui.loading')}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}
        {!isLoading && items.length === 0 ? <p className={styles.status}>{t('moderation.ui.empty')}</p> : null}

        {items.length > 0 ? (
          <table className={styles.table}>
            <thead>
              <tr>
                <th>{t('moderation.ui.content')}</th>
                <th>{t('moderation.ui.author')}</th>
                <th>{t('moderation.ui.status')}</th>
                <th>{t('moderation.ui.updated')}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.id}>
                  <td>
                    <Link to={`/moderation/${type}/${item.id}`}>{item.title || t('moderation.ui.untitled')}</Link>
                    <p className={styles.preview}>{config.preview(item)}</p>
                  </td>
                  <td>{authorLabel(item)}</td>
                  <td>
                    <span className={`${styles.badge} ${styles[item.status] || ''}`}>
                      {statusLabel[item.status] || item.status}
                    </span>
                  </td>
                  <td>{formatShortPublishedDate(item.updated_at || item.created_at, locale)}</td>
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
