import { useLayoutEffect, useRef } from 'react'
import DiscoverButton from './DiscoverButton.jsx'
import styles from './CategoryCard.module.css'

export default function CategoryCard({
  title,
  href,
  accent,
  tone,
  ctaLabel,
  imageSrc,
  imageSrcSet,
  imageSizes = '(max-width: 768px) 100vw, 50vw',
}) {
  const titleRef = useRef(null)

  useLayoutEffect(() => {
    const titleEl = titleRef.current
    const parentEl = titleEl?.parentElement

    if (!titleEl || !parentEl) {
      return undefined
    }

    const fitTitle = () => {
      titleEl.style.fontSize = ''

      const availableWidth = titleEl.clientWidth
      if (availableWidth <= 0) {
        return
      }

      const textWidth = titleEl.scrollWidth
      if (textWidth <= availableWidth + 1) {
        return
      }

      const currentSize = parseFloat(window.getComputedStyle(titleEl).fontSize)
      const minSize = window.matchMedia('(max-width: 768px)').matches ? 18 : 42
      const nextSize = Math.max(minSize, currentSize * (availableWidth / textWidth) * 0.98)
      titleEl.style.fontSize = `${nextSize}px`
    }

    fitTitle()

    const observer = new ResizeObserver(fitTitle)
    observer.observe(parentEl)

    return () => observer.disconnect()
  }, [title])

  return (
    <article
      className={[styles.card, styles[tone] || ''].join(' ')}
      style={{ '--card-accent': accent }}
    >
      {imageSrc ? (
        <img
          src={imageSrc}
          srcSet={imageSrcSet || undefined}
          sizes={imageSrcSet ? imageSizes : undefined}
          alt=""
          className={styles.backgroundImage}
          aria-hidden="true"
          loading="lazy"
          decoding="async"
        />
      ) : null}
      <div className={styles.texture} aria-hidden="true" />
      <div className={styles.overlay} aria-hidden="true" />
      <div className={styles.inner}>
        <h3 ref={titleRef} className={styles.title}>
          {title}
        </h3>
        <div className={styles.desktopButton}>
          <DiscoverButton href={href} variant="light" size="desktop">
            {ctaLabel}
          </DiscoverButton>
        </div>
        <div className={styles.mobileButton}>
          <DiscoverButton href={href} variant="light" size="mobile">
            {ctaLabel}
          </DiscoverButton>
        </div>
      </div>
    </article>
  )
}
