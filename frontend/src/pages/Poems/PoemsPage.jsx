import { useEffect, useMemo, useState } from 'react'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import styles from './PoemsPage.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { usePoemPageSearchParams } from '../../app/hooks/usePoemPageSearchParams.js'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { listPoemCategories, listPoems } from '../../app/api/poems.js'
import AllPoemsSection from '../../components/poems/AllPoemsSection.jsx'
import PoemsFilterBar from '../../components/poems/PoemsFilterBar.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'

const CATALOG_PER_PAGE = 9
const CATALOG_FETCH_PER_PAGE = 100
const SEARCH_DEBOUNCE_MS = 300

export default function PoemsPage() {
  const { t, locale, availableLocales } = useI18n()
  const { user } = useAuth()
  const canCreatePoem = user?.account_type === 'poet_organizer'
  const {
    search,
    categorySlug,
    language,
    sort,
    currentPage: currentPageFromUrl,
    setSearch,
    setCategory,
    setLanguage,
    setSort,
    goToPage,
  } = usePoemPageSearchParams()

  const [searchInput, setSearchInput] = useState(search)
  const [categories, setCategories] = useState([])
  const [isLoadingLookups, setIsLoadingLookups] = useState(true)

  useEffect(() => {
    setSearchInput(search)
  }, [search])

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (searchInput !== search) {
        setSearch(searchInput)
      }
    }, SEARCH_DEBOUNCE_MS)

    return () => window.clearTimeout(timer)
  }, [search, searchInput, setSearch])

  useEffect(() => {
    const controller = new AbortController()

    async function loadCategories() {
      setIsLoadingLookups(true)
      try {
        const { categories: nextCategories } = await listPoemCategories({ signal: controller.signal })
        setCategories(Array.isArray(nextCategories) ? nextCategories : [])
      } catch (error) {
        if (error?.name !== 'AbortError') {
          setCategories([])
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoadingLookups(false)
        }
      }
    }

    loadCategories()
    return () => controller.abort()
  }, [])

  const categoryOptions = useMemo(
    () => categories.map((item) => ({
      value: item.slug ?? String(item.id),
      label: item.name ?? item.slug ?? String(item.id),
    })),
    [categories],
  )

  const categoryIdForFilter = useMemo(() => {
    if (!categorySlug) {
      return null
    }
    const match = categories.find((item) => item.slug === categorySlug || String(item.id) === String(categorySlug))
    return match?.id ?? null
  }, [categories, categorySlug])

  const languageOptions = useMemo(
    () => (availableLocales ?? []).map((entry) => {
      if (typeof entry === 'string') {
        return { value: entry, label: entry.toUpperCase() }
      }
      return {
        value: entry.code,
        label: entry.name ?? String(entry.code ?? '').toUpperCase(),
      }
    }).filter((item) => item.value),
    [availableLocales],
  )

  const {
    items: poems,
    meta: listMeta,
    isLoading: isLoadingList,
    error: listError,
  } = usePaginatedApiList({
    fetcher: listPoems,
    params: {
      page: 1,
      perPage: CATALOG_FETCH_PER_PAGE,
      poemCategoryId: categoryIdForFilter,
      search,
      language,
      sort,
    },
    enabled: !isLoadingLookups && (!categorySlug || Boolean(categoryIdForFilter)),
    deps: [categoryIdForFilter, categorySlug, search, language, sort, isLoadingLookups],
  })

  const total = Math.max(0, Number(listMeta?.total ?? poems.length))
  const lastPage = Math.max(1, Math.ceil(total / CATALOG_PER_PAGE) || 1)
  const currentPage = Math.min(currentPageFromUrl, lastPage)
  const perPage = CATALOG_PER_PAGE
  const pageStart = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1
  const pageEnd = total === 0 ? 0 : Math.min(total, currentPage * perPage)
  const paginationInfo = t('poems.pagination.results', { from: pageStart, to: pageEnd, total })
  const prevPaginationLabel = t('poems.pagination.prev_page')
  const nextPaginationLabel = t('poems.pagination.next_page')

  const {
    reportModalItem,
    toastMessage,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    t,
    contentType: 'poem',
    knownItems: poems,
    shareCopiedMessage: t('poems.actions.share_copied'),
    shareSentMessage: t('poems.actions.share_sent'),
    shareFailedMessage: t('poems.actions.share_failed'),
    reportSubmittedMessage: t('poems.actions.report_submitted'),
  })

  return (
    <MainLayout themeTone="poems">
      <div className={pageStyles.poemsPage}>
        <PageHero
          title={t('poems.title')}
          leadPrefix={t('poems.lead_prefix')}
          leadEmphasis={t('poems.lead_emphasis')}
          ctaLabel={t('poems.upload_poem')}
          ctaHref="/poems/create"
          hideCta={!canCreatePoem}
        />

        <section className={styles.filtersSection}>
          <PoemsFilterBar
            searchValue={searchInput}
            onSearchChange={setSearchInput}
            sortValue={sort}
            onSortChange={setSort}
            categoryValue={categorySlug}
            categoryOptions={categoryOptions}
            onCategoryChange={setCategory}
            languageValue={language}
            languageOptions={languageOptions}
            onLanguageChange={setLanguage}
            isLoading={isLoadingLookups}
            t={t}
          />
        </section>

        <div className={styles.catalogBand}>
          <AllPoemsSection
            poems={poems}
            isLoading={isLoadingList || isLoadingLookups}
            error={listError}
            locale={locale}
            t={t}
            page={currentPage}
            lastPage={lastPage}
            onPageChange={goToPage}
            showHeader={false}
            showPagination={false}
          />
        </div>

        <div className={styles.pageFooter}>
          <div className={styles.paginationWrap}>
            {!isLoadingList && !isLoadingLookups ? (
              <Pagination
                currentPage={currentPage}
                lastPage={lastPage}
                onPageChange={goToPage}
                prevAriaLabel={prevPaginationLabel}
                nextAriaLabel={nextPaginationLabel}
                infoText={paginationInfo}
                showSinglePage
              />
            ) : null}
          </div>
        </div>
      </div>

      <HomeDashboardPromoSection />

      <ContentReportModal
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? t('poems.all.no_title')}
        title={t('poems.actions.report_title')}
        description={t('poems.actions.report_description')}
        cancelLabel={t('poems.actions.report_cancel')}
        confirmLabel={t('poems.actions.report_confirm')}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />

      <AppToast
        message={toastMessage}
        isVisible={Boolean(toastMessage)}
        onClose={() => setToastMessage('')}
      />
    </MainLayout>
  )
}
