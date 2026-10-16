import MainLayout from '../../components/layout/MainLayout.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './AboutPage.module.css'

const stats = [
  ['321', 'about.dashboard.poems'],
  ['321', 'about.dashboard.events'],
  ['321', 'about.dashboard.views'],
  ['321', 'about.dashboard.likes'],
]

export default function AboutPage() {
  const { t } = useI18n()

  return (
    <MainLayout mainClassName={styles.page}>
      <section className={styles.hero}>
        <h1>{t('about.title')}</h1>
      </section>

      <section className={styles.statement}>
        <div className={styles.statementText}>
          <span>{t('about.statement_first')}</span>
          <strong>{t('about.statement_second')}</strong>
        </div>
        <img src="/about/hero-wedge.svg" alt="" width="434" height="287" />
      </section>

      <section className={styles.entities} aria-label={t('about.entities_label')}>
        <article className={styles.entityCard}>
          <img
            className={styles.slaminLogo}
            src="/about/slamin-logo.svg"
            alt="Slam In"
            width="344"
            height="199"
          />
          <div className={styles.entityCopy}>
            <h2>Slam In It OÜ</h2>
            <p>{t('about.slamin_description')}</p>
            <p>
              {t('about.contribute')}{' '}
              <a href="mailto:mail@slamin.it">mail@slamin.it</a>
            </p>
          </div>
        </article>

        <article className={styles.entityCard}>
          <img
            className={styles.haevolLogo}
            src="/about/haevol-logo.svg"
            alt="Hævol"
            width="363"
            height="206"
          />
          <div className={styles.entityCopy}>
            <h2>Hævol</h2>
            <p>{t('about.haevol_description')}</p>
          </div>
        </article>
      </section>

      <section className={styles.dashboard}>
        <div className={styles.dashboardLead}>
          <h2>{t('about.dashboard.title')}</h2>
          <div className={styles.profilePill}>
            <img src="/about/profile.png" alt="" width="52" height="52" />
            <strong>{t('about.dashboard.person')}</strong>
            <img className={styles.profileArrow} src="/about/arrow.svg" alt="" width="44" height="31" />
          </div>
        </div>

        <div className={styles.stats}>
          {stats.map(([value, labelKey]) => (
            <div className={styles.stat} key={labelKey}>
              <strong>{value}</strong>
              <span>{t(labelKey)}</span>
            </div>
          ))}
        </div>
      </section>
    </MainLayout>
  )
}
