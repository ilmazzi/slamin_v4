import { useEffect, useState } from 'react'
import styles from '../../pages/Gigs/GigWorkspace.module.css'
import { formatGigDateTime } from './gigFormat.js'
import {
  createGigAnnouncement,
  deleteGigAnnouncement,
  listGigAnnouncements,
  updateGigAnnouncement,
} from '../../app/api/gigs.js'

const ANNOUNCEMENT_STATUSES = ['published', 'draft', 'archived']

export default function GigAnnouncementsPanel({ gigId, canManage, token, t, locale, onNotify }) {
  const [announcements, setAnnouncements] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [status, setStatus] = useState('published')
  const [busyId, setBusyId] = useState(null)

  useEffect(() => {
    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    listGigAnnouncements(gigId, { token, signal: controller.signal })
      .then((items) => setAnnouncements(items))
      .catch((err) => {
        if (err?.name !== 'AbortError') {
          setError(err.message)
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      })
    return () => controller.abort()
  }, [gigId, token])

  if (!canManage && !isLoading && !error && announcements.length === 0) {
    return null
  }

  async function handleCreate(event) {
    event.preventDefault()
    const text = message.trim()
    if (!text) {
      return
    }
    setBusyId('new')
    setError('')
    try {
      const created = await createGigAnnouncement(gigId, { message: text, status }, { token })
      if (created) {
        setAnnouncements((prev) => [created, ...prev])
      }
      setMessage('')
      onNotify(t('gigs.announcements.saved'))
    } catch (err) {
      setError(err.message)
    } finally {
      setBusyId(null)
    }
  }

  async function handleStatus(announcement, nextStatus) {
    setBusyId(announcement.id)
    setError('')
    try {
      const updated = await updateGigAnnouncement(gigId, announcement.id, { status: nextStatus }, { token })
      if (updated) {
        setAnnouncements((prev) => prev.map((item) => (item.id === updated.id ? updated : item)))
      }
      onNotify(t('gigs.announcements.saved'))
    } catch (err) {
      setError(err.message)
    } finally {
      setBusyId(null)
    }
  }

  async function handleDelete(announcement) {
    if (!window.confirm(t('gigs.common.confirm_delete'))) {
      return
    }
    setBusyId(announcement.id)
    setError('')
    try {
      await deleteGigAnnouncement(gigId, announcement.id, { token })
      setAnnouncements((prev) => prev.filter((item) => item.id !== announcement.id))
      onNotify(t('gigs.announcements.deleted'))
    } catch (err) {
      setError(err.message)
    } finally {
      setBusyId(null)
    }
  }

  return (
    <section className={styles.panel}>
      <header className={styles.panelHead}>
        <h2 className={styles.panelTitle}>{t('gigs.announcements.title')}</h2>
      </header>
      <div className={styles.panelBody}>
        {error ? <p className={styles.error} role="alert">{error}</p> : null}

        {canManage ? (
          <form className={styles.form} onSubmit={handleCreate}>
            <label className={styles.field}>
              {t('gigs.announcements.message_label')}
              <textarea
                className={styles.textarea}
                value={message}
                onChange={(event) => setMessage(event.target.value)}
                placeholder={t('gigs.announcements.message_placeholder')}
                required
              />
            </label>
            <div className={styles.actions}>
              <select
                className={[styles.select, styles.inlineSelect].join(' ')}
                value={status}
                onChange={(event) => setStatus(event.target.value)}
                aria-label={t('gigs.announcements.status_label')}
              >
                {ANNOUNCEMENT_STATUSES.map((value) => (
                  <option key={value} value={value}>{t(`gigs.announcement_statuses.${value}`)}</option>
                ))}
              </select>
              <button type="submit" className={[styles.btn, styles.btnDark].join(' ')} disabled={busyId === 'new' || !message.trim()}>
                {busyId === 'new' ? t('gigs.common.saving') : t('gigs.common.save')}
              </button>
            </div>
          </form>
        ) : null}

        {isLoading ? <p className={styles.muted}>{t('gigs.common.loading')}</p> : null}
        {!isLoading && announcements.length === 0 ? <p className={styles.muted}>{t('gigs.announcements.empty')}</p> : null}

        {announcements.length > 0 ? (
          <ul className={styles.list}>
            {announcements.map((announcement) => (
              <li key={announcement.id} className={styles.listItem}>
                <div className={styles.listItemHead}>
                  <span className={styles.muted}>
                    {formatGigDateTime(announcement.published_at || announcement.created_at, locale)}
                  </span>
                  {canManage ? (
                    <span className={styles.badge}>{t(`gigs.announcement_statuses.${announcement.status}`)}</span>
                  ) : null}
                </div>
                <p className={styles.text}>{announcement.message}</p>
                {canManage ? (
                  <div className={styles.actions}>
                    {announcement.status !== 'published' ? (
                      <button type="button" className={styles.btn} disabled={busyId === announcement.id} onClick={() => handleStatus(announcement, 'published')}>
                        {t('gigs.announcements.publish')}
                      </button>
                    ) : (
                      <button type="button" className={styles.btn} disabled={busyId === announcement.id} onClick={() => handleStatus(announcement, 'archived')}>
                        {t('gigs.announcements.archive')}
                      </button>
                    )}
                    <button type="button" className={[styles.btn, styles.btnDanger].join(' ')} disabled={busyId === announcement.id} onClick={() => handleDelete(announcement)}>
                      {t('gigs.common.delete')}
                    </button>
                  </div>
                ) : null}
              </li>
            ))}
          </ul>
        ) : null}
      </div>
    </section>
  )
}
