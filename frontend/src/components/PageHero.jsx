import styles from './PageHero.module.css'
import PillButton from './shared/PillButton.jsx'

export default function PageHero({
  title,
  lead,
  leadPrefix,
  leadEmphasis,
  leadSuffix,
  ctaLabel,
  ctaHref,
  hideCta = false,
  showCornerMark = false,
  ctaOnClick,
  ctaVariant = 'dark',
}) {
  const leadBody = lead ?? (
    <>
      {leadPrefix}
      <span>{leadEmphasis}</span>
      {leadSuffix}
    </>
  )
  const leadClassName = lead ? `${styles.lead} ${styles.leadRich}` : styles.lead
  const mobileLeadClassName = lead ? `${styles.mobileLead} ${styles.leadRich}` : styles.mobileLead

  return (
    <section className={styles.hero}>
      <div className={styles.topBand}>
        <h1 className={styles.title}>{title}</h1>
      </div>

      <div className={styles.mobileHero}>
        <h1 className={styles.mobileTitle}>{title}</h1>
        <p className={mobileLeadClassName}>{leadBody}</p>
        {!hideCta ? (
          <PillButton
            label={ctaLabel}
            href={ctaHref}
            onClick={ctaOnClick}
            variant={ctaVariant}
            className={styles.mobileCtaButton}
          />
        ) : null}
      </div>

      <div className={styles.bottomSection}>
        <div className={styles.bottomFrame}>
          <p className={leadClassName}>{leadBody}</p>
        </div>
        <div className={styles.desktopPolygon} aria-hidden="true" />
      </div>

      {!hideCta ? (
        <div className={styles.ctaRow}>
          <PillButton
            label={ctaLabel}
            href={ctaHref}
            onClick={ctaOnClick}
            variant={ctaVariant}
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
