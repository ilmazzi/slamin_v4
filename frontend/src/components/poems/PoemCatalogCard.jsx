import { formatShortPublishedDate } from '../../app/formatters/date.js'
import PillButton from '../shared/PillButton.jsx'
import SocialActions from '../shared/SocialActions.jsx'
import styles from './PoemCatalogCard.module.css'

function buildPoemMetrics(poem) {
  return {
    views: Number.isFinite(Number(poem?.views_count)) ? Number(poem.views_count) : 0,
    likes: Number.isFinite(Number(poem?.likes_count)) ? Number(poem.likes_count) : 0,
    comments: Number.isFinite(Number(poem?.comments_count)) ? Number(poem.comments_count) : 0,
  }
}

export default function PoemCatalogCard({ poem, locale, t }) {
  const authorName = poem?.author?.nickname ?? poem?.author?.display_name ?? t('poems.all.no_author')
  const publishedDate = formatShortPublishedDate(poem?.published_at, locale, '-')
  const excerpt = poem?.description || poem?.content || t('poems.all.no_excerpt')
  const title = poem?.title ?? t('poems.all.no_title')
  const detailId = poem?.slug ?? poem?.id
  const metrics = buildPoemMetrics(poem)

  return (
    <article className={styles.card}>
      <div className={styles.top}>
        <div className={styles.authorRow}>
          <span className={styles.authorDot} aria-hidden="true" />
          <span className={styles.authorName}>{authorName}</span>
        </div>
        <span className={styles.date}>{publishedDate}</span>
      </div>

      <div className={styles.body}>
        <h3 className={styles.title}>&ldquo;{title}&rdquo;</h3>
        <p className={styles.excerpt}>{excerpt}</p>
      </div>

      <div className={styles.footer}>
        <PillButton
          href={detailId ? `/poems/${detailId}` : '/poems'}
          label={t('poems.all.read_text')}
          variant="accent"
          size="poemCatalog"
          icon="arrow"
          className={styles.readLink}
        />

        <SocialActions
          target={poem}
          contentType="poem"
          apiBasePath="/poems"
          publicBasePath="/poems"
          metrics={metrics}
          shareLabel={t('poems.actions.share')}
          reportLabel={t('poems.actions.report')}
          variant="list"
          className={styles.social}
          metricsClassName={styles.metrics}
          metricClassName={styles.metric}
          metricValueClassName={styles.metricValue}
          viewIconClassName={styles.metricIcon}
          likeIconClassName={styles.metricIcon}
          commentIconClassName={styles.metricIcon}
          buttonsClassName={styles.actionButtons}
          iconClassName={styles.actionIcon}
          shareIconClassName={styles.actionIcon}
          reportIconClassName={styles.actionIcon}
        />
      </div>
    </article>
  )
}
