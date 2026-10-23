import { authApiPost } from './client.js'

export async function createPoemReport(poem, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/poems/${encodeURIComponent(poem)}/reports`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to submit poem report',
  })
}

export async function createArticleReport(article, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/articles/${encodeURIComponent(article)}/reports`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to submit article report',
  })
}

export async function createMediaReport(mediaId, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/media/${encodeURIComponent(mediaId)}/reports`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to submit media report',
  })
}

export async function createEventReport(eventId, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/events/${encodeURIComponent(eventId)}/reports`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to submit event report',
  })
}

export async function createGroupReport(group, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/groups/${encodeURIComponent(group)}/reports`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to submit group report',
  })
}

export async function createForumPostReport(postPublicId, payload, { token, signal } = {}) {
  return authApiPost(`/api/v1/forum/posts/${encodeURIComponent(postPublicId)}/reports`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to submit forum post report',
  })
}
