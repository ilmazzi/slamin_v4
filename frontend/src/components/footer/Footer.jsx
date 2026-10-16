import { Link } from 'react-router-dom'
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
              <Link to="/events">{t('footer.discover_events')}</Link>
              <Link to="/poems">{t('footer.discover_poems')}</Link>
              <Link to="/articles">{t('footer.discover_news')}</Link>
              <Link to="/gigs">{t('footer.discover_jobs')}</Link>
              <Link to="/media">{t('footer.discover_media')}</Link>
            </div>

            <div className={styles.navColumn}>
              <h2 className={styles.navTitle}>{t('footer.community_title')}</h2>
              <Link to="/faq">{t('footer.community_faq')}</Link>
              <Link to="/help">{t('footer.community_help')}</Link>
              <Link to="/support">{t('footer.community_support')}</Link>
              <Link to="/contact">{t('footer.community_contact')}</Link>
              <Link to="/tutorial">{t('footer.community_tutorial')}</Link>
            </div>

            <div className={styles.navColumn}>
              <h2 className={styles.navTitle}>{t('footer.info_title')}</h2>
              <Link to="/about">{t('footer.info_about')}</Link>
              <Link to="/terms">{t('footer.info_terms')}</Link>
              <Link to="/privacy">{t('footer.info_privacy')}</Link>
              <Link to="/cookies">{t('footer.info_cookies')}</Link>
            </div>
          </nav>

          <section className={styles.newsletter}>
            <div className={styles.newsletterCard}>
              <div className={styles.newsletterCopy}>
                <h2 className={styles.newsletterTitle}>{t('footer.newsletter_title')}</h2>
                <p className={styles.newsletterText}>{t('footer.newsletter_description')}</p>
              </div>

              <Link to="/newsletter" className={styles.newsletterButton}>
                <span>{t('footer.newsletter_cta')}</span>
                <span className={styles.newsletterArrow} aria-hidden="true">
                  &gt;
                </span>
              </Link>
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
