import SocialActions from '../shared/SocialActions.jsx'
import PillButton from '../shared/PillButton.jsx'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import {
  mediaArrowFeaturedFigmaIcon,
} from '../../assets/home/media/index.js'
import styles from './FeaturedDesktopArticle.module.css'

function estimateReadingMinutes(article) {
  const sourceText = `${article?.title ?? ''} ${article?.excerpt ?? ''}`.trim()
  if (!sourceText) {
    return 1
  }

  const words = sourceText.split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.round(words / 200))
}

function initialsFromName(value) {
  const parts = String(value ?? '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  if (parts.length === 0) {
    return 'SL'
  }
  return parts
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

function buildArticleMetrics(article) {
  return {
    views: Number.isFinite(Number(article?.views_count)) ? Number(article.views_count) : 0,
    likes: Number.isFinite(Number(article?.likes_count)) ? Number(article.likes_count) : 0,
    comments: Number.isFinite(Number(article?.comments_count)) ? Number(article.comments_count) : 0,
  }
}

export default function FeaturedDesktopArticle({ article, locale, t }) {
  if (!article) {
    return null
  }

  const authorName = t('articles.featured.author_name')
  const readingMinutes = estimateReadingMinutes(article)
  const publishedDate = formatShortPublishedDate(article?.published_at, locale, '-')
  const metrics = buildArticleMetrics(article)
  const detailId = article?.slug ?? article?.id

  return (
    <section className={styles.featuredSection}>
      <article className={styles.featuredCard}>
        <div className={styles.top}>
          <div
            className={styles.poster}
            style={
              article?.cover_image_url
                ? {
                    backgroundImage: `url("${article.cover_image_url}")`,
                  }
                : undefined
            }
          >
            {!article?.cover_image_url ? (
              <div className={styles.logo}>
                <span className={styles.logoMain}>SLAM</span>
                <span className={styles.logoBottom}>IN</span>
                <span className={styles.logoMark} aria-hidden="true" />
              </div>
            ) : null}
          </div>

          <div className={styles.content}>
            <div className={styles.contentTop}>
              <div className={styles.authorRow}>
                <span className={styles.avatar}>{initialsFromName(authorName)}</span>
                <span className={styles.authorName}>{authorName}</span>
              </div>

              <div className={styles.textRow}>
                <h2 className={styles.title}>{article?.title ?? t('articles.featured.no_title')}</h2>
                <p className={styles.excerpt}>{article?.excerpt ?? t('articles.featured.no_excerpt')}</p>
              </div>
            </div>

            <div className={styles.contentBottom}>
              <PillButton
                href={detailId ? `/articles/${detailId}` : '/articles'}
                label={t('common.discover_more')}
                variant="dark"
                size="featuredCta"
                iconSrc={mediaArrowFeaturedFigmaIcon}
                className={styles.discoverButton}
              />

              <p className={styles.meta}>
                <span>{publishedDate}</span>
                <span>
                  {t('articles.featured.reading_time').replace(':minutes', String(readingMinutes))}
                </span>
              </p>
            </div>
          </div>
        </div>

        <div className={styles.bottom}>
          <div className={styles.interactions}>
            <SocialActions
              article={article}
              metrics={metrics}
              shareLabel={t('articles.featured.share')}
              reportLabel={t('articles.featured.save')}
              variant="featured"
              metricsClassName={styles.metrics}
              metricClassName={styles.metric}
              viewMetricClassName={styles.metricView}
              likeMetricClassName={styles.metricLike}
              commentMetricClassName={styles.metricComment}
              viewIconClassName={styles.metricIconView}
              likeIconClassName={styles.metricIconLike}
              commentIconClassName={styles.metricIconComment}
              buttonsClassName={styles.actions}
              shareIconClassName={styles.actionIconShare}
              reportIconClassName={styles.actionIconReport}
            />
          </div>

          <span className={styles.cornerPolygon} aria-hidden="true" />
        </div>
      </article>
    </section>
  )
}
