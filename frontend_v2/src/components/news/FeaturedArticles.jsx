import ArticleTeaserCard from '../shared/ArticleTeaserCard.jsx'
import { useState } from 'react'
import { useI18n } from '../../app/hooks/useI18n.js'
import { listFeaturedArticles } from '../../app/api/articles.js'
import { normalizeArticlesForUi } from '../../app/normalizers/article.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import Pagination from '../shared/Pagination.jsx'

import styles from './FeaturedArticles.module.css'
import titleStyles from './AllArticlesSection.module.css'

const API_PER_PAGE = 1

export default function FeaturedArticles() {
  const { t, locale } = useI18n()
  const [page, setPage] = useState(1)

  const { items: featuredArticles, meta, isLoading, error } = usePaginatedApiList({
    fetcher: listFeaturedArticles,
    params: { page, perPage: API_PER_PAGE },
    normalizeItems: normalizeArticlesForUi,
    deps: [page],
  })

  const currentPage = meta?.current_page ?? page
  const lastPage = meta?.last_page ?? 1

  const [titlePrefix = '', ...titleRest] = t('articles.featured.title').split(' ')
  const titleEmphasis = titleRest.join(' ')

  const pageLabelTemplate =
    t('articles.pagination.page') === 'articles.pagination.page'
      ? 'Pagina :current di :last'
      : t('articles.pagination.page')
  const paginationInfo = pageLabelTemplate
    .replace(':current', String(currentPage))
    .replace(':last', String(lastPage))

  const prevPaginationLabel =
    t('articles.pagination.prev') === 'articles.pagination.prev'
      ? 'Pagina precedente'
      : t('articles.pagination.prev')
  const nextPaginationLabel =
    t('articles.pagination.next') === 'articles.pagination.next'
      ? 'Pagina successiva'
      : t('articles.pagination.next')

  return (
    <section className={styles.section}>
      <header className={titleStyles.header}>
        <h2 className={titleStyles.title}>
          <span className={titleStyles.titlePrefix}>{titlePrefix} </span>
          <span className={titleStyles.titleEmphasis}>{titleEmphasis}</span>
        </h2>
      </header>

      {isLoading ? <p className={styles.empty}>Loading...</p> : null}
      {error ? <p className={styles.empty}>{error}</p> : null}

      {!isLoading && !error && !featuredArticles?.length ? (
        <p className={styles.empty}>{t('articles.featured.empty')}</p>
      ) : null}

      {!isLoading && !error && featuredArticles?.length ? (
        <>
          <div className={styles.grid}>
            {featuredArticles.map((article) => (
              <ArticleTeaserCard
                key={article.id ?? article.slug}
                href="/articles"
                article={article}
                locale={locale}
                t={t}
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
            showSinglePage={true}
          />
        </>
      ) : null}
    </section>
  )
}
