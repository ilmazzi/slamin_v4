import { useEffect, useMemo, useState } from 'react'
import { listArticleCategories } from '../api/articles.js'

export function useArticleCategoryLookup({ categorySlug, t }) {
  const [categories, setCategories] = useState([])
  const [isLoadingLookups, setIsLoadingLookups] = useState(true)
  const [lookupsError, setLookupsError] = useState('')

  useEffect(() => {
    const controller = new AbortController()

    async function loadCategories() {
      setIsLoadingLookups(true)
      setLookupsError('')

      try {
        const { categories: nextCategories } = await listArticleCategories({ signal: controller.signal })
        setCategories(nextCategories)
      } catch (error) {
        if (error?.name !== 'AbortError') {
          setCategories([])
          setLookupsError(t('articles.filters.load_error'))
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoadingLookups(false)
        }
      }
    }

    loadCategories()
    return () => controller.abort()
  }, [t])

  const categoryIdForFilter = useMemo(() => {
    if (!categorySlug) {
      return null
    }

    const found = categories.find((category) => category?.slug === categorySlug)
    return found?.id ?? null
  }, [categories, categorySlug])

  const categoryOptions = useMemo(
    () =>
      categories.map((category) => ({
        value: category?.slug ?? '',
        label: category?.name ?? '',
      })),
    [categories],
  )

  return {
    categories,
    categoryOptions,
    categoryIdForFilter,
    isLoadingLookups,
    lookupsError,
  }
}
