import styles from './PageHero.module.css'
import PillButton from './shared/PillButton.jsx'

export default function PageHero({
  title,
  leadPrefix,
  leadEmphasis,
  leadSuffix,
  ctaLabel,
  ctaHref,
  hideCta = false,
  hideTitle = false,
  showCornerMark = false,
  ctaOnClick,
  ctaVariant = 'dark',
}) {
  return (
    <section className={styles.hero}>
      <div className={[styles.topBand, hideTitle ? styles.topBandCompact : ''].join(' ')}>
        {hideTitle ? null : <h1 className={styles.title}>{title}</h1>}
      </div>

      <div className={styles.mobileHero}>
        <h1 className={styles.mobileTitle}>{hideTitle ? null : title}</h1>
        <p className={styles.mobileLead}>
          {leadPrefix}
          <span>{leadEmphasis}</span>
          {leadSuffix}
        </p>
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
          {hideTitle ? (
            <h1 className={styles.lead}>
              {leadPrefix}
              <span>{leadEmphasis}</span>
              {leadSuffix}
            </h1>
          ) : (
            <p className={styles.lead}>
              {leadPrefix}
              <span>{leadEmphasis}</span>
              {leadSuffix}
            </p>
          )}
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