import { useEffect, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { listArticleCategories, listArticles } from '../../app/api/articles.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useArticleFeedbackUi } from '../../app/hooks/useArticleFeedbackUi.js'
import { normalizeArticlesForUi } from '../../app/normalizers/article.js'
import pageStyles from '../shared/ContentPageShell.module.css'
import styles from './ArticlePage.module.css'
import NewsFilterBar from '../../components/news/NewsFilterBar.jsx'
import FeaturedDesktopArticle from '../../components/news/FeaturedDesktopArticle.jsx'
import AllArticlesSection from '../../components/news/AllArticlesSection.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import ArticleReportModal from '../../components/shared/ArticleReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import Pagination from '../../components/shared/Pagination.jsx'

const API_PER_PAGE = 5

export default function ArticlePage() {
  const { t, locale } = useI18n()
  const [searchParams, setSearchParams] = useSearchParams()
  const [categories, setCategories] = useState([])
  const [isLoadingLookups, setIsLoadingLookups] = useState(true)
  const [lookupsError, setLookupsError] = useState('')

  const categorySlug = searchParams.get('category') ?? ''
  const rawPage = searchParams.get('page') ?? '1'
  const currentPageFromUrl = Math.max(1, Math.floor(Number.parseInt(rawPage, 10)) || 1)

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

  const {
    items: pageItems,
    meta: listMeta,
    isLoading: isLoadingList,
  } = usePaginatedApiList({
    fetcher: listArticles,
    params: {
      page: currentPageFromUrl,
      perPage: API_PER_PAGE,
      articleCategoryId: categoryIdForFilter,
    },
    enabled: !isLoadingLookups && (!categorySlug || Boolean(categoryIdForFilter)),
    normalizeItems: normalizeArticlesForUi,
    deps: [currentPageFromUrl, categoryIdForFilter, categorySlug, isLoadingLookups],
  })

  useEffect(() => {
    if (isLoadingLookups || !categorySlug || categoryIdForFilter) {
      return
    }

    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev)
        next.set('page', '1')
        return next
      },
      { replace: true },
    )
  }, [categoryIdForFilter, categorySlug, isLoadingLookups, setSearchParams])

  useEffect(() => {
    const last = Math.max(1, Number(listMeta?.last_page ?? 1))
    if (currentPageFromUrl <= last) {
      return
    }

    setSearchParams(
      (prev) => {
        const next = new URLSearchParams(prev)
        next.set('page', String(last))
        return next
      },
      { replace: true },
    )
  }, [currentPageFromUrl, listMeta?.last_page, setSearchParams])

  const categoryOptions = useMemo(
    () =>
      categories.map((category) => ({
        value: category?.slug ?? '',
        label: category?.name ?? '',
      })),
    [categories],
  )

  const { featuredArticle, articlesList } = useMemo(() => {
    if (pageItems.length === 0) {
      return { featuredArticle: null, articlesList: [] }
    }

    if ((listMeta?.current_page ?? currentPageFromUrl) === 1) {
      return {
        featuredArticle: pageItems[0] ?? null,
        articlesList: pageItems.slice(1),
      }
    }

    return {
      featuredArticle: null,
      articlesList: pageItems,
    }
  }, [currentPageFromUrl, listMeta?.current_page, pageItems])

  const currentPage = Math.max(1, Number(listMeta?.current_page ?? currentPageFromUrl))
  const lastPage = Math.max(1, Number(listMeta?.last_page ?? 1))
  const total = Math.max(0, Number(listMeta?.total ?? pageItems.length))
  const perPage = Math.max(1, Number(listMeta?.per_page ?? API_PER_PAGE))
  const pageStart = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1
  const pageEnd = total === 0 ? 0 : Math.min(total, pageStart + Math.max(0, pageItems.length - 1))
  const paginationInfoTemplate =
    t('articles.pagination.results') === 'articles.pagination.results'
      ? 'Mostra :from-:to di :total risultati'
      : t('articles.pagination.results')
  const prevPaginationLabel =
    t('articles.pagination.prev') === 'articles.pagination.prev'
      ? 'Pagina precedente'
      : t('articles.pagination.prev')
  const nextPaginationLabel =
    t('articles.pagination.next') === 'articles.pagination.next'
      ? 'Pagina successiva'
      : t('articles.pagination.next')
  const paginationInfo = paginationInfoTemplate
    .replace(':from', String(pageStart))
    .replace(':to', String(pageEnd))
    .replace(':total', String(total))

  const handleParamChange = (key, value) => {
    const next = new URLSearchParams(searchParams)
    const normalizedValue = String(value ?? '').trim()

    if (normalizedValue) {
      next.set(key, normalizedValue)
    } else {
      next.delete(key)
    }

    next.set('page', '1')
    setSearchParams(next, { replace: true })
  }

  const handleResetFilters = () => {
    const next = new URLSearchParams()
    next.set('page', '1')
    setSearchParams(next, { replace: true })
  }

  const goToPage = (page) => {
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

  const {
    reportModalArticle,
    toastMessage,
    setReportModalArticle,
    setToastMessage,
    handleReportConfirm,
  } = useArticleFeedbackUi(t, pageItems)

  return (
    <MainLayout themeTone="articles">
      <div className={pageStyles.articlesPage}>
        <PageHero
          title={t('articles.title')}
          leadPrefix={t('articles.lead_prefix')}
          leadEmphasis={t('articles.lead_emphasis')}
          ctaLabel={t('articles.create_article')}
          ctaHref="/articles/create"
        />

        <section className={styles.filterSection}>
          <NewsFilterBar
            searchValue=""
            onSearchChange={() => {}}
            categoryValue={categorySlug}
            categoryOptions={categoryOptions}
            onCategoryChange={(value) => handleParamChange('category', value)}
            tagValue=""
            tagOptions={[]}
            onTagChange={() => {}}
            sortValue="newest"
            onSortChange={() => {}}
            onReset={handleResetFilters}
            isLoading={isLoadingLookups}
            t={t}
            showTag={false}
            showReset={false}
            showSearch={false}
            showSort={false}
          />

          {lookupsError ? <p className={styles.filterError}>{lookupsError}</p> : null}

          <p className={styles.resultsInfo}>
            {isLoadingList || isLoadingLookups
              ? t('articles.filters.loading_results')
              : t('articles.filters.results_count').replace(':count', String(total))}
          </p>
        </section>

        <FeaturedDesktopArticle article={featuredArticle} locale={locale} t={t} />
        <AllArticlesSection articles={articlesList} locale={locale} t={t} />

        {!isLoadingList && !isLoadingLookups ? (
          <Pagination
            currentPage={currentPage}
            lastPage={lastPage}
            onPageChange={goToPage}
            prevAriaLabel={prevPaginationLabel}
            nextAriaLabel={nextPaginationLabel}
            infoText={paginationInfo}
          />
        ) : null}

        <HomeDashboardPromoSection compactTop={true} />

        <ArticleReportModal
          isOpen={Boolean(reportModalArticle)}
          article={reportModalArticle}
          t={t}
          onClose={() => setReportModalArticle(null)}
          onConfirm={handleReportConfirm}
        />

        <AppToast
          message={toastMessage}
          isVisible={Boolean(toastMessage)}
          onClose={() => setToastMessage('')}
        />
      </div>
    </MainLayout>
  )
}
