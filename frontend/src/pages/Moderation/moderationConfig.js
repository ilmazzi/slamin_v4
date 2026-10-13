export const QUEUE_TYPES = {
  poems: {
    apiType: 'poems',
    labelKey: 'moderation.ui.queue_poems',
    pendingKey: 'poems',
    publicHref: (item) => (item?.slug ? `/poems/${encodeURIComponent(item.slug)}` : null),
    preview: (item) => item?.content || item?.description || '',
  },
  articles: {
    apiType: 'articles',
    labelKey: 'moderation.ui.queue_articles',
    pendingKey: 'articles',
    publicHref: (item) => (item?.slug ? `/articles/${encodeURIComponent(item.slug)}` : null),
    preview: (item) => item?.excerpt || item?.content || '',
  },
  events: {
    apiType: 'events',
    labelKey: 'moderation.ui.queue_events',
    pendingKey: 'events',
    publicHref: (item) => (item?.slug ? `/events/${encodeURIComponent(item.slug)}` : null),
    preview: (item) => item?.description || item?.subtitle || '',
  },
}

export const QUEUE_STATUSES = ['pending_review', 'rejected', 'published', 'draft']
export const REPORT_STATUSES = ['open', 'in_review', 'resolved', 'dismissed']
export const REPORT_TYPES = ['poem', 'article', 'event', 'media', 'comment']

export function isQueueType(type) {
  return Boolean(QUEUE_TYPES[type])
}

export function authorLabel(item, fallback = '—') {
  return item?.author?.display_name || item?.author?.nickname || fallback
}

export function reporterLabel(report, fallback = '—') {
  return report?.reporter?.display_name || report?.reporter?.nickname || fallback
}

export function reportableHref(reportable) {
  if (!reportable) {
    return null
  }
  if (reportable.type === 'poem' && reportable.slug) {
    return `/poems/${encodeURIComponent(reportable.slug)}`
  }
  if (reportable.type === 'article' && reportable.slug) {
    return `/articles/${encodeURIComponent(reportable.slug)}`
  }
  if (reportable.type === 'event' && reportable.slug) {
    return `/events/${encodeURIComponent(reportable.slug)}`
  }
  if (reportable.type === 'media' && reportable.id) {
    return `/media/${encodeURIComponent(reportable.id)}`
  }
  return null
}

export function reportableTitle(reportable, fallback = '—') {
  if (!reportable) {
    return fallback
  }
  return reportable.title || reportable.content || fallback
}
