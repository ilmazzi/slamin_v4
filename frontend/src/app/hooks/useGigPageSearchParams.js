import { useCallback } from 'react'
import { useSearchParams } from 'react-router-dom'

export const GIG_SORTS = ['newest', 'oldest', 'deadline']
export const GIG_TYPES = ['poet_artist', 'mc_host', 'technical_support', 'volunteer', 'translator']

function parsePage(value) {
  return Math.max(1, Math.floor(Number.parseInt(value ?? '1', 10)) || 1)
}

export function useGigPageSearchParams() {
  const [searchParams, setSearchParams] = useSearchParams()

  const search = searchParams.get('q') ?? ''
  const rawType = searchParams.get('type') ?? ''
  const type = GIG_TYPES.includes(rawType) ? rawType : ''
  const rawSort = searchParams.get('sort') ?? ''
  const sort = GIG_SORTS.includes(rawSort) ? rawSort : 'newest'
  const currentPage = parsePage(searchParams.get('page'))

  const update = useCallback((key, value, { resetPage = true } = {}) => {
    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev)
        const normalized = String(value ?? '').trim()
        if (normalized && !(key === 'sort' && normalized === 'newest')) {
          next.set(key, normalized)
        } else {
          next.delete(key)
        }
        if (resetPage) {
          next.delete('page')
        }
        return next
      },
      { replace: true },
    )
  }, [setSearchParams])

  const setSearch = useCallback((value) => update('q', value), [update])
  const setType = useCallback((value) => update('type', value), [update])
  const setSort = useCallback((value) => update('sort', value), [update])
  const goToPage = useCallback((page) => update('page', page > 1 ? page : '', { resetPage: false }), [update])

  return { search, type, sort, currentPage, setSearch, setType, setSort, goToPage }
}
