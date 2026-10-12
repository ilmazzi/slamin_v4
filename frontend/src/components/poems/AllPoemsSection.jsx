import { useMemo, useState } from 'react'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { listPoems } from '../../app/api/poems.js'
import PoemCatalogCard from './PoemCatalogCard.jsx'
import Pagination from '../shared/Pagination.jsx'
import styles from './AllPoemsSection.module.css'

const SLIDE_SIZE = 9
const FETCH_PER_PAGE = 100
const COLUMNS = 3

function chunkItems(items, size) {
  const rows = []
  for (let index = 0; index < items.length; index += size) {
    rows.push(items.slice(index, index + size))
  }
  return rows
}

function ChevronIcon({ direction }) {
  const isPrev = direction === 'prev'
  return (
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d={isPrev ? 'M15 5L8 12L15 19' : 'M9 5L16 12L9 19'}
        stroke="currentColor"
        strokeWidth="2.4"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  )
}

function PoemSlide({ poems, locale, t }) {
  const rows = chunkItems(poems, COLUMNS)

  return (
    <div className={styles.slide}>
      {rows.map((row, rowIndex) => (
        <div
          key={`row-${rowIndex}`}
          className={`${styles.row} ${rowIndex % 2 === 1 ? styles.rowStaggered : ''}`}
        >
          {row.map((poem) => (
            <div key={poem.id ?? poem.slug} className={styles.cardWrap}>
              <PoemCatalogCard poem={poem} locale={locale} t={t} />
            </div>
          ))}
        </div>
      ))}
    </div>
  )
}

export default function AllPoemsSection({
  poems: poemsProp,
  isLoading: loadingProp,
  error: errorProp,
  locale: localeProp,
  t: tProp,
  page = 1,
  lastPage: lastPageProp,
  onPageChange,
  search = '',
  poemCategoryId = null,
  language = '',
  sort = 'newest',
  showHeader = false,
  showPagination = true,
  perPage = SLIDE_SIZE,
}) {
  const i18n = useI18n()
  const t = tProp ?? i18n.t
  const locale = localeProp ?? i18n.locale
  const ownsFetch = poemsProp === undefined
  const [internalPage, setInternalPage] = useState(1)

  const fetched = usePaginatedApiList({
    fetcher: listPoems,
    params: {
      page: 1,
      perPage: FETCH_PER_PAGE,
      poemCategoryId,
      search,
      language,
      sort,
    },
    enabled: ownsFetch,
    deps: [poemCategoryId, search, language, sort, ownsFetch],
  })

  const fetchedPoems = ownsFetch ? fetched.items : poemsProp
  const poems = useMemo(
    () => (Array.isArray(fetchedPoems) ? fetchedPoems : []),
    [fetchedPoems],
  )
  const isLoading = ownsFetch ? fetched.isLoading : Boolean(loadingProp)
  const error = ownsFetch ? fetched.error : errorProp
  const slides = useMemo(() => chunkItems(poems, perPage), [poems, perPage])
  const derivedLastPage = Math.max(1, slides.length)
  const currentPage = ownsFetch
    ? Math.min(internalPage, derivedLastPage)
    : Math.min(Math.max(1, page), lastPageProp || derivedLastPage)
  const lastPage = ownsFetch ? derivedLastPage : Math.max(1, Number(lastPageProp ?? derivedLastPage))
  const handlePageChange = ownsFetch ? setInternalPage : onPageChange
  const [titlePrefix = '', ...titleRest] = t('poems.all.title').split(' ')
  const titleEmphasis = titleRest.join(' ')
  const canGoPrev = currentPage > 1
  const canGoNext = currentPage < lastPage
  const total = ownsFetch ? (fetched.meta?.total ?? poems.length) : poems.length

  function goTo(nextPage) {
    if (typeof handlePageChange !== 'function') {
      return
    }
    const clamped = Math.min(lastPage, Math.max(1, nextPage))
    handlePageChange(clamped)
  }

  if (isLoading) {
    return (
      <section className={styles.section}>
        {showHeader ? <h2 className={styles.title}>{t('poems.all.title')}</h2> : null}
        <p className={styles.empty}>{t('common.loading')}</p>
      </section>
    )
  }

  if (error) {
    return (
      <section className={styles.section}>
        {showHeader ? <h2 className={styles.title}>{t('poems.all.title')}</h2> : null}
        <p className={styles.empty}>{error}</p>
      </section>
    )
  }

  if (!poems.length) {
    return (
      <section className={styles.section}>
        {showHeader ? (
          <header className={styles.header}>
            <h2 className={styles.title}>
              <span className={styles.titlePrefix}>{titlePrefix} </span>
              <span className={styles.titleEmphasis}>{titleEmphasis}</span>
            </h2>
          </header>
        ) : null}
        <p className={styles.empty}>{t('poems.all.empty')}</p>
      </section>
    )
  }

  return (
    <section className={styles.section}>
      {showHeader ? (
        <header className={styles.header}>
          <h2 className={styles.title}>
            <span className={styles.titlePrefix}>{titlePrefix} </span>
            <span className={styles.titleEmphasis}>{titleEmphasis}</span>
          </h2>
        </header>
      ) : null}

      <div className={styles.slider}>
        <button
          type="button"
          className={`${styles.navButton} ${styles.prev}`}
          aria-label={t('poems.pagination.prev_page')}
          onClick={() => goTo(currentPage - 1)}
          disabled={!canGoPrev}
        >
          <ChevronIcon direction="prev" />
        </button>

        <div className={styles.viewport}>
          <div
            className={styles.track}
            style={{ transform: `translateX(-${(currentPage - 1) * 100}%)` }}
          >
            {slides.map((slidePoems, slideIndex) => (
              <PoemSlide
                key={slidePoems[0]?.id ?? `slide-${slideIndex}`}
                poems={slidePoems}
                locale={locale}
                t={t}
              />
            ))}
          </div>
        </div>

        <button
          type="button"
          className={`${styles.navButton} ${styles.next}`}
          aria-label={t('poems.pagination.next_page')}
          onClick={() => goTo(currentPage + 1)}
          disabled={!canGoNext}
        >
          <ChevronIcon direction="next" />
        </button>
      </div>

      {showPagination ? (
        <div className={styles.paginationSlot}>
          <Pagination
            currentPage={currentPage}
            lastPage={lastPage}
            onPageChange={goTo}
            prevAriaLabel={t('poems.pagination.prev_page')}
            nextAriaLabel={t('poems.pagination.next_page')}
            infoText={t('poems.pagination.results', {
              from: poems.length ? ((currentPage - 1) * perPage) + 1 : 0,
              to: Math.min(total, currentPage * perPage),
              total,
            })}
          />
        </div>
      ) : null}
    </section>
  )
}
