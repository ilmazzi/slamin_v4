import MainLayout from '../components/layout/MainLayout.jsx'
import PageHero from '../components/PageHero.jsx'
import styles from './EventPage.module.css'
import { useI18n } from '../app/hooks/useI18n.js'
import EventMap from '../components/events/EventMap.jsx'
import UpcomingEventSlider from '../components/events/UpcomingEventSlider.jsx'
import PersonalEventsCalendar from '../components/PersonalEventsCalendar.jsx'
import HomeDashboardPromoSection from '../components/home/HomeDashboardPromoSection.jsx'

export default function EventPage() {
  const { t } = useI18n()

  return (
    <MainLayout themeTone="event">
      <div className={styles.eventPage}>
        <PageHero
          title={t('events.title')}
          leadPrefix={t('events.lead_prefix')}
          leadEmphasis={t('events.lead_emphasis')}
          ctaLabel={t('events.create_event')}
          ctaHref="/events/create"
        />
        <EventMap />
        <UpcomingEventSlider />
        <PersonalEventsCalendar tone="event" showVisibilityToggle={false} />

        <HomeDashboardPromoSection compactTop={true} />
      </div>
    </MainLayout>
  )
}