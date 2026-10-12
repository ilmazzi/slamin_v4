import { formatShortPublishedDate } from '../../app/formatters/date.js'
import { useArticleDetail } from '../../app/hooks/useArticleDetail.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import SocialActions from '../shared/SocialActions.jsx'
import styles from './ArticleCard.module.css'
import { useEffect } from 'react'

export default function ArticleCard({ slug, onComments, onLoaded }) {
  const { t, locale } = useI18n()
  const { data: article, isLoading, error } = useArticleDetail(slug)

  useEffect(() => {
    if (article) {
      onLoaded?.(article)
    }
  }, [article, onLoaded])

  if (!slug) {
    return <div className={styles.article}>{t('articles.missing_slug')}</div>
  }

  if (isLoading) {
    return <div className={styles.article}>{t('common.loading')}</div>
  }

  if (error) {
    return <div className={styles.article}>{error}</div>
  }

  if (!article) {
    return <div className={styles.article}>{t('articles.not_found')}</div>
  }

  const authorLabel = article?.author?.display_name || article?.author?.nickname || 'Slamin'
  const publishedDate = formatShortPublishedDate(article?.published_at, locale, '')
  const categoryLabel = article?.category?.name || t('articles.no_category')
  const coverUrl = article?.cover_image_url || ''
  const viewsCount = Number.isFinite(Number(article?.views_count)) ? Number(article.views_count) : 0
  const likesCount = Number.isFinite(Number(article?.likes_count)) ? Number(article.likes_count) : 0
  const commentsCount = Number.isFinite(Number(article?.comments_count)) ? Number(article.comments_count) : 0

  return (
    <article className={styles.card}>
      <header className={styles.topBar}>
        <div className={styles.author}>
          <span className={styles.avatar} aria-hidden="true" />
          <span className={styles.authorName}>{authorLabel}</span>
        </div>

        <div className={styles.meta}>
          {publishedDate ? <span className={styles.date}>{publishedDate}</span> : null}
          <SocialActions
            article={article}
            contentType="article"
            apiBasePath="/articles"
            publicBasePath="/articles"
            metrics={{ views: viewsCount, likes: likesCount, comments: commentsCount }}
            shareLabel={t('articles.featured.share')}
            reportLabel={t('articles.actions.report')}
            variant="list"
            className={styles.socialActions}
            metricsClassName={styles.metrics}
            metricClassName={styles.metric}
            metricValueClassName={styles.metricValue}
            viewIconClassName={styles.metricIcon}
            likeIconClassName={styles.metricIcon}
            commentIconClassName={styles.metricIcon}
            buttonsClassName={styles.actions}
            iconClassName={styles.actionIcon}
            shareIconClassName={styles.actionIconShare}
            reportIconClassName={styles.actionIconReport}
            onComments={onComments}
          />
        </div>

        <div className={styles.actionsWrap}>
          <span className={styles.categoryPill}>{categoryLabel}</span>
         
        </div>
      </header>

      <div className={styles.inner}>
        <h1 className={styles.title}>{article.title}</h1>

        <div className={styles.contentGrid}>
          <div className={styles.coverWrap}>
            {coverUrl ? <img src={coverUrl} alt="" className={styles.cover} /> : <div className={styles.coverPlaceholder} aria-hidden="true" />}
          </div>

          <div className={styles.copy}>
            {article.excerpt ? <p className={styles.lead}>{article.excerpt}</p> : null}
            <div className={styles.rule} aria-hidden="true" />
            {article.content ? <div className={styles.bodyText}>{article.content}</div> : null}
          </div>
        </div>
      </div>
    </article>
  )
}
