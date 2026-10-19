import { Link } from 'react-router-dom'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './TermsPage.module.css'

export default function PrivacyPage() {
  const { t } = useI18n()

  const definitions = [
    ['def_gdpr_term', 'def_gdpr_body'],
    ['def_data_term', 'def_data_body'],
    ['def_services_term', 'def_services_body'],
    ['def_user_term', 'def_user_body'],
    ['def_we_term', 'def_we_body'],
    ['def_platform_term', 'def_platform_body'],
    ['def_credentials_term', 'def_credentials_body'],
    ['def_attacks_term', 'def_attacks_body'],
  ]

  const rights = [
    ['r1_term', 'r1_body'],
    ['r2_term', 'r2_body'],
    ['r3_term', 'r3_body'],
    ['r4_term', 'r4_body'],
    ['r5_term', 'r5_body'],
    ['r6_term', 'r6_body'],
  ]

  return (
    <MainLayout>
      <main className={styles.page}>
        <header className={styles.header}>
          <h1>{t('privacy.title')}</h1>
          <p className={styles.version}>{t('privacy.version')}</p>
        </header>

        <section className={styles.introSection}>
          <h2>{t('privacy.about_title')}</h2>
          <p>{t('privacy.about_p1')}</p>
          <p>{t('privacy.about_p2')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('privacy.definitions_title')}</h2>
          <dl className={styles.definitions}>
            {definitions.map(([termKey, bodyKey]) => (
              <div key={termKey}>
                <dt>{t(`privacy.${termKey}`)}:</dt>
                <dd>{t(`privacy.${bodyKey}`)}</dd>
              </div>
            ))}
          </dl>
        </section>

        <section className={styles.introSection}>
          <h2>{t('privacy.scope_title')}</h2>
          <p>{t('privacy.scope_p1')}</p>
          <p>{t('privacy.scope_p2')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('privacy.collect_title')}</h2>
          <p>{t('privacy.collect_intro')}</p>
          <ul>
            <li><strong>{t('privacy.collect_email_label')}</strong> {t('privacy.collect_email_body')}</li>
            <li><strong>{t('privacy.collect_password_label')}</strong> {t('privacy.collect_password_body')}</li>
            <li><strong>{t('privacy.collect_newsletter_label')}</strong> {t('privacy.collect_newsletter_body')}</li>
          </ul>
          <p>{t('privacy.collect_optional')}</p>
        </section>

        <section className={`${styles.termSection} ${styles.subsection}`}>
          <h3>{t('privacy.use_title')}</h3>
          <p>{t('privacy.use_p1')}</p>
          <p>{t('privacy.use_p2')}</p>
        </section>

        <section className={`${styles.termSection} ${styles.subsection}`}>
          <h3>{t('privacy.store_title')}</h3>
          <p>{t('privacy.store_intro')}</p>
          <ul>
            <li>{t('privacy.store_l1')}</li>
            <li>{t('privacy.store_l2')}</li>
            <li>{t('privacy.store_l3')}</li>
          </ul>
        </section>

        <section className={`${styles.termSection} ${styles.subsection}`}>
          <h3>{t('privacy.backup_title')}</h3>
          <p>{t('privacy.backup_p1')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('privacy.excluded_title')}</h2>
          <ul>
            <li><strong>{t('privacy.not')}</strong> {t('privacy.ex_l1')}</li>
            <li><strong>{t('privacy.not')}</strong> {t('privacy.ex_l2')}</li>
            <li><strong>{t('privacy.not')}</strong> {t('privacy.ex_l3')}</li>
            <li><strong>{t('privacy.not')}</strong> {t('privacy.ex_l4')}</li>
            <li><strong>{t('privacy.not')}</strong> {t('privacy.ex_l5')}</li>
            <li>
              <strong>{t('privacy.not')}</strong>{' '}
              {t('privacy.ex_l6_before')}{' '}
              <Link to="/terms">{t('privacy.ex_l6_link')}</Link>
              {t('privacy.ex_l6_after')}
            </li>
          </ul>
        </section>

        <section className={styles.introSection}>
          <h2>{t('privacy.where_title')}</h2>
          <p>{t('privacy.where_p1')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('privacy.rights_title')}</h2>
          <p>{t('privacy.rights_intro')}</p>
          <ul>
            {rights.map(([termKey, bodyKey]) => (
              <li key={termKey}><strong>{t(`privacy.${termKey}`)}:</strong> {t(`privacy.${bodyKey}`)}</li>
            ))}
          </ul>
          <p className={styles.footnote}>{t('privacy.rights_footnote')}</p>
          <p>
            {t('privacy.rights_contact')}{' '}
            <a href="mailto:mail@slamin.it">mail@slamin.it</a>
          </p>
          <p>{t('privacy.rights_controller')}</p>
        </section>

        <aside className={styles.vatCard}>
          <strong>{t('privacy.vat_name')}</strong>
          <span>{t('privacy.vat_details')}</span>
        </aside>

        <section className={`${styles.termSection} ${styles.subsection}`}>
          <h3>{t('privacy.access_title')}</h3>
          <p>{t('privacy.access_p1')}</p>
          <p>{t('privacy.access_p2')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('privacy.changes_title')}</h2>
          <p>{t('privacy.changes_p1')}</p>
          <p>{t('privacy.changes_p2')}</p>
        </section>

        <p className={styles.updated}>{t('privacy.updated')}</p>
      </main>
    </MainLayout>
  )
}
