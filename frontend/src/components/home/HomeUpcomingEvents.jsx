import { useI18n } from '../../app/hooks/useI18n.js'
import UpcomingEventSlider from '../events/UpcomingEventSlider.jsx'
import SectionHeading from './SectionHeading.jsx'
import styles from './HomeUpcomingEvents.module.css'

export default function HomeUpcomingEvents() {
  const { t } = useI18n()

  return (
    <section className={styles.section}>
      <div className={styles.headingWrap}>
        <SectionHeading
          accent={t('home.upcoming_events.title')}
          subtitle={t('home.upcoming_events.subtitle')}
          align="left"
        />
      </div>
      <UpcomingEventSlider showHeading={false} />
    </section>
  )
}
