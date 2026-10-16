import { apiGet, authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

function buildArticlePayload(article) {
  const coverImage = article?.cover_image

  if (!(typeof Blob !== 'undefined' && coverImage instanceof Blob)) {
    return article
  }

  const formData = new FormData()

  Object.entries(article ?? {}).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') {
      return
    }

    if (value instanceof Blob) {
      formData.append(key, value)
      return
    }

    if (typeof value === 'boolean') {
      formData.append(key, value ? '1' : '0')
      return
    }

    formData.append(key, String(value))
  })

  return formData
}

export async function createArticle(article, { token, signal } = {}) {
  const payload = buildArticlePayload(article)
  const { data } = await authApiPost('/api/v1/articles', payload, { token, signal })
  return data?.article ?? null
}

export async function listArticleCategories({ signal } = {}) {
  const { data } = await apiGet('/api/v1/article-categories', { signal })
  const categories = Array.isArray(data?.categories) ? data.categories : []
  return { categories }
}

export async function listArticles(
  { page = 1, perPage = 10, articleCategoryId = null, nickname = '' } = {},
  { signal } = {},
) {
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  params.set('page', String(page))
  if (articleCategoryId) {
    params.set('article_category_id', String(articleCategoryId))
  }
  if (nickname) {
    params.set('nickname', String(nickname))
  }

  const { data, meta } = await apiGet(`/api/v1/articles?${params.toString()}`, { signal })
  const items = Array.isArray(data) ? data : []
  return { items, meta }
}

export async function listFeaturedArticles({ page = 1, perPage = 3 } = {}, { signal } = {}) {
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  params.set('page', String(page))

  const { data, meta } = await apiGet(`/api/v1/featured-articles?${params.toString()}`, { signal })
  const items = Array.isArray(data) ? data : []
  return { items, meta }
}

export async function getArticleBySlug(slug, { token, signal } = {}) {
  const safeSlug = String(slug ?? '').trim()
  if (!safeSlug) {
    return null
  }

  const fetcher = token ? authApiGet : apiGet
  const { data } = await fetcher(`/api/v1/articles/${encodeURIComponent(safeSlug)}`, token
    ? {
        token,
        signal,
        fallbackMessage: 'Unable to load article',
      }
    : {
        signal,
        fallbackMessage: 'Unable to load article',
      })

  return data?.article ?? null
}

export async function listMyArticles(
  { page = 1, perPage = 50 } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  params.set('page', String(page))

  const { data, meta } = await authApiGet(`/api/v1/my/articles?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Failed to fetch my articles',
  })

  return {
    items: Array.isArray(data) ? data : [],
    meta,
  }
}

export async function updateArticle(articleId, article, { token, signal } = {}) {
  const payload = buildArticlePayload(article)
  const path = `/api/v1/articles/${encodeURIComponent(articleId)}`
  const options = { token, signal, fallbackMessage: 'Unable to update article' }
  const { data } = typeof FormData !== 'undefined' && payload instanceof FormData
    ? await authApiPost(path, appendMethodOverride(payload), options)
    : await authApiPatch(path, payload, options)
  return data?.article ?? null
}

function appendMethodOverride(formData) {
  formData.append('_method', 'PATCH')
  return formData
}

export async function deleteArticle(articleId, { token, signal } = {}) {
  await authApiDelete(`/api/v1/articles/${encodeURIComponent(articleId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to delete article',
  })
}
