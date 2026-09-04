import { useEffect, useRef, useState } from 'react'
import CategoryCard from './CategoryCard.jsx'
import styles from './HomeCategories.module.css'

export default function HomeCategories({ cards, ctaLabel }) {
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
    <section className={styles.section}>
      <div
        ref={viewportRef}
        className={styles.gridViewport}
        style={{ '--categories-grid-scale': desktopScale }}
      >
        <div className={styles.gridScaler}>
          <div className={styles.grid}>
            {cards.map((card) => (
              <CategoryCard
                key={card.key}
                title={card.title}
                href={card.href}
                accent={card.accent}
                tone={card.tone}
                ctaLabel={ctaLabel}
                imageSrc={card.imageSrc}
                imageSrcSet={card.imageSrcSet}
                imageSizes={card.imageSizes}
              />
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
