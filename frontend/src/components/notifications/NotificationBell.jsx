import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useNotifications } from '../../app/hooks/useNotifications.js'
import {
  describeNotification,
  formatNotificationTime,
  notificationLink,
  notificationTone,
} from './notificationPresentation.js'
import styles from './NotificationBell.module.css'

export default function NotificationBell({ open, onToggle, onClose, buttonClassName = '', iconClassName = '' }) {
  const { t, locale } = useI18n()
  const { token, user } = useAuth()
  const navigate = useNavigate()
  const { unreadCount, items, status, loadList, markRead, markAllRead } = useNotifications({
    token,
    userId: user?.id,
  })

  useEffect(() => {
    if (open) {
      loadList()
    }
  }, [loadList, open])

  const handleSelect = async (notification) => {
    await markRead(notification)
    const link = notificationLink(notification)
    if (link) {
      onClose?.()
      navigate(link)
    }
  }

  const badge = unreadCount > 99 ? '99+' : String(unreadCount)
  const buttonLabel = unreadCount > 0
    ? t('notifications.aria_unread', { count: unreadCount })
    : t('notifications.title')

  return (
    <>
      <button
        type="button"
        className={`${buttonClassName} ${styles.bellButton}`}
        onClick={onToggle}
        aria-label={buttonLabel}
        aria-expanded={open}
        aria-haspopup="dialog"
      >
        <svg viewBox="0 0 24 24" fill="none" className={iconClassName} aria-hidden="true">
          <path d="M12 3a5 5 0 0 0-5 5v2.5c0 .7-.2 1.4-.6 2L5 15h14l-1.4-2.5a4.1 4.1 0 0 1-.6-2V8a5 5 0 0 0-5-5Z" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
          <path d="M10 18a2 2 0 0 0 4 0" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
        </svg>
        {unreadCount > 0 ? <span className={styles.badge} aria-hidden="true">{badge}</span> : null}
      </button>

      {open ? (
        <section className={styles.panel} role="dialog" aria-label={t('notifications.title')}>
          <header className={styles.head}>
            <h2 className={styles.title}>{t('notifications.title')}</h2>
            <button
              type="button"
              className={styles.markAll}
              onClick={markAllRead}
              disabled={unreadCount === 0}
            >
              {t('notifications.mark_all_read')}
            </button>
          </header>

          {status === 'loading' ? <p className={styles.state}>{t('notifications.loading')}</p> : null}

          {status === 'error' ? (
            <p className={styles.state}>
              {t('notifications.load_error')}{' '}
              <button type="button" className={styles.retry} onClick={loadList}>
                {t('notifications.retry')}
              </button>
            </p>
          ) : null}

          {status === 'ready' && items.length === 0 ? (
            <p className={styles.state}>{t('notifications.empty')}</p>
          ) : null}

          {items.length > 0 ? (
            <ul className={styles.list}>
              {items.map((notification) => {
                const { title, message } = describeNotification(notification, t)
                const unread = !notification.read_at

                return (
                  <li key={notification.id}>
                    <button
                      type="button"
                      className={styles.item}
                      data-unread={unread ? 'true' : 'false'}
                      data-tone={notificationTone(notification.type)}
                      onClick={() => handleSelect(notification)}
                    >
                      <span className={styles.dot} aria-hidden="true" />
                      <span className={styles.body}>
                        <span className={styles.itemTitle}>{title}</span>
                        {message ? <span className={styles.itemMessage}>{message}</span> : null}
                        <time className={styles.time} dateTime={notification.created_at}>
                          {formatNotificationTime(notification.created_at, locale, t)}
                        </time>
                      </span>
                      {unread ? <span className={styles.srOnly}>{t('notifications.unread')}</span> : null}
                    </button>
                  </li>
                )
              })}
            </ul>
          ) : null}
        </section>
      ) : null}
    </>
  )
}
