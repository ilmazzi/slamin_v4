import MainLayout from '../components/layout/MainLayout.jsx'
import PageHero from '../components/PageHero.jsx'
import styles from './EventPage.module.css'
import { useI18n } from '../app/hooks/useI18n.js'

    export default function GigPage() {
    const { t } = useI18n()
  return (
    <MainLayout themeTone="gigs">
        <div className={styles.gigsPage}>
        <PageHero
  title={t('gigs.title')}
  leadPrefix={t('gigs.lead_prefix')}
  leadEmphasis={t('gigs.lead_emphasis')}
  ctaLabel={t('gigs.create_gig')}
  ctaHref="/gigs/create"
/>
        </div>
    </MainLayout>
  )
}