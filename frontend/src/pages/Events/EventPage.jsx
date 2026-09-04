import { lazy, Suspense } from 'react'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import shellStyles from '../shared/ContentPageShell.module.css'
import styles from './EventPage.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import EventMap from '../../components/events/EventMap.jsx'
import PersonalEventsCalendar from '../../components/PersonalEventsCalendar.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'

const UpcomingEventSlider = lazy(() => import('../../components/events/UpcomingEventSlider.jsx'))

export default function EventPage() {
  const { t } = useI18n()

  return (
    <MainLayout themeTone="event">
      <div className={[shellStyles.eventPage, styles.eventPage].join(' ')}>
        <PageHero
          title={t('events.title')}
          leadPrefix={t('events.lead_prefix')}
          leadEmphasis={t('events.lead_emphasis')}
          ctaLabel={t('events.create_event')}
          ctaHref="/events/create"
        />
        <EventMap />
        <Suspense fallback={null}>
          <UpcomingEventSlider />
        </Suspense>
        <PersonalEventsCalendar tone="event" showVisibilityToggle={false} />

        <HomeDashboardPromoSection compactTop={true} />
      </div>
    </MainLayout>
  )
}
