import { useCallback } from 'react'
import { getArticleBySlug } from '../api/articles.js'
import { useApiResource } from './useApiResource.js'
import { useAuth } from './useAuth.js'

export function useArticleDetail(slug) {
  const { token } = useAuth()
  const fetchArticleDetail = useCallback(
    ({ slug: nextSlug }, { signal }) => getArticleBySlug(nextSlug, { token, signal }),
    [token],
  )

  return useApiResource({
    fetcher: fetchArticleDetail,
    params: { slug },
    enabled: Boolean(slug),
    deps: [slug, token],
  })
}
