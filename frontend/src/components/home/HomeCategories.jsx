import CategoryCard from './CategoryCard.jsx'
import styles from './HomeCategories.module.css'

export default function HomeCategories({ cards, ctaLabel }) {
  return (
    <section className={styles.section}>
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
    </section>
  )
}
