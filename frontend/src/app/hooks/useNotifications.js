import { useCallback, useEffect, useRef, useState } from 'react'
import {
  getUnreadNotificationsCount,
  listNotifications,
  markAllNotificationsAsRead,
  markNotificationAsRead,
} from '../api/notifications.js'
import { acquireEcho, isReverbConfigured, releaseEcho } from '../realtime/echo.js'
import { useRealtimeConfig } from './useRealtimeConfig.js'

const LIST_SIZE = 10
const FALLBACK_POLL_MS = 60000

export function useNotifications({ token, userId, enabled = true }) {
  const active = Boolean(enabled && token && userId)
  const realtime = useRealtimeConfig(active ? token : null)
  const [unreadCount, setUnreadCount] = useState(0)
  const [items, setItems] = useState([])
  const [status, setStatus] = useState('idle')
  const listLoadedRef = useRef(false)
  const listRequestRef = useRef(null)

  const refreshCount = useCallback((signal) => (
    getUnreadNotificationsCount({ token, signal }).then(setUnreadCount)
  ), [token])

  const loadList = useCallback(async () => {
    listRequestRef.current?.abort()
    const controller = new AbortController()
    listRequestRef.current = controller
    setStatus((current) => (current === 'ready' ? current : 'loading'))

    try {
      const result = await listNotifications({ perPage: LIST_SIZE, unreadOnly: true }, { token, signal: controller.signal })
      setItems(result.items)
      listLoadedRef.current = true
      setStatus('ready')
    } catch {
      if (!controller.signal.aborted) {
        setStatus('error')
      }
    }
  }, [token])

  useEffect(() => {
    listLoadedRef.current = false

    if (!active) {
      return undefined
    }

    const controller = new AbortController()
    refreshCount(controller.signal).catch(() => null)

    const handleVisibility = () => {
      if (document.visibilityState === 'visible') {
        refreshCount().catch(() => null)
      }
    }
    document.addEventListener('visibilitychange', handleVisibility)

    return () => {
      controller.abort()
      listRequestRef.current?.abort()
      document.removeEventListener('visibilitychange', handleVisibility)
    }
  }, [active, refreshCount])

  useEffect(() => {
    if (!active || realtime === undefined || isReverbConfigured(realtime)) {
      return undefined
    }

    const timer = window.setInterval(() => {
      if (document.visibilityState === 'visible') {
        refreshCount().catch(() => null)
      }
    }, FALLBACK_POLL_MS)

    return () => window.clearInterval(timer)
  }, [active, realtime, refreshCount])

  useEffect(() => {
    if (!active || !isReverbConfigured(realtime)) {
      return undefined
    }

    const echo = acquireEcho(token, realtime)
    if (!echo) {
      return undefined
    }

    const channel = echo.private(`notifications.${userId}`)
    const handleCreated = (payload) => {
      const notification = payload?.notification
      if (!notification?.id) {
        return
      }

      setUnreadCount((count) => count + 1)
      if (listLoadedRef.current) {
        setItems((current) => [
          notification,
          ...current.filter((item) => item.id !== notification.id),
        ].slice(0, LIST_SIZE))
      }
    }
    channel.listen('.notification.created', handleCreated)

    return () => {
      channel.stopListening('.notification.created', handleCreated)
      releaseEcho(echo)
    }
  }, [active, realtime, token, userId])

  const markRead = useCallback(async (notification) => {
    if (!notification?.id || notification.read_at) {
      return
    }

    setItems((current) => current.filter((item) => item.id !== notification.id))
    setUnreadCount((count) => Math.max(0, count - 1))

    try {
      await markNotificationAsRead(notification.id, { token })
    } catch {
      refreshCount().catch(() => null)
      loadList()
    }
  }, [loadList, refreshCount, token])

  const markAllRead = useCallback(async () => {
    setItems([])
    setUnreadCount(0)

    try {
      await markAllNotificationsAsRead({ token })
    } catch {
      refreshCount().catch(() => null)
      loadList()
    }
  }, [loadList, refreshCount, token])

  return { unreadCount, items, status, loadList, markRead, markAllRead }
}
