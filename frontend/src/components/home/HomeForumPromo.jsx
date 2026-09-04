import { Link } from 'react-router-dom'
import styles from './HomeForumPromo.module.css'

const forumImageDesktop =
  'https://www.figma.com/api/mcp/asset/df9530d9-e9d9-4665-b9b6-ebef5efe5a8b'
const forumImageMobile =
  'https://www.figma.com/api/mcp/asset/aa8c41a3-52d1-4fb4-a487-8302d01d96d8'
const forumArrowDesktop =
  'https://www.figma.com/api/mcp/asset/fa640ce6-be0a-4701-b3f6-9deda65f9f44'
const forumArrowMobile =
  'https://www.figma.com/api/mcp/asset/3891db4f-baa3-4b03-88e7-1c67ed58f4c8'

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
          <span className={styles.buttonIconWrap} aria-hidden="true">
            <img src={forumArrowDesktop} alt="" className={styles.buttonIconDesktop} />
            <img src={forumArrowMobile} alt="" className={styles.buttonIconMobile} />
          </span>
        </Link>
      </div>
    </section>
  )
}
