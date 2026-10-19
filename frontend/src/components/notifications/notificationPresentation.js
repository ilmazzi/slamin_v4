const APPLICATION_STATUSES = new Set(['pending', 'accepted', 'rejected', 'withdrawn', 'confirmed'])

const STATIC_TYPES = new Set([
  'gig.negotiation_opened',
  'gig.negotiation_event_created',
  'gig.negotiation_agreed',
  'translation_job.submitted',
  'translation_job.changes_requested',
  'translation_job.approved',
  'translation_comment.created',
])

const COMMENTABLE_PATHS = {
  poem: '/poems',
  event: '/events',
  article: '/articles',
}

function typeKey(type) {
  return String(type).replace(/\./g, '_')
}

export function describeNotification(notification, t) {
  const type = notification?.type ?? ''
  const data = notification?.data ?? {}
  const stored = { title: notification?.title ?? '', message: notification?.message ?? '' }

  if (type === 'gig.application_received' && data.gig_title) {
    return {
      title: t('notifications.types.gig_application_received.title'),
      message: t('notifications.types.gig_application_received.message', {
        name: data.applicant_name || t('notifications.someone'),
        title: data.gig_title,
      }),
    }
  }

  if (type === 'gig.application_updated' && data.gig_title && APPLICATION_STATUSES.has(data.status)) {
    return {
      title: t('notifications.types.gig_application_updated.title'),
      message: t(`notifications.types.gig_application_updated.${data.status}`, { title: data.gig_title }),
    }
  }

  if (STATIC_TYPES.has(type)) {
    return {
      title: t(`notifications.types.${typeKey(type)}.title`),
      message: t(`notifications.types.${typeKey(type)}.message`),
    }
  }

  return stored
}

export function notificationTone(type) {
  const value = String(type ?? '')
  if (value.startsWith('gig.') || value.startsWith('translation_')) {
    return 'gigs'
  }
  if (value.startsWith('event')) {
    return 'event'
  }
  return 'default'
}

export function notificationLink(notification) {
  const data = notification?.data ?? {}

  if (data.translation_job_id) {
    return `/translation-jobs/${data.translation_job_id}`
  }
  if (data.negotiation_id) {
    return `/gig-negotiations/${data.negotiation_id}`
  }
  if (data.gig_id) {
    return `/gigs/${data.gig_id}`
  }
  const commentablePath = COMMENTABLE_PATHS[data.commentable_type]
  if (commentablePath && data.commentable_slug) {
    return `${commentablePath}/${encodeURIComponent(data.commentable_slug)}`
  }
  if (data.commentable_type === 'media' && data.commentable_id) {
    return `/media/${data.commentable_id}`
  }
  if (String(notification?.type ?? '').startsWith('group_') && data.group_slug) {
    return `/groups/${encodeURIComponent(data.group_slug)}`
  }
  if (data.event_slug) {
    return `/events/${encodeURIComponent(data.event_slug)}`
  }
  if (data.poem_slug) {
    return `/poems/${encodeURIComponent(data.poem_slug)}`
  }
  if (data.article_slug) {
    return `/articles/${encodeURIComponent(data.article_slug)}`
  }
  if (data.media_item_id) {
    return `/media/${data.media_item_id}`
  }
  if (data.follower_nickname) {
    return `/u/${encodeURIComponent(data.follower_nickname)}`
  }

  return null
}

const RELATIVE_UNITS = [
  ['year', 31536000],
  ['month', 2592000],
  ['week', 604800],
  ['day', 86400],
  ['hour', 3600],
  ['minute', 60],
]

export function formatNotificationTime(value, locale, t) {
  const time = value ? new Date(value).getTime() : Number.NaN
  if (Number.isNaN(time)) {
    return ''
  }

  const seconds = Math.round((time - Date.now()) / 1000)
  if (Math.abs(seconds) < 60) {
    return t('notifications.just_now')
  }

  const formatter = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })
  const [unit, size] = RELATIVE_UNITS.find(([, unitSeconds]) => Math.abs(seconds) >= unitSeconds)
  return formatter.format(Math.round(seconds / size), unit)
}
