import { useEffect, useRef, useState } from 'react'
import StatsCard from './StatsCard.jsx'
import styles from './HomeStats.module.css'

const statsSeparator =
  'https://www.figma.com/api/mcp/asset/4e9601b5-46f6-43b5-9ce4-4d65bc0a57d2'
const statsIcons = {
  views: {
    desktop: 'https://www.figma.com/api/mcp/asset/5787dd1f-2161-4ba2-9e8f-f45e22a830f9',
    mobile: 'https://www.figma.com/api/mcp/asset/f31a12d5-b71e-44c3-a7a8-89c64ddf34ff',
  },
  poets: {
    desktop: 'https://www.figma.com/api/mcp/asset/29332790-2054-4258-ad02-a1957a5ee29c',
    mobile: 'https://www.figma.com/api/mcp/asset/3f5cc324-98e8-48d3-b9fc-9d1581fbe93a',
  },
  events: {
    desktop: 'https://www.figma.com/api/mcp/asset/172a3aa2-1b4b-4a93-88de-955261e5a548',
    mobile: 'https://www.figma.com/api/mcp/asset/4b6215ac-e4e8-4fa5-85fd-5d9b09eedb52',
  },
  videos: {
    desktop: 'https://www.figma.com/api/mcp/asset/9f40cf6f-39e3-4ea1-96e2-22fe446bb154',
    mobile: 'https://www.figma.com/api/mcp/asset/ecf6e816-1e0b-41fa-ac31-2ecc599507f2',
  },
}

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
      <section className={styles.separator}>
        <img src={statsSeparator} alt="" className={styles.separatorAsset} aria-hidden="true" />
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
                  desktopIconUrl={statsIcons[stat.id]?.desktop}
                  mobileIconUrl={statsIcons[stat.id]?.mobile}
                />
              ))}
            </div>
          </div>
        </div>
      </section>
    </>
  )
}
