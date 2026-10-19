import { useEffect, useRef, useState } from 'react'
import styles from './ArticleDetailPage.module.css'
import MainLayout from '../../components/layout/MainLayout.jsx'
import ArticleCard from '../../components/news/ArticleCard.jsx'
import EventGroupsHighlight from '../../components/groups/EventGroupsHighlight.jsx'
import { useLocation, useParams } from 'react-router-dom'
import shellStyles from '../shared/ContentPageShell.module.css'
import { listArticles } from '../../app/api/articles.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import FeaturedArticles from '../../components/news/FeaturedArticles.jsx'
import AllArticlesSection from '../../components/news/AllArticlesSection.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import CommentsThread from '../../components/shared/CommentsThread.jsx'
import { createArticleReport } from '../../app/api/reports.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { scheduleScrollToComments } from '../../app/utils/scrollToComments.js'

const ALL_ARTICLES_PER_PAGE = 5

export default function ArticleDetailPage() {
  const { slug } = useParams()
  const location = useLocation()
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const commentsRef = useRef(null)
  const [articleDetail, setArticleDetail] = useState(null)
  const [allArticlesPage, setAllArticlesPage] = useState(1)
  const { items: allArticles, meta, isLoading: isLoadingAllArticles } = usePaginatedApiList({
    fetcher: listArticles,
    params: { page: allArticlesPage, perPage: ALL_ARTICLES_PER_PAGE },
    deps: [allArticlesPage],
  })

  const currentPage = Math.max(1, Number(meta?.current_page ?? allArticlesPage))
  const lastPage = Math.max(1, Number(meta?.last_page ?? 1))
  const prevPaginationLabel = t('articles.pagination.prev_page')
  const nextPaginationLabel = t('articles.pagination.next_page')
  const paginationInfo = t('articles.pagination.page', { current: currentPage, last: lastPage })

  useEffect(() => {
    if (location.hash !== '#comments' && !location.state?.focusComments) {
      return
    }
    if (!articleDetail?.id || !commentsRef.current) {
      return
    }

    const cancel = scheduleScrollToComments({ behavior: 'smooth' })

    return cancel
  }, [articleDetail?.id, location.hash, location.state])

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
    knownItems: allArticles,
    shareCopiedMessage: t('articles.actions.share_copied'),
    shareSentMessage: t('articles.actions.share_sent'),
    shareFailedMessage: t('articles.actions.share_failed'),
    reportSubmittedMessage: t('articles.actions.report_submitted'),
    reportFailedMessage: t('common.no_results'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id || item?.slug || articleDetail?.id || null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createArticleReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  return (
    <MainLayout themeTone="articles">
      <div className={[shellStyles.articlesPage, styles.articleDetailPage].join(' ')}>
        <div className={styles.article}>
          <ArticleCard
            slug={slug}
            onLoaded={setArticleDetail}
            onComments={() => commentsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
          />
          <div className={styles.groupsBand}>
            <EventGroupsHighlight
              groups={articleDetail?.groups}
              t={t}
              contentType="article"
              variant="inset"
            />
          </div>
        </div>
        <div id="comments" ref={commentsRef} style={{ scrollMarginTop: 104 }}>
          <CommentsThread contentType="article" contentId={articleDetail?.id ?? null} />
        </div>
        <FeaturedArticles />
        <AllArticlesSection articles={allArticles} locale={locale} t={t} />
        {!isLoadingAllArticles ? (
          <Pagination
            currentPage={currentPage}
            lastPage={lastPage}
            onPageChange={setAllArticlesPage}
            prevAriaLabel={prevPaginationLabel}
            nextAriaLabel={nextPaginationLabel}
            infoText={paginationInfo}
          />
        ) : null}
      </div>
      <ContentReportModal
        key={reportModalItem?.id ?? 'article-report-closed'}
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
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
