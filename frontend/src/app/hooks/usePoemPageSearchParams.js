import { useCallback } from 'react'
import { useSearchParams } from 'react-router-dom'

function parsePage(value) {
  return Math.max(1, Math.floor(Number.parseInt(value ?? '1', 10)) || 1)
}

function setOrDelete(params, key, value) {
  const normalized = String(value ?? '').trim()
  if (normalized) {
    params.set(key, normalized)
  } else {
    params.delete(key)
  }
}

export function usePoemPageSearchParams() {
  const [searchParams, setSearchParams] = useSearchParams()

  const search = searchParams.get('q') ?? ''
  const categorySlug = searchParams.get('category') ?? ''
  const language = searchParams.get('language') ?? ''
  const sort = searchParams.get('sort') || 'newest'
  const currentPage = parsePage(searchParams.get('page'))

  const updateParams = useCallback((mutator, { resetPage = false } = {}) => {
    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev)
        mutator(next)
        if (resetPage) {
          next.set('page', '1')
        }
        return next
      },
      { replace: true },
    )
  }, [setSearchParams])

  const setSearch = useCallback((value) => {
    updateParams((next) => setOrDelete(next, 'q', value), { resetPage: true })
  }, [updateParams])

  const setCategory = useCallback((value) => {
    updateParams((next) => setOrDelete(next, 'category', value), { resetPage: true })
  }, [updateParams])

  const setLanguage = useCallback((value) => {
    updateParams((next) => setOrDelete(next, 'language', value), { resetPage: true })
  }, [updateParams])

  const setSort = useCallback((value) => {
    updateParams((next) => {
      const normalized = String(value ?? '').trim() || 'newest'
      if (normalized === 'newest') {
        next.delete('sort')
      } else {
        next.set('sort', normalized)
      }
    }, { resetPage: true })
  }, [updateParams])

  const goToPage = useCallback((page) => {
    updateParams((next) => next.set('page', String(page)))
  }, [updateParams])

  return {
    search,
    categorySlug,
    language,
    sort,
    currentPage,
    setSearch,
    setCategory,
    setLanguage,
    setSort,
    goToPage,
  }
}
