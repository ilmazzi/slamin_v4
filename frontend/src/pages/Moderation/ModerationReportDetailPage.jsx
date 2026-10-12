import { useEffect, useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { showModerationReport, updateModerationReport } from '../../app/api/moderation.js'
import { formatDateTime } from '../../app/formatters/date.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { reportableHref, reportableTitle, reporterLabel } from './moderationConfig.js'
import styles from './Moderation.module.css'

export default function ModerationReportDetailPage() {
  const { id } = useParams()
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const [report, setReport] = useState(null)
  const [status, setStatus] = useState('in_review')
  const [resolutionNote, setResolutionNote] = useState('')
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)

  const statusLabel = useMemo(() => ({
    open: t('moderation.ui.status_open'),
    in_review: t('moderation.ui.status_in_review'),
    resolved: t('moderation.ui.status_resolved'),
    dismissed: t('moderation.ui.status_dismissed'),
  }), [t])

  useEffect(() => {
    if (!token || !id) {
      return undefined
    }

    const controller = new AbortController()

    async function load() {
      setIsLoading(true)
      setError('')
      try {
        const nextReport = await showModerationReport(id, { token, signal: controller.signal })
        setReport(nextReport)
        setStatus(nextReport?.status === 'open' ? 'in_review' : (nextReport?.status || 'in_review'))
        setResolutionNote(nextReport?.resolution_note || '')
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
  }, [token, id, t])

  const handleSave = async (event) => {
    event.preventDefault()
    setIsSaving(true)
    setError('')
    setMessage('')
    try {
      const nextReport = await updateModerationReport(id, {
        status,
        resolution_note: resolutionNote.trim() || null,
      }, { token })
      setReport(nextReport)
      setMessage(t('moderation.ui.saved'))
    } catch (saveError) {
      setError(saveError?.message || t('moderation.ui.save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  const href = reportableHref(report?.reportable)

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/moderation/reports">{t('moderation.ui.back_reports')}</Link>
          </p>
          <h1 className={styles.title}>{t('moderation.ui.report_detail')}</h1>
        </header>

        {isLoading ? <p className={styles.status}>{t('moderation.ui.loading')}</p> : null}
        {message ? <p className={styles.message}>{message}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        {report ? (
          <div className={styles.review}>
            <section className={styles.panel}>
              <div className={styles.meta}>
                <span className={`${styles.badge} ${styles[report.status] || ''}`}>
                  {statusLabel[report.status] || report.status}
                </span>
                <span>{reporterLabel(report)}</span>
                <span>{formatDateTime(report.created_at, locale)}</span>
              </div>
              <p>
                <strong>{t('moderation.ui.content')}:</strong>
                {' '}
                {href ? (
                  <Link to={href}>{reportableTitle(report.reportable, t('moderation.ui.untitled'))}</Link>
                ) : reportableTitle(report.reportable, t('moderation.ui.untitled'))}
              </p>
              <p>
                <strong>{t('moderation.ui.report_type')}:</strong>
                {' '}
                {report.reportable?.type ? t(`report.types.${report.reportable.type}`) : '—'}
              </p>
              <p>
                <strong>{t('moderation.ui.reason')}:</strong>
                {' '}
                {t(`report.ui.reasons.${report.reason}`, report.reason)}
              </p>
              {report.details ? <p className={styles.body}>{report.details}</p> : null}
            </section>

            <form className={styles.panel} onSubmit={handleSave}>
              <h2>{t('moderation.ui.decision')}</h2>
              <label className={styles.field}>
                <span>{t('moderation.ui.status')}</span>
                <select value={status} onChange={(event) => setStatus(event.target.value)} disabled={isSaving}>
                  <option value="in_review">{statusLabel.in_review}</option>
                  <option value="resolved">{statusLabel.resolved}</option>
                  <option value="dismissed">{statusLabel.dismissed}</option>
                </select>
              </label>
              <label className={styles.fieldGrow}>
                <span>{t('moderation.ui.resolution')}</span>
                <textarea
                  className={styles.resolution}
                  rows={4}
                  value={resolutionNote}
                  onChange={(event) => setResolutionNote(event.target.value)}
                  disabled={isSaving}
                />
              </label>
              <div className={styles.actions}>
                <button type="submit" disabled={isSaving}>
                  {isSaving ? t('moderation.ui.saving') : t('moderation.ui.save')}
                </button>
              </div>
            </form>
          </div>
        ) : null}
      </section>
    </MainLayout>
  )
}
