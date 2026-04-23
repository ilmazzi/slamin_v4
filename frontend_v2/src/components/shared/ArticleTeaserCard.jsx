import styles from './ArticleTeaserCard.module.css'
import PillButton from './PillButton.jsx'
import ArticleSocialActions from './ArticleSocialActions.jsx'
import {
  mediaArrowListFigmaIcon,
  mediaCommentsDesktopIcon,
  mediaLikeDesktopIcon,
  mediaViewsDesktopIcon,
} from '../../assets/home/media/index.js'

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

export default function ArticleTeaserCard({ article, locale, t, href = '/articles' }) {
  const readingMinutes = estimateReadingMinutes(article)
  const publishedDate = formatPublishedDate(article?.published_at, locale)
  const authorName = t('articles.featured.author_name')
  const excerpt = article?.excerpt ?? t('articles.all.no_excerpt')

  return (
    <>
      <article className={styles.desktopCard}>
        <div
          className={styles.cover}
          style={
            article?.featured_image
              ? {
                  backgroundImage: `url("${article.featured_image}")`,
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
              href={href}
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
            <div className={styles.interactions}>
              <div className={styles.metric}>
                <span>{article?.metrics?.views ?? 0}</span>
                <img src={mediaViewsDesktopIcon} alt="" className={styles.metricIconView} />
              </div>
              <div className={styles.metric}>
                <span>{article?.metrics?.likes ?? 0}</span>
                <img src={mediaLikeDesktopIcon} alt="" className={styles.metricIconLike} />
              </div>
              <div className={styles.metric}>
                <span>{article?.metrics?.comments ?? 0}</span>
                <img src={mediaCommentsDesktopIcon} alt="" className={styles.metricIconComment} />
              </div>
            </div>

            <ArticleSocialActions
              article={article}
              shareLabel={t('articles.featured.share')}
              reportLabel={t('articles.featured.save')}
              variant="list"
              className={styles.actions}
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
            article?.featured_image
              ? {
                  backgroundImage: `url("${article.featured_image}")`,
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
              href={href}
              label={t('common.discover_more')}
              variant="accent"
              size="articleTeaserMobile"
              iconSrc={mediaArrowListFigmaIcon}
              className={styles.mobileReadLink}
            />

            <div className={styles.mobileIconsRow}>
              <ArticleSocialActions
                article={article}
                shareLabel={t('articles.featured.share')}
                reportLabel={t('articles.featured.save')}
                variant="list"
                className={styles.mobileActions}
                shareIconClassName={styles.mobileSocialIcon}
                reportIconClassName={styles.mobileSocialIcon}
              />

              <div className={styles.mobileMetrics}>
                <div className={styles.mobileMetric}>
                  <span>{article?.metrics?.views ?? 0}</span>
                  <img src={mediaViewsDesktopIcon} alt="" className={styles.mobileMetricIcon} />
                </div>
                <div className={styles.mobileMetric}>
                  <span>{article?.metrics?.likes ?? 0}</span>
                  <img src={mediaLikeDesktopIcon} alt="" className={styles.mobileMetricIcon} />
                </div>
                <div className={styles.mobileMetric}>
                  <span>{article?.metrics?.comments ?? 0}</span>
                  <img src={mediaCommentsDesktopIcon} alt="" className={styles.mobileMetricIcon} />
                </div>
              </div>
            </div>
          </div>
        </div>
      </article>
    </>
  )
}
