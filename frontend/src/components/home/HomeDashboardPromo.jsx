import { Link } from 'react-router-dom'
import styles from './HomeDashboardPromo.module.css'

export default function HomeDashboardPromo({ title, userName, avatarSrc, metrics, compactTop = false }) {
  return (
    <section className={`${styles.section} ${compactTop ? styles.compactTop : ''}`}>
      <div className={styles.left}>
        <h2 className={styles.title}>{title}</h2>

        <Link to="/dashboard" className={styles.userPill}>
          {avatarSrc ? (
            <img src={avatarSrc} alt="" className={styles.avatar} />
          ) : (
            <span className={styles.avatar} aria-hidden="true" />
          )}
          <span className={styles.userName}>{userName}</span>
          <span className={styles.arrow} aria-hidden="true">
            &gt;
          </span>
        </Link>
      </div>

      <div className={styles.metrics}>
        {metrics.map((metric) => (
          <div key={metric.id} className={styles.metricPill}>
            <span className={styles.metricValue}>{metric.value}</span>
            <span className={styles.metricLabel}>{metric.label}</span>
          </div>
        ))}
      </div>
    </section>
  )
}
