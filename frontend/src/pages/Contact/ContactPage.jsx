import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './ContactPage.module.css'

export default function ContactPage() {
  const { t } = useI18n()

  return (
    <MainLayout>
      <main className={styles.page}>
        <header className={styles.header}>
          <h1>{t('contact.title')}</h1>
          <p>{t('contact.subtitle')}</p>
        </header>

        <section className={styles.card}>
          <p className={styles.intro}>{t('contact.intro')}</p>
          <a className={styles.email} href="mailto:info@slamin.it">
            <img src="/contact-mail.svg" alt="" width="60" height="60" />
            <span>
              <strong>{t('contact.email_label')}</strong>
              <span>info@slamin.it</span>
            </span>
          </a>
        </section>
      </main>
    </MainLayout>
  )
}
