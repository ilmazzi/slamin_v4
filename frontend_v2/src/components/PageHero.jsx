import styles from './PageHero.module.css'
import PillButton from './shared/PillButton.jsx'

export default function PageHero({
  title,
  leadPrefix,
  leadEmphasis,
  ctaLabel,
  ctaHref,
  hideCta = false,
  showCornerMark = false,
  ctaOnClick,
}) {
  return (
    <section className={styles.hero}>
      <div className={styles.topBand}>
        <h1 className={styles.title}>{title}</h1>
      </div>

      <div className={styles.mobileHero}>
        <h1 className={styles.mobileTitle}>{title}</h1>
        <p className={styles.mobileLead}>
          {leadPrefix}
          <span>{leadEmphasis}</span>
        </p>
        {!hideCta ? (
          <PillButton
            label={ctaLabel}
            href={ctaHref}
            onClick={ctaOnClick}
            className={styles.mobileCtaButton}
          />
        ) : null}
      </div>

      <div className={styles.bottomSection}>
        <div className={styles.bottomFrame}>
          <p className={styles.lead}>
            {leadPrefix}
            <span>{leadEmphasis}</span>
          </p>
        </div>
        <div className={styles.desktopPolygon} aria-hidden="true" />
      </div>

      {!hideCta ? (
        <div className={styles.ctaRow}>
          <PillButton
            label={ctaLabel}
            href={ctaHref}
            onClick={ctaOnClick}
            className={styles.ctaButton}
          />
        </div>
      ) : null}
      <div className={styles.mobileBottom} aria-hidden="true">
        <div className={styles.mobileBottomPolygon} />
      </div>
      {showCornerMark ? <span className={styles.cornerMark} aria-hidden="true" /> : null}
    </section>
  )
}