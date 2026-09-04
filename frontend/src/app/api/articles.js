import { apiGet, authApiGet } from './client.js'

export async function listArticleCategories({ signal } = {}) {
  const { data } = await apiGet('/api/v1/article-categories', { signal })
  const categories = Array.isArray(data?.categories) ? data.categories : []
  return { categories }
}

export async function listArticles(
  { page = 1, perPage = 10, articleCategoryId = null } = {},
  { signal } = {},
) {
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  params.set('page', String(page))
  if (articleCategoryId) {
    params.set('article_category_id', String(articleCategoryId))
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
