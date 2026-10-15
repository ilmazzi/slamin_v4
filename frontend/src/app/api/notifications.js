import { authApiGet, authApiPatch, authApiPut } from './client.js'

export async function getUnreadNotificationsCount({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/notifications/unread-count', {
    token,
    signal,
    fallbackMessage: 'Unable to load notifications',
  })

  return Number(data?.unread_count ?? 0)
}

export async function listNotifications({ perPage = 10, unreadOnly = false } = {}, { token, signal } = {}) {
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  if (unreadOnly) {
    params.set('unread_only', '1')
  }

  const { data, meta } = await authApiGet(`/api/v1/notifications?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load notifications',
  })

  return { items: Array.isArray(data) ? data : [], meta }
}

export async function markNotificationAsRead(notificationId, { token } = {}) {
  const { data } = await authApiPatch(`/api/v1/notifications/${encodeURIComponent(notificationId)}/read`, {}, {
    token,
    fallbackMessage: 'Unable to update notification',
  })

  return data?.notification ?? null
}

export async function markAllNotificationsAsRead({ token } = {}) {
  await authApiPatch('/api/v1/notifications/read-all', {}, {
    token,
    fallbackMessage: 'Unable to update notifications',
  })
}

export async function getNotificationEmailPreferences({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/me/notification-preferences', {
    token,
    signal,
    fallbackMessage: 'Unable to load notification preferences',
  })

  return data?.preferences ?? {}
}

export async function updateNotificationEmailPreferences(preferences, { token } = {}) {
  const { data } = await authApiPut('/api/v1/me/notification-preferences', { preferences }, {
    token,
    fallbackMessage: 'Unable to save notification preferences',
  })

  return data?.preferences ?? {}
}
