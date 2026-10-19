import { Link } from 'react-router-dom'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './TermsPage.module.css'

function LegalSection({ item }) {
  return (
    <section className={styles.termSection}>
      <h3>{item.title}</h3>
      {item.paragraphs.map((paragraph) => <p key={paragraph}>{paragraph}</p>)}
      {item.list ? (
        <ul>
          {item.list.map((entry) => <li key={entry}>{entry}</li>)}
        </ul>
      ) : null}
      {item.emphasis ? <p className={styles.emphasis}>{item.emphasis}</p> : null}
      {item.warning ? <p className={styles.warning}>{item.warning}</p> : null}
      {item.privacy ? item.privacy : null}
      {item.after ? <p>{item.after}</p> : null}
    </section>
  )
}

export default function TermsPage() {
  const { t } = useI18n()

  const serviceTerms = [
    {
      title: t('terms.s1_title'),
      paragraphs: [t('terms.s1_p1')],
    },
    {
      title: t('terms.s2_title'),
      paragraphs: [t('terms.s2_p1')],
      emphasis: t('terms.s2_emphasis'),
    },
    {
      title: t('terms.s3_title'),
      paragraphs: [t('terms.s3_p1'), t('terms.s3_p2')],
    },
    {
      title: t('terms.s4_title'),
      paragraphs: [t('terms.s4_p1'), t('terms.s4_p2')],
      warning: t('terms.s4_warning'),
    },
    {
      title: t('terms.s5_title'),
      paragraphs: [t('terms.s5_p1'), t('terms.s5_p2')],
    },
    {
      title: t('terms.s6_title'),
      paragraphs: [t('terms.s6_p1'), t('terms.s6_p2')],
    },
    {
      title: t('terms.s7_title'),
      paragraphs: [t('terms.s7_p1')],
      list: [t('terms.s7_l1'), t('terms.s7_l2'), t('terms.s7_l3'), t('terms.s7_l4'), t('terms.s7_l5'), t('terms.s7_l6'), t('terms.s7_l7')],
    },
    {
      title: t('terms.s8_title'),
      paragraphs: [t('terms.s8_p1'), t('terms.s8_p2'), t('terms.s8_p3'), t('terms.s8_p4'), t('terms.s8_p5'), t('terms.s8_p6')],
    },
    {
      title: t('terms.s9_title'),
      paragraphs: [t('terms.s9_p1')],
    },
    {
      title: t('terms.s10_title'),
      paragraphs: [t('terms.s10_p1')],
      privacy: (
        <p>
          {t('terms.privacy_consult')}{' '}
          <Link to="/privacy">{t('terms.privacy_link')}</Link>.
        </p>
      ),
    },
    {
      title: t('terms.s11_title'),
      paragraphs: [t('terms.s11_p1')],
    },
    {
      title: t('terms.s12_title'),
      paragraphs: [t('terms.s12_p1')],
      list: [t('terms.s12_l1'), t('terms.s12_l2')],
      after: t('terms.s12_after'),
    },
    {
      title: t('terms.s13_title'),
      paragraphs: [t('terms.s13_p1'), t('terms.s13_p2')],
    },
  ]

  const communityGuidelines = [
    {
      title: t('terms.g1_title'),
      paragraphs: [t('terms.g1_p1'), t('terms.g1_p2'), t('terms.g1_p3')],
    },
    {
      title: t('terms.g2_title'),
      paragraphs: [t('terms.g2_p1')],
    },
    {
      title: t('terms.g3_title'),
      paragraphs: [t('terms.g3_p1'), t('terms.g3_p2')],
    },
    {
      title: t('terms.g4_title'),
      paragraphs: [t('terms.g4_p1'), t('terms.g4_p2'), t('terms.g4_p3'), t('terms.g4_p4'), t('terms.g4_p5'), t('terms.g4_p6')],
    },
  ]

  return (
    <MainLayout>
      <main className={styles.page}>
        <header className={styles.header}>
          <h1>{t('terms.title')}</h1>
          <p className={styles.version}>{t('terms.version')}</p>
          <p className={styles.lead}>{t('terms.lead')}</p>
        </header>

        <section className={styles.introSection}>
          <h2>{t('terms.about_title')}</h2>
          <p>{t('terms.about_p1')}</p>
          <p>{t('terms.about_p2')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('terms.acceptance_title')}</h2>
          <p>{t('terms.acceptance_p1')}</p>
        </section>

        <section className={styles.introSection}>
          <h2>{t('terms.notice_title')}</h2>
          <p>{t('terms.notice_p1')}</p>
        </section>

        <section className={styles.documentSection}>
          <h2>{t('terms.service_title')}</h2>
          {serviceTerms.map((item) => <LegalSection key={item.title} item={item} />)}
        </section>

        <section className={styles.documentSection}>
          <h2>{t('terms.community_title')}</h2>
          <div className={styles.communityIntro}>
            <p>{t('terms.community_intro_1')}</p>
            <p>{t('terms.community_intro_2')}</p>
            <p>{t('terms.community_intro_3')}</p>
            <p>{t('terms.community_intro_4')}</p>
          </div>
          {communityGuidelines.map((item) => <LegalSection key={item.title} item={item} />)}
        </section>
      </main>
    </MainLayout>
  )
}
