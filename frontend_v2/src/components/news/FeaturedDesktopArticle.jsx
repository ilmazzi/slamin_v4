import ArticleSocialActions from '../shared/ArticleSocialActions.jsx'
import PillButton from '../shared/PillButton.jsx'
import {
  mediaArrowFeaturedFigmaIcon,
  mediaCommentsDesktopIcon,
  mediaLikeDesktopIcon,
  mediaViewsDesktopIcon,
} from '../../assets/home/media/index.js'
import styles from './FeaturedDesktopArticle.module.css'

function formatPublishedDate(value, locale) {
  if (!value) {
    return '—'
  }

  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) {
    return '—'
  }

  return parsed.toLocaleDateString(locale, {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

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

export default function FeaturedDesktopArticle({ article, locale, t }) {
  if (!article) {
    return null
  }

  const authorName = t('articles.featured.author_name')
  const readingMinutes = estimateReadingMinutes(article)
  const publishedDate = formatPublishedDate(article.published_at, locale)

  return (
    <section className={styles.featuredSection}>
      <article className={styles.featuredCard}>
        <div className={styles.top}>
          <div
            className={styles.poster}
            style={
              article.featured_image
                ? {
                    backgroundImage: `url("${article.featured_image}")`,
                  }
                : undefined
            }
          >
            {!article.featured_image ? (
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
                <h2 className={styles.title}>{article.title ?? t('articles.featured.no_title')}</h2>
                <p className={styles.excerpt}>{article.excerpt ?? t('articles.featured.no_excerpt')}</p>
              </div>
            </div>

            <div className={styles.contentBottom}>
              <PillButton
                href="/articles"
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
            <div className={styles.metrics}>
              <span className={`${styles.metric} ${styles.metricView}`}>
                <span>{article.metrics?.views ?? 0}</span>
                <img src={mediaViewsDesktopIcon} alt="" className={styles.metricIconView} />
              </span>
              <span className={`${styles.metric} ${styles.metricLike}`}>
                <span>{article.metrics?.likes ?? 0}</span>
                <img src={mediaLikeDesktopIcon} alt="" className={styles.metricIconLike} />
              </span>
              <span className={`${styles.metric} ${styles.metricComment}`}>
                <span>{article.metrics?.comments ?? 0}</span>
                <img src={mediaCommentsDesktopIcon} alt="" className={styles.metricIconComment} />
              </span>
            </div>

            <ArticleSocialActions
              article={article}
              shareLabel={t('articles.featured.share')}
              reportLabel={t('articles.featured.save')}
              variant="featured"
              className={styles.actions}
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
