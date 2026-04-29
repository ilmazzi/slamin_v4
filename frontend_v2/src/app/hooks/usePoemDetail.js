import { useCallback } from 'react'
import { getPoemBySlug } from '../api/poems.js'
import { useApiResource } from './useApiResource.js'
import { useAuth } from './useAuth.js'

export function usePoemDetail(slug) {
  const { token } = useAuth()
  const fetchPoemDetail = useCallback(
    ({ slug: nextSlug }, { signal }) => getPoemBySlug(nextSlug, { token, signal }),
    [token],
  )

  return useApiResource({
    fetcher: fetchPoemDetail,
    params: { slug },
    enabled: Boolean(slug),
    deps: [slug, token],
  })
}
