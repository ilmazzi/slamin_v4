import { useState } from 'react'
import styles from './ArticleDetailPage.module.css'
import MainLayout from '../../components/layout/MainLayout.jsx'
import ArticleCard from '../../components/news/ArticleCard.jsx'
import { useParams } from 'react-router-dom'
import shellStyles from '../shared/ContentPageShell.module.css'
import { listArticles } from '../../app/api/articles.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { normalizeArticlesForUi } from '../../app/normalizers/article.js'
import ArticleReportModal from '../../components/shared/ArticleReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import FeaturedArticles from '../../components/news/FeaturedArticles.jsx'
import AllArticlesSection from '../../components/news/AllArticlesSection.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import { useArticleFeedbackUi } from '../../app/hooks/useArticleFeedbackUi.js'

const ALL_ARTICLES_PER_PAGE = 5

export default function ArticleDetailPage() {
  const { slug } = useParams()
  const { t, locale } = useI18n()
  const [allArticlesPage, setAllArticlesPage] = useState(1)
  const { items: allArticles, meta, isLoading: isLoadingAllArticles } = usePaginatedApiList({
    fetcher: listArticles,
    params: { page: allArticlesPage, perPage: ALL_ARTICLES_PER_PAGE },
    normalizeItems: normalizeArticlesForUi,
    deps: [allArticlesPage],
  })

  const currentPage = Math.max(1, Number(meta?.current_page ?? allArticlesPage))
  const lastPage = Math.max(1, Number(meta?.last_page ?? 1))
  const paginationInfoTemplate =
    t('articles.pagination.page') === 'articles.pagination.page'
      ? 'Pagina :current di :last'
      : t('articles.pagination.page')
  const prevPaginationLabel =
    t('articles.pagination.prev') === 'articles.pagination.prev'
      ? 'Pagina precedente'
      : t('articles.pagination.prev')
  const nextPaginationLabel =
    t('articles.pagination.next') === 'articles.pagination.next'
      ? 'Pagina successiva'
      : t('articles.pagination.next')
  const paginationInfo = paginationInfoTemplate
    .replace(':current', String(currentPage))
    .replace(':last', String(lastPage))

  const {
    reportModalArticle,
    toastMessage,
    setReportModalArticle,
    setToastMessage,
    handleReportConfirm,
  } = useArticleFeedbackUi(t, allArticles)

  return (
    <MainLayout themeTone="articles">
      <div className={[shellStyles.articlesPage, styles.articleDetailPage].join(' ')}>
        <div className={styles.article}>
          <ArticleCard slug={slug} />
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
      <ArticleReportModal
        isOpen={Boolean(reportModalArticle)}
        article={reportModalArticle}
        t={t}
        onClose={() => setReportModalArticle(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
