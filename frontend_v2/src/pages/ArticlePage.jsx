import { useEffect, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import MainLayout from '../components/layout/MainLayout.jsx'
import PageHero from '../components/PageHero.jsx'
import { useI18n } from '../app/hooks/useI18n.js'
import { getApiUrl } from '../app/api/url.js'
import { readJsonSafe } from '../app/api/slaminApi.js'
import pageStyles from './EventPage.module.css'
import styles from './ArticlePage.module.css'
import NewsFilterBar from '../components/news/NewsFilterBar.jsx'
import FeaturedDesktopArticle from '../components/news/FeaturedDesktopArticle.jsx'
import AllArticlesSection from '../components/news/AllArticlesSection.jsx'
import HomeDashboardPromoSection from '../components/home/HomeDashboardPromoSection.jsx'
import ArticleReportModal from '../components/shared/ArticleReportModal.jsx'
import AppToast from '../components/shared/AppToast.jsx'

/**
 * Deve combaciare con l’intento “prima riga hero + 9 card” sulla pagina 1.
 * L’API (`ListArticleRequest`) ammette per_page fino a 50.
 */
const API_PER_PAGE = 10

/**
 * I componenti notizie (Featured / Teaser) erano costruiti su un DTO con `featured_image` e `metrics`.
 * L'API v4 espone `cover_image_url` e `likes_count` — qui adattiamo senza toccare ogni sotto-componente.
 */
function normalizeArticleForUi(article) {
  if (!article) {
    return null
  }
  return {
    ...article,
    featured_image: article.cover_image_url ?? article.featured_image,
    metrics: {
      views: 0,
      likes: article.likes_count ?? 0,
      comments: 0,
    },
  }
}

export default function ArticlePage() {
  const { t, locale } = useI18n()
  const [searchParams, setSearchParams] = useSearchParams()

  const [categories, setCategories] = useState([])
  const [isLoadingLookups, setIsLoadingLookups] = useState(true)
  const [lookupsError, setLookupsError] = useState('')

  const [isLoadingList, setIsLoadingList] = useState(false)
  /** Risposta corrente del backend: una pagina alla volta. */
  const [pageItems, setPageItems] = useState([])
  const [listMeta, setListMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: API_PER_PAGE,
    total: 0,
  })
  const [reportModalArticle, setReportModalArticle] = useState(null)
  const [toastMessage, setToastMessage] = useState('')

  const categorySlug = searchParams.get('category') ?? ''
  const rawPage = searchParams.get('page') ?? '1'
  const currentPageFromUrl = Math.max(1, Math.floor(Number.parseInt(rawPage, 10)) || 1)

  const categoryIdForFilter = useMemo(() => {
    if (!categorySlug) {
      return null
    }
    const found = categories.find((c) => c?.slug === categorySlug)
    return found?.id ?? null
  }, [categories, categorySlug])

  const { featuredArticle, articlesList } = useMemo(() => {
    if (pageItems.length === 0) {
      return { featuredArticle: null, articlesList: [] }
    }
    if (listMeta.current_page === 1) {
      return {
        featuredArticle: pageItems[0] ?? null,
        articlesList: pageItems.slice(1),
      }
    }
    return {
      featuredArticle: null,
      articlesList: pageItems,
    }
  }, [pageItems, listMeta.current_page])

  useEffect(() => {
    const controller = new AbortController()

    async function loadLookups() {
      setIsLoadingLookups(true)
      setLookupsError('')

      try {
        const response = await fetch(getApiUrl('/api/v1/article-categories'), {
          signal: controller.signal,
          headers: { Accept: 'application/json' },
        })

        if (!response.ok) {
          throw new Error('Unable to load categories')
        }

        const payload = await readJsonSafe(response)
        const list = Array.isArray(payload?.data?.categories) ? payload.data.categories : []
        setCategories(list)
      } catch (error) {
        if (error?.name !== 'AbortError') {
          setLookupsError(t('articles.filters.load_error'))
          setCategories([])
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoadingLookups(false)
        }
      }
    }

    loadLookups()
    return () => controller.abort()
  }, [t])

  useEffect(() => {
    const controller = new AbortController()

    async function loadArticles() {
      if (isLoadingLookups) {
        return
      }

      if (categorySlug && !categoryIdForFilter) {
        setIsLoadingList(false)
        setPageItems([])
        setListMeta((m) => ({ ...m, total: 0, last_page: 1, current_page: 1 }))
        return
      }

      setIsLoadingList(true)

      try {
        const params = new URLSearchParams()
        params.set('per_page', String(API_PER_PAGE))
        params.set('page', String(currentPageFromUrl))
        if (categoryIdForFilter) {
          params.set('article_category_id', String(categoryIdForFilter))
        }

        const response = await fetch(getApiUrl(`/api/v1/articles?${params.toString()}`), {
          signal: controller.signal,
          headers: { Accept: 'application/json' },
        })

        if (!response.ok) {
          throw new Error('Unable to load articles')
        }

        const payload = await readJsonSafe(response)
        const items = Array.isArray(payload?.data) ? payload.data : []
        const meta = payload?.meta && typeof payload.meta === 'object' ? payload.meta : {}

        const last = Math.max(1, Number(meta.last_page ?? 1))
        const currentFromApi = Math.max(1, Number(meta.current_page ?? currentPageFromUrl))
        if (currentPageFromUrl > last) {
          setSearchParams(
            (prev) => {
              const next = new URLSearchParams(prev)
              next.set('page', String(last))
              return next
            },
            { replace: true },
          )
          return
        }

        setPageItems(items.map((a) => normalizeArticleForUi(a)).filter(Boolean))
        setListMeta({
          current_page: currentFromApi,
          last_page: last,
          per_page: Math.max(1, Number(meta.per_page ?? API_PER_PAGE)),
          total: Math.max(0, Number(meta.total ?? items.length)),
        })
      } catch (error) {
        if (error?.name !== 'AbortError') {
          setPageItems([])
          setListMeta({ current_page: 1, last_page: 1, per_page: API_PER_PAGE, total: 0 })
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoadingList(false)
        }
      }
    }

    loadArticles()
    return () => controller.abort()
  }, [currentPageFromUrl, categorySlug, categoryIdForFilter, isLoadingLookups, setSearchParams])

  useEffect(() => {
    const findKnownArticle = (detail = {}) => {
      if (detail.article) {
        return detail.article
      }

      if (detail.articleId != null) {
        return pageItems.find((item) => item?.id === detail.articleId) ?? null
      }

      if (detail.articleSlug) {
        return pageItems.find((item) => item?.slug === detail.articleSlug) ?? null
      }

      return null
    }

    const handleReport = (event) => {
      const nextArticle = findKnownArticle(event?.detail ?? {})
      setReportModalArticle(nextArticle)
    }

    const handleShareSuccess = (event) => {
      const mode = event?.detail?.mode
      if (mode === 'clipboard') {
        setToastMessage(t('articles.actions.share_copied'))
      } else {
        setToastMessage(t('articles.actions.share_sent'))
      }
    }

    const handleShareError = () => {
      setToastMessage(t('articles.actions.share_failed'))
    }

    window.addEventListener('article:report', handleReport)
    window.addEventListener('article:share-success', handleShareSuccess)
    window.addEventListener('article:share-error', handleShareError)

    return () => {
      window.removeEventListener('article:report', handleReport)
      window.removeEventListener('article:share-success', handleShareSuccess)
      window.removeEventListener('article:share-error', handleShareError)
    }
  }, [pageItems, t])

  const categoryOptions = useMemo(
    () =>
      categories.map((item) => ({
        value: item?.slug ?? '',
        label: item?.name ?? '',
      })),
    [categories],
  )

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

  const handleReportConfirm = () => {
    setReportModalArticle(null)
    setToastMessage(t('articles.actions.report_submitted'))
  }

  const currentPage = listMeta.current_page
  const lastPage = listMeta.last_page
  const total = listMeta.total

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
        {lastPage > 1 && !isLoadingList && !isLoadingLookups ? (
          <nav className={styles.pagination} aria-label={t('articles.pagination.nav_label')}>
            <p className={styles.paginationInfo}>
              {t('articles.pagination.page').replace(':current', String(currentPage)).replace(':last', String(lastPage))}
            </p>
            <div className={styles.paginationActions}>
              <button
                type="button"
                className={styles.paginationBtn}
                disabled={currentPage <= 1}
                onClick={() => goToPage(currentPage - 1)}
              >
                {t('articles.pagination.prev')}
              </button>
              <button
                type="button"
                className={styles.paginationBtn}
                disabled={currentPage >= lastPage}
                onClick={() => goToPage(currentPage + 1)}
              >
                {t('articles.pagination.next')}
              </button>
            </div>
          </nav>
        ) : null}
        <HomeDashboardPromoSection compactTop={true} />
        <ArticleReportModal
          isOpen={Boolean(reportModalArticle)}
          article={reportModalArticle}
          t={t}
          onClose={() => setReportModalArticle(null)}
          onConfirm={handleReportConfirm}
        />
        <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
      </div>
    </MainLayout>
  )
}
