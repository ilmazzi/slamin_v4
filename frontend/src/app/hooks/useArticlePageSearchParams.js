import { useSearchParams } from 'react-router-dom'

function parsePage(value) {
  return Math.max(1, Math.floor(Number.parseInt(value ?? '1', 10)) || 1)
}

export function useArticlePageSearchParams() {
  const [searchParams, setSearchParams] = useSearchParams()

  const categorySlug = searchParams.get('category') ?? ''
  const currentPage = parsePage(searchParams.get('page'))

  function setCategory(value) {
    const next = new URLSearchParams(searchParams)
    const normalizedValue = String(value ?? '').trim()

    if (normalizedValue) {
      next.set('category', normalizedValue)
    } else {
      next.delete('category')
    }

    next.set('page', '1')
    setSearchParams(next, { replace: true })
  }

  function resetFilters() {
    const next = new URLSearchParams()
    next.set('page', '1')
    setSearchParams(next, { replace: true })
  }

  function goToPage(page) {
    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev)
        next.set('page', String(page))
        return next
      },
      { replace: true },
    )

    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }
  }

  return {
    categorySlug,
    currentPage,
    searchParams,
    setSearchParams,
    setCategory,
    resetFilters,
    goToPage,
  }
}
