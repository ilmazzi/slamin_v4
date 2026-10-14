import { Link } from 'react-router-dom'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import SocialActions from './SocialActions.jsx'
import styles from './SlideCard.module.css'

const isInternalHref = (href) => typeof href === 'string' && href.startsWith('/') && !href.startsWith('//')

export default function SlideCard({
  title,
  description,
  city,
  dateLabel,
  imageUrl,
  imageAlt,
  badge = '',
  organizerName = '',
  organizerAvatarUrl = '',
  href,
  openAriaLabel,
  onClick,
  className = '',
  item = null,
}) {
  const { t } = useI18n()
  const resolvedImage = imageUrl ? resolvePublicUrl(imageUrl) : ''
  const resolvedOrganizerAvatar = resolvePublicUrl(organizerAvatarUrl)
  const hasSocial = Boolean(item?.id)

  return (
    <article className={`${styles.card} ${className}`.trim()} onClick={onClick}>
      <div className={styles.picture}>
        {resolvedImage ? (
          <img src={resolvedImage} alt={imageAlt} className={styles.pictureAsset} />
        ) : (
          <div className={styles.pictureFallback} aria-hidden="true" />
        )}
        {badge ? <span className={styles.badge}>{badge}</span> : null}
        {organizerName ? (
          <span className={styles.organizer}>
            {resolvedOrganizerAvatar ? (
              <img src={resolvedOrganizerAvatar} alt="" className={styles.organizerAvatar} />
            ) : (
              <span className={styles.organizerAvatar} aria-hidden="true" />
            )}
            <span className={styles.organizerName}>{organizerName}</span>
          </span>
        ) : null}
      </div>

      <div className={styles.info}>
        <div className={styles.titleWrap}>
          <h4 className={styles.title}>{title}</h4>
          <p className={styles.description}>{description}</p>
        </div>

        {hasSocial ? (
          <SocialActions
            target={item}
            contentType="event"
            apiBasePath="/events"
            publicBasePath="/events"
            variant="list"
            showBookmark
            metrics={{
              views: item?.views_count ?? 0,
              likes: item?.likes_count ?? 0,
              comments: item?.comments_count ?? 0,
            }}
            metricsOrder={['views', 'likes', 'comments']}
            shareLabel={t('events.ui.share')}
            reportLabel={t('events.ui.report')}
            bookmarkLabel={item?.is_bookmarked ? t('events.unsave_event') : t('events.save_event')}
            className={styles.social}
            metricsClassName={styles.socialMetrics}
            metricClassName={styles.socialMetric}
            metricValueClassName={styles.socialMetricValue}
            buttonsClassName={styles.socialButtons}
          />
        ) : null}

        <div className={styles.bottom}>
          <div className={styles.left}>
            <strong className={styles.city}>{city}</strong>
            <span className={styles.line} aria-hidden="true" />
            <small className={styles.date}>{dateLabel}</small>
          </div>

          {isInternalHref(href) ? (
            <Link to={href} className={styles.right} aria-label={openAriaLabel}>
              <span className={styles.chevron} aria-hidden="true">›</span>
            </Link>
          ) : (
            <a href={href} className={styles.right} aria-label={openAriaLabel}>
              <span className={styles.chevron} aria-hidden="true">›</span>
            </a>
          )}
        </div>
      </div>
    </article>
  )
}
