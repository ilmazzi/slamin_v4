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

    const prevPaginationLabel =
      t('articles.pagination.prev') === 'articles.pagination.prev'
        ? 'Pagina precedente'
        : t('articles.pagination.prev')

    const nextPaginationLabel =
      t('articles.pagination.next') === 'articles.pagination.next'
        ? 'Pagina successiva'
        : t('articles.pagination.next')

    if (mode === 'page') {
      const paginationInfoTemplate =
        t('articles.pagination.page') === 'articles.pagination.page'
          ? 'Pagina :current di :last'
          : t('articles.pagination.page')

      return {
        currentPage,
        lastPage,
        total,
        perPage,
        prevPaginationLabel,
        nextPaginationLabel,
        paginationInfo: paginationInfoTemplate
          .replace(':current', String(currentPage))
          .replace(':last', String(lastPage)),
      }
    }

    const pageStart = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1
    const pageEnd = total === 0 ? 0 : Math.min(total, pageStart + Math.max(0, itemCount - 1))
    const paginationInfoTemplate =
      t('articles.pagination.results') === 'articles.pagination.results'
        ? 'Mostra :from-:to di :total risultati'
        : t('articles.pagination.results')

    return {
      currentPage,
      lastPage,
      total,
      perPage,
      prevPaginationLabel,
      nextPaginationLabel,
      paginationInfo: paginationInfoTemplate
        .replace(':from', String(pageStart))
        .replace(':to', String(pageEnd))
        .replace(':total', String(total)),
    }
  }, [fallbackPage, fallbackPerPage, itemCount, meta, mode, t])
}
