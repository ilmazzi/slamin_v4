import { Link } from 'react-router-dom'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './TermsPage.module.css'

export default function CookiePolicyPage() {
  const { t } = useI18n()

  return (
    <MainLayout>
      <main className={styles.page}>
        <header className={styles.header}>
          <h1>{t('cookies.page_title')}</h1>
          <p className={styles.version}>{t('cookies.page_version')}</p>
          <p className={styles.lead}>{t('cookies.page_lead')}</p>
        </header>

        <section className={styles.introSection}>
          <h2>{t('cookies.technical_title')}</h2>
          <p>{t('cookies.technical_intro')}</p>
          <ul>
            <li><strong>{t('cookies.auth_name')}:</strong> {t('cookies.auth_description')}</li>
            <li><strong>{t('cookies.locale_name')}:</strong> {t('cookies.locale_description')}</li>
            <li><strong>{t('cookies.security_name')}:</strong> {t('cookies.security_description')}</li>
          </ul>
        </section>

        <section className={styles.introSection}>
          <h2>{t('cookies.no_tracking_title')}</h2>
          <p>{t('cookies.no_tracking_body')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('cookies.consent_title')}</h2>
          <p>{t('cookies.consent_body')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('cookies.management_title')}</h2>
          <p>{t('cookies.management_body')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('cookies.logs_title')}</h2>
          <p>{t('cookies.logs_body')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('cookies.changes_title')}</h2>
          <p>{t('cookies.changes_body')}</p>
          <p>
            <Link to="/privacy">{t('cookies.privacy_link')}</Link>
            {' · '}
            <Link to="/contact">{t('cookies.contact_link')}</Link>
          </p>
        </section>

        <p className={styles.updated}>{t('cookies.updated')}</p>
      </main>
    </MainLayout>
  )
}
