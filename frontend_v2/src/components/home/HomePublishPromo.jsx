import styles from './HomePublishPromo.module.css'

const publishDesktopAsset = 'https://www.figma.com/api/mcp/asset/1168c59c-bf3a-43ac-b7c9-b6d42d8aee00'
const publishDesktopArrow = 'https://www.figma.com/api/mcp/asset/1b3486e9-08aa-4feb-baca-b9059e6a855e'
const publishMobileAsset = 'https://www.figma.com/api/mcp/asset/95bb3a1f-aecf-49c2-bdab-83905d0e8962'
const publishMobileArrow = 'https://www.figma.com/api/mcp/asset/75c55ebb-2286-4aa9-8772-4cdfb201ea7d'

export default function HomePublishPromo({
  title,
  subtitle,
  ctaLabel,
  href = '/pubblica',
}) {
  return (
    <section className={styles.section}>
      <div className={styles.card}>
        <div className={styles.backdrop} aria-hidden="true">
          <img
            src={publishDesktopAsset}
            alt=""
            className={styles.desktopBackdropAsset}
            aria-hidden="true"
          />
          <img
            src={publishMobileAsset}
            alt=""
            className={styles.mobileBackdropAsset}
            aria-hidden="true"
          />
        </div>
        <div className={styles.content}>
          <div className={styles.copy}>
            <h2 className={styles.title}>{title}</h2>
            <p className={styles.subtitle}>{subtitle}</p>
          </div>
          <a href={href} className={styles.button}>
            <span className={styles.buttonLabel}>{ctaLabel}</span>
            <span className={styles.buttonIconWrap} aria-hidden="true">
              <img
                src={publishDesktopArrow}
                alt=""
                className={styles.buttonIconDesktop}
                aria-hidden="true"
              />
              <img
                src={publishMobileArrow}
                alt=""
                className={styles.buttonIconMobile}
                aria-hidden="true"
              />
            </span>
          </a>
        </div>
      </div>
    </section>
  )
}
