import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import FeaturedPoemsSection from '../../components/poems/FeaturedPoemsSection.jsx'
import AllPoemsSection from '../../components/poems/AllPoemsSection.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'

export default function PoemsPage() {
    const { t } = useI18n()


  return (
    <MainLayout themeTone="poems">
        <div className={pageStyles.poemsPage}>
        <PageHero
  title={t('poems.title')}
  leadPrefix={t('poems.lead_prefix')}
  leadEmphasis={t('poems.lead_emphasis')}
  ctaLabel={t('poems.create_poem')}
  ctaHref="/poems/create"
/>
<FeaturedPoemsSection />
<AllPoemsSection />
        </div>
        <HomeDashboardPromoSection compactTop={true} />
    </MainLayout>
  )
}
