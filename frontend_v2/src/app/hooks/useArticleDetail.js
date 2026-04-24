import { useCallback } from 'react'
import { getArticleBySlug } from '../api/articles.js'
import { useApiResource } from './useApiResource.js'

export function useArticleDetail(slug) {
  const fetchArticleDetail = useCallback(
    ({ slug: nextSlug }, { signal }) => getArticleBySlug(nextSlug, { signal }),
    [],
  )

  return useApiResource({
    fetcher: fetchArticleDetail,
    params: { slug },
    enabled: Boolean(slug),
    deps: [slug],
  })
}
