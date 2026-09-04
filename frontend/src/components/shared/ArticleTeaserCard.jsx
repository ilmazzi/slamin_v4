import styles from './ArticleTeaserCard.module.css'
import PillButton from './PillButton.jsx'
import SocialActions from './SocialActions.jsx'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import {
  mediaArrowListFigmaIcon,
} from '../../assets/home/media/index.js'

function estimateReadingMinutes(article) {
  const sourceText = `${article?.title ?? ''} ${article?.excerpt ?? ''}`.trim()
  if (!sourceText) {
    return 1
  }

  const words = sourceText.split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.round(words / 200))
}

function buildArticleMetrics(article) {
  return {
    views: Number.isFinite(Number(article?.views_count)) ? Number(article.views_count) : 0,
    likes: Number.isFinite(Number(article?.likes_count)) ? Number(article.likes_count) : 0,
    comments: Number.isFinite(Number(article?.comments_count)) ? Number(article.comments_count) : 0,
  }
}

export default function ArticleTeaserCard({ article, locale, t }) {
  const readingMinutes = estimateReadingMinutes(article)
  const publishedDate = formatShortPublishedDate(article?.published_at, locale, '-')
  const authorName = article?.author?.nickname ?? article?.author?.display_name ?? t('articles.all.no_author')
  const excerpt = article?.excerpt ?? t('articles.all.no_excerpt')
  const metrics = buildArticleMetrics(article)
  const detailId = article?.slug ?? article?.id

  return (
    <>
      <article className={styles.desktopCard}>
        <div
          className={styles.cover}
          style={
            article?.cover_image_url
              ? {
                  backgroundImage: `url("${article.cover_image_url}")`,
                }
              : undefined
          }
        />

        <div className={styles.content}>
          <div className={styles.top}>
            <div className={styles.authorRow}>
              <span className={styles.authorDot} aria-hidden="true" />
              <span className={styles.authorName}>{authorName}</span>
            </div>

            <div className={styles.textRow}>
              <h3 className={styles.cardTitle}>{article?.title ?? t('articles.all.no_title')}</h3>
              <p className={styles.cardExcerpt}>{excerpt}</p>
            </div>
          </div>

          <div className={styles.bottom}>
            <PillButton
              href={detailId ? `/articles/${detailId}` : '/articles'}
              label={t('common.discover_more')}
              variant="accent"
              size="articleTeaser"
              iconSrc={mediaArrowListFigmaIcon}
              className={styles.readLink}
            />
          </div>
        </div>

        <div className={styles.divider} aria-hidden="true" />

        <aside className={styles.side}>
          <div className={styles.cardMeta}>
            <span>{publishedDate}</span>
            <span>{t('articles.featured.reading_time').replace(':minutes', String(readingMinutes))}</span>
          </div>

          <div className={styles.socialBlock}>
            <SocialActions
              article={article}
              metrics={metrics}
              layout="column"
              shareLabel={t('articles.featured.share')}
              reportLabel={t('articles.featured.save')}
              variant="list"
              metricsClassName={styles.interactions}
              metricClassName={styles.metric}
              viewIconClassName={styles.metricIconView}
              likeIconClassName={styles.metricIconLike}
              commentIconClassName={styles.metricIconComment}
              buttonsClassName={styles.actions}
              shareIconClassName={styles.socialIconShare}
              reportIconClassName={styles.socialIconReport}
            />
          </div>
        </aside>
      </article>

      <article className={styles.mobileCard}>
        <div
          className={styles.mobileCover}
          style={
            article?.cover_image_url
              ? {
                  backgroundImage: `url("${article.cover_image_url}")`,
                }
              : undefined
          }
        />

        <div className={styles.mobileContent}>
          <div className={styles.mobileTop}>
            <div className={styles.mobileAuthorRow}>
              <span className={styles.mobileAuthorDot} aria-hidden="true" />
              <span className={styles.mobileAuthorName}>{authorName}</span>
              <div className={styles.mobileMeta}>
                <span>{publishedDate}</span>
                <span>{t('articles.featured.reading_time').replace(':minutes', String(readingMinutes))}</span>
              </div>
            </div>

            <h3 className={styles.mobileTitle}>{article?.title ?? t('articles.all.no_title')}</h3>
            <p className={styles.mobileExcerpt}>{excerpt}</p>
          </div>

          <div className={styles.mobileBottom}>
            <PillButton
              href={detailId ? `/articles/${detailId}` : '/articles'}
              label={t('common.discover_more')}
              variant="accent"
              size="articleTeaserMobile"
              iconSrc={mediaArrowListFigmaIcon}
              className={styles.mobileReadLink}
            />

            <SocialActions
              article={article}
              metrics={metrics}
              metricsPlacement="end"
              className={styles.mobileIconsRow}
              shareLabel={t('articles.featured.share')}
              reportLabel={t('articles.featured.save')}
              variant="list"
              metricsClassName={styles.mobileMetrics}
              metricClassName={styles.mobileMetric}
              viewIconClassName={styles.mobileMetricIcon}
              likeIconClassName={styles.mobileMetricIcon}
              commentIconClassName={styles.mobileMetricIcon}
              buttonsClassName={styles.mobileActions}
              shareIconClassName={styles.mobileSocialIcon}
              reportIconClassName={styles.mobileSocialIcon}
            />
          </div>
        </div>
      </article>
    </>
  )
}
