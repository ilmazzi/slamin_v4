import MainLayout from '../../components/layout/MainLayout.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './AboutPage.module.css'

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

      <HomeDashboardPromoSection />
    </MainLayout>
  )
}
