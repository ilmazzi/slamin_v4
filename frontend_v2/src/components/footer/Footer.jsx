import logoBlack from '../../assets/slamin-logo-black.svg'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './Footer.module.css'

export default function Footer() {
  const { t } = useI18n()

  return (
    <footer className={styles.footer}>
      <div className={styles.inner}>
        <div className={styles.main}>
          <section className={styles.brand}>
            <img src={logoBlack} alt={t('home.footer.logo_alt')} className={styles.logo} />
            <p className={styles.intro}>{t('footer.tagline')}</p>
          </section>

          <nav className={styles.nav} aria-label={t('footer.info_title')}>
            <div className={styles.navColumn}>
              <h2 className={styles.navTitle}>{t('footer.discover_title')}</h2>
              <a href="/events">{t('footer.discover_events')}</a>
              <a href="/poems">{t('footer.discover_poems')}</a>
              <a href="/articles">{t('footer.discover_news')}</a>
              <a href="/gigs">{t('footer.discover_jobs')}</a>
              <a href="/media">{t('footer.discover_media')}</a>
            </div>

            <div className={styles.navColumn}>
              <h2 className={styles.navTitle}>{t('footer.community_title')}</h2>
              <a href="/faq">{t('footer.community_faq')}</a>
              <a href="/help">{t('footer.community_help')}</a>
              <a href="/support">{t('footer.community_support')}</a>
              <a href="/contact">{t('footer.community_contact')}</a>
              <a href="/tutorial">{t('footer.community_tutorial')}</a>
            </div>

            <div className={styles.navColumn}>
              <h2 className={styles.navTitle}>{t('footer.info_title')}</h2>
              <a href="/about">{t('footer.info_about')}</a>
              <a href="/terms">{t('footer.info_terms')}</a>
              <a href="/privacy">{t('footer.info_privacy')}</a>
            </div>
          </nav>

          <section className={styles.newsletter}>
            <div className={styles.newsletterCard}>
              <div className={styles.newsletterCopy}>
                <h2 className={styles.newsletterTitle}>{t('footer.newsletter_title')}</h2>
                <p className={styles.newsletterText}>{t('footer.newsletter_description')}</p>
              </div>

              <a href="/newsletter" className={styles.newsletterButton}>
                <span>{t('footer.newsletter_cta')}</span>
                <span className={styles.newsletterArrow} aria-hidden="true">
                  &gt;
                </span>
              </a>
            </div>
          </section>
        </div>

        <div className={styles.disclaimer}>
          <p>
            {t('footer.license_text')}{' '}
            <strong>{t('footer.license_name')}</strong>
          </p>
        </div>
      </div>
    </footer>
  )
}
