import { Link } from 'react-router-dom'
import styles from './HomeIntro.module.css'

export default function HomeIntro({
  accent,
  descriptionPrefix,
  descriptionEmphasis,
  descriptionMiddle,
  descriptionTail,
  ctaLabel,
}) {
  const [tailEmphasis = '', ...tailRest] = descriptionTail.split(' ')

  return (
    <>
      <section className={styles.header}>
        <div className={styles.copy}>
          <h2 className={styles.accent}>{accent}</h2>
          <div className={styles.desktopText} aria-hidden="true">
            <p className={styles.line}>
              <span>{descriptionPrefix} </span>
              <span className={styles.emphasis}>{descriptionEmphasis}</span>
            </p>
            <p className={styles.line}>
              <span>{descriptionMiddle} </span>
              <span className={styles.emphasis}>{tailEmphasis}</span>
              <span> {tailRest.join(' ')}</span>
            </p>
          </div>

          <div className={styles.mobileText} aria-hidden="true">
            <p className={styles.line}>
              <span>{descriptionPrefix} </span>
              <span className={styles.emphasis}>{descriptionEmphasis}</span>
            </p>
            <p className={styles.line}>
              <span>{descriptionMiddle} </span>
              <span className={styles.emphasis}>{tailEmphasis}</span>
            </p>
            <p className={styles.line}>{tailRest.join(' ')}</p>
          </div>
        </div>
        <div className={styles.shape} aria-hidden="true" />
      </section>

      <section className={styles.ctaRow}>
        <Link to="/dashboard" className={styles.button}>
          <span>{ctaLabel}</span>
          <span className={styles.buttonArrow} aria-hidden="true">
            &gt;
          </span>
        </Link>
      </section>
    </>
  )
}
