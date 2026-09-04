import styles from './AllPoemsSection.module.css'
import PoemTeaserCard from '../shared/PoemTeaserCard.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { listPoems } from '../../app/api/poems.js'
import { useState } from 'react'
import Pagination from '../shared/Pagination.jsx'

const MAX_PER_PAGE = 5


export default function AllPoemsSection() {


  const { t, locale } = useI18n()
  const [titlePrefix = '', ...titleRest] = t('poems.all.title').split(' ')
  const titleEmphasis = titleRest.join(' ')
  const [page, setPage] = useState(1)
  const {
    items: poems,
    isLoading,
    error,
    meta,
  } = usePaginatedApiList({
    fetcher: listPoems,
    params: {
      page,
      perPage: MAX_PER_PAGE,
    },
    deps: [page],
  })
  const currentPage = meta?.current_page ?? page
  const lastPage = meta?.last_page ?? 1
  const allTitle = t('poems.all.title') === 'poems.all.title'
    ? 'All Poems'
    : t('poems.all.title')
  const loadingLabel = t('common.loading') === 'common.loading' ? 'Loading...' : t('common.loading')
  const pageLabelTemplate =
    t('poems.pagination.page') === 'poems.pagination.page'
      ? 'Pagina :current di :last'
      : t('poems.pagination.page')
  const paginationInfo = pageLabelTemplate
    .replace(':current', String(currentPage))
    .replace(':last', String(lastPage))
  const prevPaginationLabel =
    t('poems.pagination.prev') === 'poems.pagination.prev'
      ? 'Pagina precedente'
      : t('poems.pagination.prev')
  const nextPaginationLabel =
    t('poems.pagination.next') === 'poems.pagination.next'
      ? 'Pagina successiva'
      : t('poems.pagination.next')

  if (isLoading) {
    return (
      <section className={styles.section}>
        <h1 className={styles.title}>{allTitle}</h1>
        <p>{loadingLabel}</p>
      </section>
    )
  }

  if (error) {
    return (
      <section className={styles.section}>
        <h1 className={styles.title}>{allTitle}</h1>
        <p>{error}</p>
      </section>
    )
  }



  if (!poems?.length) {
    return (
      <section className={styles.section}>
        <header className={styles.header}>
          <h2 className={styles.title}>
            <span className={styles.titlePrefix}>{titlePrefix} </span>
            <span className={styles.titleEmphasis}>{titleEmphasis}</span>
          </h2>
        </header>
        <p className={styles.empty}>{t('poems.all.empty')}</p>
      </section>
    )
  }

  return (
    <section className={styles.section}>
      <header className={styles.header}>
        <h2 className={styles.title}>
          <span className={styles.titlePrefix}>{titlePrefix} </span>
          <span className={styles.titleEmphasis}>{titleEmphasis}</span>
        </h2>
      </header>

      <div className={styles.grid}>
        {poems.map((poem) => (
          <PoemTeaserCard
            key={poem.id ?? poem.slug}
            poem={poem}
            locale={locale}
            t={t}
            href="/poems"
          />
        ))}
      </div>
      <Pagination
  currentPage={currentPage}
  lastPage={lastPage}
  onPageChange={setPage}
  prevAriaLabel={prevPaginationLabel}
  nextAriaLabel={nextPaginationLabel}
  infoText={paginationInfo}
/>
    </section>
  )
}
