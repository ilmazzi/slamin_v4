import { resolvePublicUrl } from '../../app/api/url.js'
import styles from './SlideCard.module.css'

export default function SlideCard({
  title,
  description,
  city,
  dateLabel,
  imageUrl,
  imageAlt,
  href,
  openAriaLabel,
  onClick,
  className = '',
}) {
  const resolvedImage = imageUrl ? resolvePublicUrl(imageUrl) : ''

  return (
    <article className={`${styles.card} ${className}`.trim()} onClick={onClick}>
      <div className={styles.picture}>
        {resolvedImage ? (
          <img src={resolvedImage} alt={imageAlt} className={styles.pictureAsset} />
        ) : (
          <div className={styles.pictureFallback} aria-hidden="true" />
        )}
      </div>

      <div className={styles.info}>
        <div className={styles.titleWrap}>
          <h4 className={styles.title}>{title}</h4>
          <p className={styles.description}>{description}</p>
        </div>

        <div className={styles.bottom}>
          <div className={styles.left}>
            <strong className={styles.city}>{city}</strong>
            <span className={styles.line} aria-hidden="true" />
            <small className={styles.date}>{dateLabel}</small>
          </div>

          <a href={href} className={styles.right} aria-label={openAriaLabel}>
            <span className={styles.chevron} aria-hidden="true">›</span>
          </a>
        </div>
      </div>
    </article>
  )
}