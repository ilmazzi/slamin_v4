import { Link } from 'react-router-dom'
import styles from './HomePublishPromo.module.css'

const isInternalHref = (href) => typeof href === 'string' && href.startsWith('/') && !href.startsWith('//')

function PromoButton({ href, label }) {
  const content = (
    <>
      <span className={styles.buttonLabel}>{label}</span>
      <span className={styles.buttonIconWrap} aria-hidden="true">
        <span className={styles.buttonChevron} />
      </span>
    </>
  )

  if (isInternalHref(href)) {
    return (
      <Link to={href} className={styles.button}>
        {content}
      </Link>
    )
  }

  return (
    <a href={href} className={styles.button}>
      {content}
    </a>
  )
}

export default function HomePublishPromo({
  title,
  subtitle,
  ctaLabel,
  href = '/pubblica',
}) {
  return (
    <section className={styles.section}>
      <div className={styles.card}>
        <div className={styles.content}>
          <div className={styles.copy}>
            <h2 className={styles.title}>{title}</h2>
            <p className={styles.subtitle}>{subtitle}</p>
          </div>
          <PromoButton href={href} label={ctaLabel} />
        </div>
      </div>
    </section>
  )
}
