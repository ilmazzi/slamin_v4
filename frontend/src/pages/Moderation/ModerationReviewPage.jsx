import { useEffect, useMemo, useState } from 'react'
import { Link, Navigate, useParams } from 'react-router-dom'
import { approveModerationItem, rejectModerationItem, showModerationItem } from '../../app/api/moderation.js'
import { formatDateTime } from '../../app/formatters/date.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { authorLabel, isQueueType, QUEUE_TYPES } from './moderationConfig.js'
import styles from './Moderation.module.css'

export default function ModerationReviewPage() {
  const { type, id } = useParams()
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const [item, setItem] = useState(null)
  const [history, setHistory] = useState([])
  const [note, setNote] = useState('')
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)

  const config = QUEUE_TYPES[type]

  const statusLabel = useMemo(() => ({
    pending_review: t('moderation.ui.status_pending_review'),
    rejected: t('moderation.ui.status_rejected'),
    published: t('moderation.ui.status_published'),
    draft: t('moderation.ui.status_draft'),
  }), [t])

  useEffect(() => {
    if (!token || !config || !id) {
      return undefined
    }

    const controller = new AbortController()

    async function load() {
      setIsLoading(true)
      setError('')
      try {
        const result = await showModerationItem(config.apiType, id, {
          token,
          signal: controller.signal,
        })
        setItem(result.item)
        setHistory(result.history)
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
  }, [token, config, id, t])

  const handleApprove = async () => {
    setIsSaving(true)
    setError('')
    setMessage('')
    try {
      await approveModerationItem(config.apiType, id, {
        token,
        note: note.trim() || null,
      })
      const result = await showModerationItem(config.apiType, id, { token })
      setItem(result.item)
      setHistory(result.history)
      setMessage(t('moderation.ui.approved'))
      setNote('')
    } catch (saveError) {
      setError(saveError?.message || t('moderation.ui.save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  const handleReject = async () => {
    if (!note.trim()) {
      setError(t('moderation.ui.note_required'))
      return
    }

    setIsSaving(true)
    setError('')
    setMessage('')
    try {
      await rejectModerationItem(config.apiType, id, {
        token,
        note: note.trim(),
      })
      const result = await showModerationItem(config.apiType, id, { token })
      setItem(result.item)
      setHistory(result.history)
      setMessage(t('moderation.ui.rejected_ok'))
      setNote('')
    } catch (saveError) {
      setError(saveError?.message || t('moderation.ui.save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  if (!isQueueType(type)) {
    return <Navigate to="/moderation/dashboard" replace />
  }

  const publicHref = config.publicHref(item)
  const canDecide = item?.status === 'pending_review'

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to={`/moderation/${type}`}>{t('moderation.ui.back_queue')}</Link>
          </p>
          <h1 className={styles.title}>{item?.title || t('moderation.ui.review')}</h1>
        </header>

        {isLoading ? <p className={styles.status}>{t('moderation.ui.loading')}</p> : null}
        {message ? <p className={styles.message}>{message}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        {item ? (
          <div className={styles.review}>
            <section className={styles.panel}>
              <div className={styles.meta}>
                <span className={`${styles.badge} ${styles[item.status] || ''}`}>
                  {statusLabel[item.status] || item.status}
                </span>
                <span>{authorLabel(item)}</span>
                <span>{formatDateTime(item.updated_at || item.created_at, locale)}</span>
                {publicHref ? <Link to={publicHref}>{t('moderation.ui.view_public')}</Link> : null}
              </div>
              <p className={styles.body}>{config.preview(item) || t('moderation.ui.no_preview')}</p>
            </section>

            <section className={styles.panel}>
              <h2>{t('moderation.ui.decision')}</h2>
              <label className={styles.fieldGrow}>
                <span>{t('moderation.ui.note')}</span>
                <textarea
                  className={styles.note}
                  rows={4}
                  value={note}
                  onChange={(event) => setNote(event.target.value)}
                  disabled={!canDecide || isSaving}
                />
              </label>
              <div className={styles.actions}>
                <button type="button" onClick={handleApprove} disabled={!canDecide || isSaving}>
                  {isSaving ? t('moderation.ui.saving') : t('moderation.ui.approve')}
                </button>
                <button type="button" className={styles.reject} onClick={handleReject} disabled={!canDecide || isSaving}>
                  {t('moderation.ui.reject')}
                </button>
              </div>
              {!canDecide ? <p className={styles.help}>{t('moderation.ui.already_decided')}</p> : null}
            </section>

            <section className={styles.panel}>
              <h2>{t('moderation.ui.history')}</h2>
              {history.length === 0 ? (
                <p className={styles.status}>{t('moderation.ui.no_history')}</p>
              ) : (
                <ul className={styles.history}>
                  {history.map((entry) => (
                    <li key={entry.id}>
                      <strong>
                        {entry.decision === 'approved'
                          ? t('moderation.ui.status_published')
                          : t('moderation.ui.status_rejected')}
                      </strong>
                      {' · '}
                      {authorLabel({ author: entry.moderator })}
                      {' · '}
                      {formatDateTime(entry.created_at, locale)}
                      {entry.note ? <p className={styles.help}>{entry.note}</p> : null}
                    </li>
                  ))}
                </ul>
              )}
            </section>
          </div>
        ) : null}
      </section>
    </MainLayout>
  )
}
