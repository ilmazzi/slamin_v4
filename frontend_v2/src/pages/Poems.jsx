import MainLayout from '../components/layout/MainLayout.jsx'
import PageHero from '../components/PageHero.jsx'
import styles from './EventPage.module.css'
import { useI18n } from '../app/hooks/useI18n.js'

export default function PoemsPage() {
    const { t } = useI18n()
  return (
    <MainLayout themeTone="poems">
        <div className={styles.poemsPage}>
        <PageHero
  title={t('poems.title')}
  leadPrefix={t('poems.lead_prefix')}
  leadEmphasis={t('poems.lead_emphasis')}
  ctaLabel={t('poems.create_poem')}
  ctaHref="/poems/create"
/>
        </div>
    </MainLayout>
  )
}