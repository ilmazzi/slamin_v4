import { useCallback, useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import GroupCard from '../../components/groups/GroupCard.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import styles from './GroupsPage.module.css'
import filterStyles from '../../components/poems/PoemsFilterBar.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { listGroups, listMyGroups } from '../../app/api/groups.js'

const PER_PAGE = 10
const SEARCH_DEBOUNCE_MS = 300

export default function GroupsPage() {
  const { t } = useI18n()
  const { token, user } = useAuth()
  const canCreate = user?.account_type === 'poet_organizer'
  const [searchParams, setSearchParams] = useSearchParams()
  const mine = searchParams.get('mine') === '1'
  const currentPage = Math.max(1, Number(searchParams.get('page') || 1))
  const search = searchParams.get('q') || ''
  const [searchInput, setSearchInput] = useState(search)
  const [refreshKey, setRefreshKey] = useState(0)

  useEffect(() => {
    setSearchInput(search)
  }, [search])

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (searchInput.trim() === search) return
      const next = new URLSearchParams(searchParams)
      if (searchInput.trim()) next.set('q', searchInput.trim())
      else next.delete('q')
      next.set('page', '1')
      setSearchParams(next, { replace: true })
    }, SEARCH_DEBOUNCE_MS)

    return () => window.clearTimeout(timer)
  }, [search, searchInput, searchParams, setSearchParams])

  const fetchGroups = useCallback(
    (params, options) => (
      mine
        ? listMyGroups(params, { ...options, token })
        : listGroups(params, { ...options, token })
    ),
    [mine, token],
  )

  const { items: groups, meta, isLoading, error } = usePaginatedApiList({
    fetcher: fetchGroups,
    params: { page: currentPage, perPage: PER_PAGE, search },
    enabled: Boolean(token),
    deps: [currentPage, search, mine, refreshKey],
  })

  const total = meta.total
  const lastPage = meta.last_page
  const pageStart = total === 0 ? 0 : ((meta.current_page - 1) * meta.per_page) + 1
  const pageEnd = total === 0 ? 0 : Math.min(total, pageStart + groups.length - 1)

  const goToPage = (page) => {
    const next = new URLSearchParams(searchParams)
    next.set('page', String(page))
    setSearchParams(next)
  }

  useEffect(() => {
    if (!isLoading && !error && currentPage > lastPage) {
      goToPage(lastPage)
    }
  }, [currentPage, error, isLoading, lastPage])

  return (
    <MainLayout themeTone="groups">
      <div className={pageStyles.groupsPage}>
        <PageHero
          title={t('group.ui.title')}
          leadPrefix={t('group.ui.lead_prefix')}
          leadEmphasis={t('group.ui.lead_emphasis')}
          ctaLabel={t('group.ui.create')}
          ctaHref="/groups/create"
          hideCta={!canCreate}
        />

        <section className={styles.filtersSection}>
          <div className={filterStyles.filterBar}>
            <div className={filterStyles.searchRow}>
              <label className={filterStyles.searchWrap}>
                <input
                  type="search"
                  className={filterStyles.searchInput}
                  value={searchInput}
                  onChange={(event) => setSearchInput(event.target.value)}
                  placeholder={t('group.ui.search_placeholder')}
                  aria-label={t('group.ui.search_label')}
                  maxLength={100}
                />
              </label>
              <Link
                to={mine ? '/groups' : '/groups?mine=1'}
                className={styles.mineLink}
              >
                {mine ? t('group.ui.all') : t('group.ui.mine')}
              </Link>
            </div>
          </div>
        </section>

        <section className={styles.catalog} aria-busy={isLoading} aria-label={t('group.ui.title')}>
          {error ? <p className={styles.state} role="alert">{error}</p> : null}
          {!error && isLoading && groups.length === 0 ? (
            <p className={styles.state}>{t('group.ui.loading')}</p>
          ) : null}
          {!error && !isLoading && groups.length === 0 ? (
            <p className={styles.state}>{t('group.ui.empty')}</p>
          ) : null}
          {groups.length > 0 ? (
            <div className={[styles.grid, isLoading ? styles.gridLoading : ''].join(' ')}>
              {groups.map((group) => (
                <GroupCard
                  key={group.id}
                  group={group}
                  token={token}
                  t={t}
                  onUpdated={() => setRefreshKey((current) => current + 1)}
                />
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
              prevAriaLabel={t('group.ui.prev_page')}
              nextAriaLabel={t('group.ui.next_page')}
              infoText={t('group.ui.results', { from: pageStart, to: pageEnd, total })}
              showSinglePage
            />
          </div>
        ) : null}

        <HomeDashboardPromoSection compactTop />
      </div>
    </MainLayout>
  )
}
