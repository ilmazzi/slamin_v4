import { useCallback, useEffect, useState } from 'react'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import GigCard from '../../components/gigs/GigCard.jsx'
import GigsFilterBar from '../../components/gigs/GigsFilterBar.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import styles from './GigPage.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { useGigPageSearchParams } from '../../app/hooks/useGigPageSearchParams.js'
import { listOpenGigs } from '../../app/api/gigs.js'

const PER_PAGE = 10
const SEARCH_DEBOUNCE_MS = 300

export default function GigPage() {
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const { search, type, sort, currentPage, setSearch, setType, setSort, goToPage } = useGigPageSearchParams()
  const [searchInput, setSearchInput] = useState(search)

  useEffect(() => {
    setSearchInput(search)
  }, [search])

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (searchInput.trim() !== search) {
        setSearch(searchInput)
      }
    }, SEARCH_DEBOUNCE_MS)

    return () => window.clearTimeout(timer)
  }, [search, searchInput, setSearch])

  const fetchGigs = useCallback((params, options) => listOpenGigs(params, { ...options, token }), [token])

  const { items: gigs, meta, isLoading, error } = usePaginatedApiList({
    fetcher: fetchGigs,
    params: { page: currentPage, perPage: PER_PAGE, search, type, sort },
    enabled: Boolean(token),
    deps: [currentPage, search, type, sort],
  })

  const total = meta.total
  const lastPage = meta.last_page
  const pageStart = total === 0 ? 0 : ((meta.current_page - 1) * meta.per_page) + 1
  const pageEnd = total === 0 ? 0 : Math.min(total, pageStart + gigs.length - 1)

  useEffect(() => {
    if (!isLoading && !error && currentPage > lastPage) {
      goToPage(lastPage)
    }
  }, [currentPage, error, goToPage, isLoading, lastPage])

  return (
    <MainLayout themeTone="gigs">
      <div className={pageStyles.gigsPage}>
        <PageHero
          title={t('gigs.title')}
          leadPrefix={t('gigs.lead_prefix')}
          leadEmphasis={t('gigs.lead_emphasis')}
          leadSuffix={t('gigs.lead_suffix')}
          hideCta
        />

        <section className={styles.filtersSection}>
          <GigsFilterBar
            searchValue={searchInput}
            onSearchChange={setSearchInput}
            sortValue={sort}
            onSortChange={setSort}
            typeValue={type}
            onTypeChange={setType}
            t={t}
          />
        </section>

        <section className={styles.catalog} aria-busy={isLoading} aria-label={t('gigs.title')}>
          {error ? <p className={styles.state} role="alert">{error}</p> : null}
          {!error && isLoading && gigs.length === 0 ? <p className={styles.state}>{t('gigs.list.loading')}</p> : null}
          {!error && !isLoading && gigs.length === 0 ? <p className={styles.state}>{t('gigs.list.empty')}</p> : null}
          {gigs.length > 0 ? (
            <div className={[styles.grid, isLoading ? styles.gridLoading : ''].join(' ')}>
              {gigs.map((gig) => (
                <GigCard key={gig.id} gig={gig} locale={locale} t={t} />
              ))}
            </div>
          ) : null}
        </section>

        {total > 0 ? (
          <div className={styles.pageFooter}>
            <Pagination
              currentPage={Math.min(currentPage, lastPage)}
              lastPage={lastPage}
              onPageChange={goToPage}
              prevAriaLabel={t('gigs.pagination.prev_page')}
              nextAriaLabel={t('gigs.pagination.next_page')}
              infoText={t('gigs.pagination.results', { from: pageStart, to: pageEnd, total })}
              showSinglePage
            />
          </div>
        ) : null}
      </div>
    </MainLayout>
  )
}
