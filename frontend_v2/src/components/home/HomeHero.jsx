import SearchBar from './SearchBar.jsx'
import styles from './HomeHero.module.css'

export default function HomeHero({
  welcomePrefix,
  brandWordmark,
  tagline,
  subcopy,
  searchPlaceholder,
}) {
  return (
    <section className={styles.section}>
      <div className={styles.content}>
        <div className={styles.copy}>
          <p className={styles.welcome}>{welcomePrefix}</p>
          <p className={styles.brand}>{brandWordmark}</p>
          <p className={styles.tagline}>{tagline}</p>
          <p className={styles.subcopy}>{subcopy}</p>
        </div>

        <div className={styles.searchWrap}>
          <SearchBar placeholder={searchPlaceholder} />
        </div>
      </div>
    </section>
  )
}
