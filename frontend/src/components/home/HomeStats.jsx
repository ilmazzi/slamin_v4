import { useEffect, useRef, useState } from 'react'
import StatsCard from './StatsCard.jsx'
import styles from './HomeStats.module.css'

export default function HomeStats({ accent, subtitle, stats }) {
  const viewportRef = useRef(null)
  const [desktopScale, setDesktopScale] = useState(1)

  useEffect(() => {
    const element = viewportRef.current

    if (!element || typeof ResizeObserver === 'undefined') {
      return undefined
    }

    const updateScale = () => {
      const nextScale = Math.min(1, element.clientWidth / 1700)
      setDesktopScale(nextScale > 0 ? nextScale : 1)
    }

    updateScale()

    const observer = new ResizeObserver(() => {
      updateScale()
    })

    observer.observe(element)

    return () => observer.disconnect()
  }, [])

  return (
    <>
      <section className={styles.separator} aria-hidden="true">
        <span className={styles.separatorMark} />
      </section>

      <section className={styles.heading}>
        <div className={styles.headingText}>
          <h2 className={styles.accent}>{accent}</h2>
          <p className={styles.subtitle}>{subtitle}</p>
        </div>
      </section>

      <section className={styles.section}>
        <div
          ref={viewportRef}
          className={styles.gridViewport}
          style={{ '--stats-grid-scale': desktopScale }}
        >
          <div className={styles.gridScaler}>
            <div className={styles.grid}>
              {stats.map((stat) => (
                <StatsCard
                  key={stat.id}
                  id={stat.id}
                  value={stat.value}
                  label={stat.label}
                />
              ))}
            </div>
          </div>
        </div>
      </section>
    </>
  )
}
