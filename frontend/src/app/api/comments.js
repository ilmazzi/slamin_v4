import { apiGet, authApiPost, authApiRequest } from './client.js'

const DEFAULT_PER_PAGE = 10

export async function listPoemComments(
  poem,
  { page = 1, perPage = DEFAULT_PER_PAGE, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  const { data, meta } = await apiGet(`/api/v1/poems/${encodeURIComponent(poem)}/comments?${params.toString()}`, {
    signal,
    fallbackMessage: 'Unable to load poem comments',
  })

  return { data, meta }
}

export async function listArticleComments(
  article,
  { page = 1, perPage = DEFAULT_PER_PAGE, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  const { data, meta } = await apiGet(`/api/v1/articles/${encodeURIComponent(article)}/comments?${params.toString()}`, {
    signal,
    fallbackMessage: 'Unable to load article comments',
  })

  return { data, meta }
}

export async function createPoemComment(poem, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/poems/${encodeURIComponent(poem)}/comments`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to post poem comment',
  })
}

export async function createArticleComment(article, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/articles/${encodeURIComponent(article)}/comments`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to post article comment',
  })
}

export async function listMediaComments(
  mediaId,
  { page = 1, perPage = DEFAULT_PER_PAGE, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  const { data, meta } = await apiGet(`/api/v1/media/${encodeURIComponent(mediaId)}/comments?${params.toString()}`, {
    signal,
    fallbackMessage: 'Unable to load media comments',
  })

  return { data, meta }
}

export async function createMediaComment(mediaId, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/media/${encodeURIComponent(mediaId)}/comments`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to post media comment',
  })
}

export async function listEventComments(
  eventId,
  { page = 1, perPage = DEFAULT_PER_PAGE, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  const { data, meta } = await apiGet(`/api/v1/events/${encodeURIComponent(eventId)}/comments?${params.toString()}`, {
    signal,
    fallbackMessage: 'Unable to load event comments',
  })

  return { data, meta }
}

export async function createEventComment(eventId, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/events/${encodeURIComponent(eventId)}/comments`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to post event comment',
  })
}

export async function updateComment(commentId, payload, { token, signal } = {}) {
  return authApiRequest(`/api/v1/comments/${encodeURIComponent(commentId)}`, {
    token,
    signal,
    method: 'PATCH',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
    fallbackMessage: 'Unable to update comment',
    allowStatuses: [200],
  })
}

export async function deleteComment(commentId, { token, signal } = {}) {
  return authApiRequest(`/api/v1/comments/${encodeURIComponent(commentId)}`, {
    token,
    signal,
    method: 'DELETE',
    headers: {
      Accept: 'application/json',
    },
    fallbackMessage: 'Unable to delete comment',
    allowStatuses: [200],
  })
}
