import { useMemo } from 'react'

export function usePaginationUi({
  meta,
  fallbackPage = 1,
  fallbackPerPage = 1,
  itemCount = 0,
  t,
  mode = 'results',
}) {
  return useMemo(() => {
    const currentPage = Math.max(1, Number(meta?.current_page ?? fallbackPage))
    const lastPage = Math.max(1, Number(meta?.last_page ?? 1))
    const total = Math.max(0, Number(meta?.total ?? itemCount))
    const perPage = Math.max(1, Number(meta?.per_page ?? fallbackPerPage))

    const prevPaginationLabel = t('articles.pagination.prev_page')
    const nextPaginationLabel = t('articles.pagination.next_page')

    if (mode === 'page') {
      return {
        currentPage,
        lastPage,
        total,
        perPage,
        prevPaginationLabel,
        nextPaginationLabel,
        paginationInfo: t('articles.pagination.page', { current: currentPage, last: lastPage }),
      }
    }

    const pageStart = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1
    const pageEnd = total === 0 ? 0 : Math.min(total, pageStart + Math.max(0, itemCount - 1))

    return {
      currentPage,
      lastPage,
      total,
      perPage,
      prevPaginationLabel,
      nextPaginationLabel,
      paginationInfo: t('articles.pagination.results', { from: pageStart, to: pageEnd, total }),
    }
  }, [fallbackPage, fallbackPerPage, itemCount, meta, mode, t])
}
