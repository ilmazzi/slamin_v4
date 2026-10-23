import { apiGet, authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

export async function listForumCategories({ signal } = {}) {
  const { data } = await apiGet('/api/v1/forum/categories', {
    signal,
    fallbackMessage: 'Unable to load forum categories',
  })

  return Array.isArray(data?.categories) ? data.categories : []
}

export async function getForumCategory(slug, { signal } = {}) {
  const { data } = await apiGet(`/api/v1/forum/categories/${encodeURIComponent(slug)}`, {
    signal,
    fallbackMessage: 'Unable to load forum category',
  })

  return data?.category ?? null
}

export async function listForumPosts(
  { page = 1, perPage = 20, category = '', sort = 'new' } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  params.set('sort', sort || 'new')
  if (category) params.set('category', category)

  const { data, meta } = await apiGet(`/api/v1/forum/posts?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load forum posts',
  })

  return {
    items: Array.isArray(data) ? data : [],
    meta,
  }
}

export async function getForumPost(publicId, { token, signal } = {}) {
  const { data } = await apiGet(`/api/v1/forum/posts/${encodeURIComponent(publicId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load forum post',
  })

  return data?.post ?? null
}

export async function getForumPostBySlug(categorySlug, postSlug, { token, signal } = {}) {
  const { data } = await apiGet(
    `/api/v1/forum/categories/${encodeURIComponent(categorySlug)}/posts/${encodeURIComponent(postSlug)}`,
    {
      token,
      signal,
      fallbackMessage: 'Unable to load forum post',
    },
  )

  return data?.post ?? null
}

export async function createForumPost(payload, { token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/forum/posts', payload, {
    token,
    signal,
    fallbackMessage: 'Unable to publish forum post',
  })

  return data?.post ?? null
}

export async function updateForumPost(publicId, payload, { token, signal } = {}) {
  const { data } = await authApiPatch(`/api/v1/forum/posts/${encodeURIComponent(publicId)}`, payload, {
    token,
    signal,
    fallbackMessage: 'Unable to update forum post',
  })

  return data?.post ?? null
}

export async function deleteForumPost(publicId, { token, signal } = {}) {
  await authApiDelete(`/api/v1/forum/posts/${encodeURIComponent(publicId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to delete forum post',
  })
}

export async function voteForumPost(publicId, value, { token, signal } = {}) {
  const { data } = await authApiPost(
    `/api/v1/forum/posts/${encodeURIComponent(publicId)}/vote`,
    { value },
    {
      token,
      signal,
      fallbackMessage: 'Unable to save vote',
    },
  )

  return data?.post ?? null
}

export async function voteForumComment(commentId, value, { token, signal } = {}) {
  const { data } = await authApiPost(
    `/api/v1/comments/${encodeURIComponent(commentId)}/vote`,
    { value },
    {
      token,
      signal,
      fallbackMessage: 'Unable to save vote',
    },
  )

  return data?.comment ?? null
}

export async function adminListForumCategories({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/forum-categories', {
    token,
    signal,
    fallbackMessage: 'Unable to load forum categories',
  })

  return Array.isArray(data?.categories) ? data.categories : []
}

export async function adminStoreForumCategory(payload, { token } = {}) {
  const { data } = await authApiPost('/api/v1/admin/forum-categories', payload, {
    token,
    fallbackMessage: 'Unable to save forum category',
  })

  return data?.category ?? null
}

export async function adminUpdateForumCategory(id, payload, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/admin/forum-categories/${encodeURIComponent(id)}`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to save forum category',
    },
  )

  return data?.category ?? null
}

export async function adminDeleteForumCategory(id, { token } = {}) {
  await authApiDelete(`/api/v1/admin/forum-categories/${encodeURIComponent(id)}`, {
    token,
    fallbackMessage: 'Unable to delete forum category',
  })
}
