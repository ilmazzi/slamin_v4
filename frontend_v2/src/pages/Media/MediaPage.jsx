import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import styles from './MediaPage.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'

export default function MediaPage() {
    const { t } = useI18n()
  return (
    <MainLayout themeTone="media" syncHeaderWithTheme={false}>
        <div className={styles.mediaPage}>
        <PageHero
  title={t('media.title')}
  leadPrefix={t('media.lead_prefix')}
  leadEmphasis={t('media.lead_emphasis')}
  ctaLabel={t('media.create_media')}
  ctaHref="/media/create"
/>
        </div>
    </MainLayout>
  )
}