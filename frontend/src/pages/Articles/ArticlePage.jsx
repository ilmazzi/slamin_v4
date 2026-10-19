import { useEffect, useMemo } from 'react'
import { listArticles } from '../../app/api/articles.js'
import { createArticleReport } from '../../app/api/reports.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { useArticlePageSearchParams } from '../../app/hooks/useArticlePageSearchParams.js'
import { useArticleCategoryLookup } from '../../app/hooks/useArticleCategoryLookup.js'
import { usePaginationUi } from '../../app/hooks/usePaginationUi.js'
import { splitFeaturedArticle } from '../../app/helpers/splitFeaturedArticle.js'
import pageStyles from '../shared/ContentPageShell.module.css'
import styles from './ArticlePage.module.css'
import NewsFilterBar from '../../components/news/NewsFilterBar.jsx'
import FeaturedDesktopArticle from '../../components/news/FeaturedDesktopArticle.jsx'
import AllArticlesSection from '../../components/news/AllArticlesSection.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import Pagination from '../../components/shared/Pagination.jsx'

const API_PER_PAGE = 5

export default function ArticlePage() {
  const { t, locale } = useI18n()
  const { user, token } = useAuth()
  const canCreateArticle = user?.account_type === 'poet_organizer'
  const {
    categorySlug,
    currentPage: currentPageFromUrl,
    setSearchParams,
    setCategory,
    resetFilters,
    goToPage,
  } = useArticlePageSearchParams()
  const {
    categoryOptions,
    categoryIdForFilter,
    isLoadingLookups,
    lookupsError,
  } = useArticleCategoryLookup({ categorySlug, t })

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

  const { featuredArticle, articlesList } = useMemo(
    () => splitFeaturedArticle(pageItems, listMeta?.current_page ?? currentPageFromUrl),
    [currentPageFromUrl, listMeta?.current_page, pageItems],
  )

  const {
    currentPage,
    lastPage,
    total,
    prevPaginationLabel,
    nextPaginationLabel,
    paginationInfo,
  } = usePaginationUi({
    meta: listMeta,
    fallbackPage: currentPageFromUrl,
    fallbackPerPage: API_PER_PAGE,
    itemCount: pageItems.length,
    t,
    mode: 'results',
  })

  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    t,
    contentType: 'article',
    knownItems: pageItems,
    shareCopiedMessage: t('articles.actions.share_copied'),
    shareSentMessage: t('articles.actions.share_sent'),
    shareFailedMessage: t('articles.actions.share_failed'),
    reportSubmittedMessage: t('articles.actions.report_submitted'),
    reportFailedMessage: t('report.ui.submit_error'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id || item?.slug || null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createArticleReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  return (
    <MainLayout themeTone="articles">
      <div className={pageStyles.articlesPage}>
        <PageHero
          title={t('articles.title')}
          leadPrefix={t('articles.lead_prefix')}
          leadEmphasis={t('articles.lead_emphasis')}
          ctaLabel={t('articles.create_article')}
          ctaHref="/articles/create"
          hideCta={!canCreateArticle}
        />

        <section className={styles.filterSection}>
          <NewsFilterBar
            searchValue=""
            onSearchChange={() => {}}
            categoryValue={categorySlug}
            categoryOptions={categoryOptions}
            onCategoryChange={setCategory}
            tagValue=""
            tagOptions={[]}
            onTagChange={() => {}}
            sortValue="newest"
            onSortChange={() => {}}
            onReset={resetFilters}
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

        <HomeDashboardPromoSection />

        <ContentReportModal
          isOpen={Boolean(reportModalItem)}
          item={reportModalItem}
          itemTitle={reportModalItem?.title ?? t('articles.all.no_title')}
          title={t('articles.actions.report_title')}
          description={t('articles.actions.report_description')}
          cancelLabel={t('articles.actions.report_cancel')}
          confirmLabel={t('articles.actions.report_confirm')}
          isSubmitting={isSubmittingReport}
          onClose={() => setReportModalItem(null)}
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
