import { CalendarDays, Eye, MonitorPlay, Users } from 'lucide-react'
import styles from './StatsCard.module.css'

const icons = {
  views: Eye,
  poets: Users,
  events: CalendarDays,
  videos: MonitorPlay,
}

export default function StatsCard({ id, value, label }) {
  const Icon = icons[id] || Eye

  return (
    <article className={styles.card}>
      <div className={styles.copy}>
        <p className={styles.value}>{value}</p>
        <p className={styles.label}>{label}</p>
      </div>
      <div className={styles.icon} aria-hidden="true">
        <Icon strokeWidth={1.5} />
      </div>
    </article>
  )
}
