import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listAdminBackups, runAdminBackup } from '../../app/api/admin.js'
import { formatLocaleNumber } from '../../app/formatters/number.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './AdminSettingsPage.module.css'

function formatBytes(bytes, locale) {
  const value = Number(bytes) || 0
  if (value < 1024) {
    return `${formatLocaleNumber(value, locale)} B`
  }
  if (value < 1024 * 1024) {
    return `${formatLocaleNumber(Math.round(value / 1024), locale)} KB`
  }
  return `${formatLocaleNumber(Math.round(value / (1024 * 1024)), locale)} MB`
}

function formatDate(value, locale) {
  if (!value) {
    return null
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat(locale, {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(date)
}

export default function BackupsAdminPage() {
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const [status, setStatus] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [isRunning, setIsRunning] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')

  const loadStatus = useCallback(async ({ signal } = {}) => {
    const next = await listAdminBackups({ token, signal })
    setStatus(next)
  }, [token])

  useEffect(() => {
    if (!token) {
      return undefined
    }

    const controller = new AbortController()
    setIsLoading(true)
    setError('')

    loadStatus({ signal: controller.signal })
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('admin.backups.load_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      })

    return () => controller.abort()
  }, [loadStatus, t, token])

  const handleRun = async () => {
    setIsRunning(true)
    setMessage('')
    setError('')

    try {
      await runAdminBackup({ token })
      setMessage(t('admin.backups.queued'))
    } catch (runError) {
      setError(runError?.message || t('admin.backups.run_error'))
    } finally {
      setIsRunning(false)
    }
  }

  const backups = Array.isArray(status?.backups) ? status.backups : []

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/admin">{t('admin.hub.back')}</Link>
          </p>
          <h1 className={styles.title}>{t('admin.backups.title')}</h1>
          <p className={styles.subtitle}>{t('admin.backups.subtitle')}</p>
        </header>

        {error ? <p className={styles.error}>{error}</p> : null}
        {message ? <p className={styles.message}>{message}</p> : null}

        {isLoading ? (
          <p className={styles.help}>{t('admin.backups.loading')}</p>
        ) : (
          <div className={styles.form}>
            <section className={styles.card}>
              <h2>{status?.configured ? t('admin.backups.healthy') : t('admin.backups.not_configured')}</h2>
              {!status?.configured ? (
                <p className={styles.help}>{t('admin.backups.not_configured')}</p>
              ) : null}
              {status?.configured && !status?.reachable ? (
                <p className={styles.help}>{status.error || t('admin.backups.unreachable')}</p>
              ) : null}
              <p className={styles.help}>
                {t('admin.backups.newest')}: {formatDate(status?.newest_at, locale) || t('admin.backups.never')}
              </p>
              <p className={styles.help}>
                {t('admin.backups.used')}: {formatBytes(status?.used_bytes, locale)}
              </p>
              <div className={styles.cardActions}>
                <button type="button" onClick={handleRun} disabled={isRunning || !status?.configured}>
                  {isRunning ? t('admin.backups.running') : t('admin.backups.run')}
                </button>
              </div>
            </section>

            <section className={styles.card}>
              {backups.length === 0 ? (
                <p className={styles.help}>{t('admin.backups.empty')}</p>
              ) : (
                <ul className={styles.flowList}>
                  {backups.map((backup) => (
                    <li key={backup.path} className={styles.flowRow}>
                      <span className={styles.flowLabel}>{backup.path}</span>
                      <span>
                        {formatDate(backup.created_at, locale)} · {formatBytes(backup.size_bytes, locale)}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
            </section>

            <p className={styles.help}>{t('admin.backups.help')}</p>
          </div>
        )}
      </section>
    </MainLayout>
  )
}
