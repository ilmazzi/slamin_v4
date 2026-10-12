import { apiGet, authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

function asArray(value) {
  if (Array.isArray(value)) {
    return value
  }
  if (Array.isArray(value?.data)) {
    return value.data
  }
  return []
}

export async function listHelp({ signal, q = '', category = '' } = {}) {
  const params = new URLSearchParams()
  if (q) {
    params.set('q', q)
  }
  if (category) {
    params.set('category', category)
  }
  const query = params.toString()
  const { data } = await apiGet(`/api/v1/help${query ? `?${query}` : ''}`, {
    signal,
    fallbackMessage: 'Unable to load help',
  })

  return {
    categories: asArray(data?.categories),
    articles: asArray(data?.articles),
  }
}

export async function adminListHelpCategories({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/help/categories', {
    token,
    signal,
    fallbackMessage: 'Unable to load help categories',
  })

  return asArray(data?.categories)
}

export async function adminStoreHelpCategory(payload, { token } = {}) {
  const { data } = await authApiPost('/api/v1/admin/help/categories', payload, {
    token,
    fallbackMessage: 'Unable to save category',
  })

  return data?.category ?? null
}

export async function adminUpdateHelpCategory(id, payload, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/admin/help/categories/${encodeURIComponent(id)}`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to save category',
    },
  )

  return data?.category ?? null
}

export async function adminDeleteHelpCategory(id, { token } = {}) {
  await authApiDelete(`/api/v1/admin/help/categories/${encodeURIComponent(id)}`, {
    token,
    fallbackMessage: 'Unable to delete category',
  })
}

export async function adminListHelpArticles({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/help/articles', {
    token,
    signal,
    fallbackMessage: 'Unable to load help articles',
  })

  return asArray(data?.articles)
}

export async function adminStoreHelpArticle(payload, { token } = {}) {
  const { data } = await authApiPost('/api/v1/admin/help/articles', payload, {
    token,
    fallbackMessage: 'Unable to save guide',
  })

  return data?.article ?? null
}

export async function adminUpdateHelpArticle(id, payload, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/admin/help/articles/${encodeURIComponent(id)}`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to save guide',
    },
  )

  return data?.article ?? null
}

export async function adminDeleteHelpArticle(id, { token } = {}) {
  await authApiDelete(`/api/v1/admin/help/articles/${encodeURIComponent(id)}`, {
    token,
    fallbackMessage: 'Unable to delete guide',
  })
}
