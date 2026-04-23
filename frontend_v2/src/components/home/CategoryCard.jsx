import DiscoverButton from './DiscoverButton.jsx'
import styles from './CategoryCard.module.css'

export default function CategoryCard({
  title,
  href,
  accent,
  tone,
  ctaLabel,
  imageSrc,
  imageSrcSet,
  imageSizes = '(max-width: 768px) 100vw, 50vw',
}) {
  return (
    <article
      className={[styles.card, styles[tone] || ''].join(' ')}
      style={{ '--card-accent': accent }}
    >
      {imageSrc ? (
        <img
          src={imageSrc}
          srcSet={imageSrcSet || undefined}
          sizes={imageSrcSet ? imageSizes : undefined}
          alt=""
          className={styles.backgroundImage}
          aria-hidden="true"
          loading="lazy"
          decoding="async"
        />
      ) : null}
      <div className={styles.texture} aria-hidden="true" />
      <div className={styles.overlay} aria-hidden="true" />
      <div className={styles.inner}>
        <h3 className={styles.title}>{title}</h3>
        <div className={styles.desktopButton}>
          <DiscoverButton href={href} variant="light" size="desktop">
            {ctaLabel}
          </DiscoverButton>
        </div>
        <div className={styles.mobileButton}>
          <DiscoverButton href={href} variant="light" size="mobile">
            {ctaLabel}
          </DiscoverButton>
        </div>
      </div>
    </article>
  )
}
