import { useI18n } from '../../app/hooks/useI18n.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { richTextToPlainText } from '../../app/content/richText.js'
import SocialActions from '../shared/SocialActions.jsx'
import styles from './FeaturedPoemCard.module.css'

export default function FeaturedPoemCard({ poem }) {
  const { t } = useI18n()

  if (!poem) {
    return null
  }

  const authorName =
    poem?.author?.nickname ??
    poem?.author?.display_name ??
    'Slamin'

  const authorAvatar = resolvePublicUrl(poem?.author?.avatar_url)
  const publishedLabel = poem?.published_at ?? ''
  const categoryName = poem?.category?.name ?? ''
  const typeName = poem?.type?.name ?? ''
  const imageUrl = poem?.image_url ? resolvePublicUrl(poem.image_url) : ''
  const shareLabel = t('poems.actions.share')
  const reportLabel = t('poems.actions.report')
  const metrics = {
    views: poem?.views_count ?? 0,
    likes: poem?.likes_count ?? 0,
    comments: poem?.comments_count ?? 0,
  }

  return (
    <article className={styles.card}>
      <div className={styles.top}>
        <div className={styles.authorRow}>
          {authorAvatar ? (
            <img src={authorAvatar} alt="" className={styles.authorDot} />
          ) : (
            <span className={styles.authorDot} aria-hidden="true" />
          )}
          <p className={styles.authorName}>{authorName}</p>
        </div>

        <p className={styles.meta}>{publishedLabel}</p>
      </div>

      <div className={styles.media}>
        {imageUrl ? (
          <img src={imageUrl} alt="" className={styles.mediaImage} />
        ) : (
          <div className={styles.mediaFallback} aria-hidden="true" />
        )}
      </div>

      <div className={styles.body}>
        <h2 className={styles.title}>{poem?.title}</h2>
        <p className={styles.excerpt}>{poem?.description || richTextToPlainText(poem?.content)}</p>
      </div>

      <div className={styles.footer}>
        <div className={styles.badges}>
          {categoryName ? <span className={styles.badge}>{categoryName}</span> : null}
          {typeName ? <span className={styles.badge}>{typeName}</span> : null}
        </div>

        <div className={styles.actions}>
          <SocialActions
            target={poem}
            contentType="poem"
            metrics={metrics}
            shareLabel={shareLabel}
            reportLabel={reportLabel}
            publicBasePath="/poems"
            apiBasePath="/poems"
            variant="featured"
            className={styles.socialActions}
            iconClassName={styles.socialIcon}
            viewIconClassName={styles.metricIcon}
            likeIconClassName={styles.metricIcon}
            commentIconClassName={styles.metricIcon}
          />
        </div>
      </div>
    </article>
  )
}
