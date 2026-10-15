import { useCallback, useEffect, useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { listChatThreads } from '../../app/api/chat.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useRealtimeConfig } from '../../app/hooks/useRealtimeConfig.js'
import { acquireEcho, isReverbConfigured, releaseEcho } from '../../app/realtime/echo.js'
import SlaminMark from '../brand/SlaminMark.jsx'
import styles from './ChatWidget.module.css'

function isHiddenPath(pathname) {
  return ['/chat', '/login', '/register', '/forgot-password', '/verify-email'].some(
    (path) => pathname === path || pathname.startsWith(`${path}/`),
  )
}

export default function ChatWidget() {
  const { t } = useI18n()
  const { pathname } = useLocation()
  const { isAuthenticated, token, user } = useAuth()
  const realtime = useRealtimeConfig(isAuthenticated ? token : null)
  const [unread, setUnread] = useState(0)
  const hidden = isHiddenPath(pathname)

  const refreshUnread = useCallback((signal) => {
    if (!token) {
      return Promise.resolve()
    }

    return listChatThreads({ token, signal }).then((threads) => {
      setUnread(threads.reduce((sum, thread) => sum + Number(thread.unread_count || 0), 0))
    })
  }, [token])

  useEffect(() => {
    if (hidden || !isAuthenticated || !token) {
      return undefined
    }

    const controller = new AbortController()
    refreshUnread(controller.signal).catch(() => null)
    return () => controller.abort()
  }, [hidden, isAuthenticated, refreshUnread, token])

  useEffect(() => {
    if (hidden || !isAuthenticated || !token || !user?.id || !isReverbConfigured(realtime)) {
      return undefined
    }

    const echo = acquireEcho(token, realtime)
    if (!echo) {
      return undefined
    }

    const channel = echo.private(`chat.user.${user.id}`)
    const handleMessage = (payload) => {
      const authorId = Number(payload?.message?.author?.id ?? payload?.message?.user_id ?? 0)
      if (!authorId || authorId !== Number(user.id)) {
        setUnread((count) => count + 1)
      }
      refreshUnread().catch(() => null)
    }
    channel.listen('.message.sent', handleMessage)

    return () => {
      channel.stopListening('.message.sent', handleMessage)
      releaseEcho(echo)
    }
  }, [hidden, isAuthenticated, realtime, refreshUnread, token, user?.id])

  if (hidden) {
    return null
  }

  return (
    <Link to="/chat" className={styles.widget} aria-label={t('chat.ui.open')}>
      <SlaminMark className={styles.mark} />
      <span className={styles.label}>{t('chat.ui.title')}</span>
      {unread > 0 ? (
        <span className={styles.badge}>{unread > 99 ? '99+' : unread}</span>
      ) : null}
    </Link>
  )
}
