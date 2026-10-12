import styles from './PoemTeaserCard.module.css'
import PillButton from './PillButton.jsx'
import SocialActions from './SocialActions.jsx'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import {
  mediaArrowListFigmaIcon,
} from '../../assets/home/media/index.js'
import { resolvePublicUrl } from '../../app/api/url.js'


function estimateReadingMinutes(poem) {
  const sourceText = `${poem?.title ?? ''} ${poem?.excerpt ?? ''}`.trim()
  if (!sourceText) {
    return 1
  }

  const words = sourceText.split(/\s+/).filter(Boolean).length
  return Math.max(1, Math.round(words / 200))
}

function buildPoemMetrics(poem) {
  return {
    views: Number.isFinite(Number(poem?.views_count)) ? Number(poem.views_count) : 0,
    likes: Number.isFinite(Number(poem?.likes_count)) ? Number(poem.likes_count) : 0,
    comments: Number.isFinite(Number(poem?.comments_count)) ? Number(poem.comments_count) : 0,
  }
}

export default function PoemTeaserCard({ poem, locale, t }) {
  const readingMinutes = estimateReadingMinutes(poem)
  const publishedDate = formatShortPublishedDate(poem?.published_at, locale, '-')
  const authorName = poem?.author?.nickname ?? poem?.author?.display_name ?? t('poems.all.no_author')
  const excerpt = poem?.excerpt ?? t('poems.all.no_excerpt')
  const metrics = buildPoemMetrics(poem)
  const coverUrl = poem?.image_url ? resolvePublicUrl(poem.image_url) : ''
  const detailId = poem?.slug ?? poem?.id

  return (
    <>
      <article className={styles.desktopCard}>
        <div
          className={styles.cover}
          style={
            coverUrl
              ? {
                  backgroundImage: `url("${coverUrl}")`,
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
              <h3 className={styles.cardTitle}>{poem?.title ?? t('poems.all.no_title')}</h3>
              <p className={styles.cardExcerpt}>{excerpt}</p>
            </div>
          </div>

          <div className={styles.bottom}>
            <PillButton
              href={detailId ? `/poems/${detailId}` : '/poems'}
              label={t('common.read')}
              variant="accent"
              size="poemTeaser"
              iconSrc={mediaArrowListFigmaIcon}
              className={styles.readLink}
            />
          </div>
        </div>

        <div className={styles.divider} aria-hidden="true" />

        <aside className={styles.side}>
          <div className={styles.cardMeta}>
            <span>{publishedDate}</span>
            <span>{t('poems.featured.reading_time').replace(':minutes', String(readingMinutes))}</span>
          </div>

          <div className={styles.socialBlock}>
            <SocialActions
              target={poem}
              contentType="poem"
              apiBasePath="/poems"
              publicBasePath="/poems"
              metrics={metrics}
              layout="column"
              shareLabel={t('poems.featured.share')}
              reportLabel={t('poems.featured.save')}
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
            coverUrl
              ? {
                  backgroundImage: `url("${coverUrl}")`,
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
                <span>{t('poems.featured.reading_time').replace(':minutes', String(readingMinutes))}</span>
              </div>
            </div>

            <h3 className={styles.mobileTitle}>{poem?.title ?? t('poems.all.no_title')}</h3>
            <p className={styles.mobileExcerpt}>{excerpt}</p>
          </div>

          <div className={styles.mobileBottom}>
            <PillButton
              href={detailId ? `/poems/${detailId}` : '/poems'}
              label={t('common.discover_more')}
              variant="accent"
              size="poemTeaserMobile"
              iconSrc={mediaArrowListFigmaIcon}
              className={styles.mobileReadLink}
            />

            <SocialActions
              target={poem}
              contentType="poem"
              apiBasePath="/poems"
              publicBasePath="/poems"
              metrics={metrics}
              metricsPlacement="end"
              className={styles.mobileIconsRow}
              shareLabel={t('poems.featured.share')}
              reportLabel={t('poems.featured.save')}
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
