import { authApiGet, authApiPatch, authApiPost } from './client.js'

const ITEM_KEY = {
  poems: 'poem',
  articles: 'article',
  events: 'event',
}

export async function getModerationDashboard({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/moderation/dashboard', {
    token,
    signal,
    fallbackMessage: 'Unable to load moderation dashboard',
  })

  return data?.stats ?? {}
}

export async function listModerationQueue(type, {
  token,
  signal,
  page = 1,
  perPage = 20,
  status = '',
  q = '',
} = {}) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  if (status) {
    params.set('status', status)
  }
  if (q) {
    params.set('q', q)
  }

  const { data, meta } = await authApiGet(`/api/v1/moderation/${type}?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load moderation queue',
  })

  return {
    items: Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []),
    meta,
  }
}

export async function showModerationItem(type, id, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/moderation/${type}/${encodeURIComponent(id)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load item for review',
  })

  const itemKey = ITEM_KEY[type]
  return {
    item: data?.[itemKey] ?? null,
    history: Array.isArray(data?.moderation_history) ? data.moderation_history : [],
  }
}

export async function approveModerationItem(type, id, { token, note = null } = {}) {
  const payload = note ? { note } : {}
  const { data } = await authApiPost(
    `/api/v1/moderation/${type}/${encodeURIComponent(id)}/approve`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to approve item',
    },
  )

  return data?.[ITEM_KEY[type]] ?? null
}

export async function rejectModerationItem(type, id, { token, note } = {}) {
  const { data } = await authApiPost(
    `/api/v1/moderation/${type}/${encodeURIComponent(id)}/reject`,
    { note },
    {
      token,
      fallbackMessage: 'Unable to reject item',
    },
  )

  return data?.[ITEM_KEY[type]] ?? null
}

export async function listModerationReports({
  token,
  signal,
  page = 1,
  perPage = 20,
  status = '',
  reportableType = '',
} = {}) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  if (status) {
    params.set('status', status)
  }
  if (reportableType) {
    params.set('reportable_type', reportableType)
  }

  const { data, meta } = await authApiGet(`/api/v1/moderation/reports?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load reports',
  })

  return {
    items: Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []),
    meta,
  }
}

export async function showModerationReport(id, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/moderation/reports/${encodeURIComponent(id)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load report',
  })

  return data?.report ?? null
}

export async function updateModerationReport(id, payload, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/moderation/reports/${encodeURIComponent(id)}`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to update report',
    },
  )

  return data?.report ?? null
}
