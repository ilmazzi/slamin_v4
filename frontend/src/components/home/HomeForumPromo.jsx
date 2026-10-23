import { Link } from 'react-router-dom'
import styles from './HomeForumPromo.module.css'

const forumImageDesktop = '/images/home/forum.webp'
const forumImageMobile = '/images/home/forum.webp'

export default function HomeForumPromo({
  title,
  description,
  ctaLabel,
  imageDesktopSrc = forumImageDesktop,
  imageMobileSrc = forumImageMobile,
  imageDesktopSrcSet,
  imageMobileSrcSet,
}) {
  return (
    <section className={styles.section}>
      <div className={styles.image} aria-hidden="true">
        <picture>
          {imageMobileSrcSet ? <source media="(max-width: 768px)" srcSet={imageMobileSrcSet} /> : null}
          <img
            src={imageMobileSrc || imageDesktopSrc}
            alt=""
            className={styles.mobileImageAsset}
            loading="lazy"
            decoding="async"
          />
        </picture>
        <img
          src={imageDesktopSrc}
          srcSet={imageDesktopSrcSet || undefined}
          sizes={imageDesktopSrcSet ? '(max-width: 768px) 0px, 50vw' : undefined}
          alt=""
          className={styles.desktopImageAsset}
          loading="lazy"
          decoding="async"
        />
      </div>
      <div className={styles.copy}>
        <div className={styles.textWrap}>
          <h2 className={styles.title}>{title}</h2>
          <p className={styles.description}>{description}</p>
        </div>
        <Link to="/forum" className={styles.button}>
          <span className={styles.buttonLabel}>{ctaLabel}</span>
        </Link>
      </div>
    </section>
  )
}
