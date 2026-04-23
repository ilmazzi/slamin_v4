import styles from './AllArticlesSection.module.css'
import ArticleTeaserCard from '../shared/ArticleTeaserCard.jsx'

export default function AllArticlesSection({ articles, locale, t }) {
  const [titlePrefix = '', ...titleRest] = t('articles.all.title').split(' ')
  const titleEmphasis = titleRest.join(' ')

  if (!articles?.length) {
    return (
      <section className={styles.section}>
        <header className={styles.header}>
          <h2 className={styles.title}>
            <span className={styles.titlePrefix}>{titlePrefix} </span>
            <span className={styles.titleEmphasis}>{titleEmphasis}</span>
          </h2>
        </header>
        <p className={styles.empty}>{t('articles.all.empty')}</p>
      </section>
    )
  }

  return (
    <section className={styles.section}>
      <header className={styles.header}>
        <h2 className={styles.title}>
          <span className={styles.titlePrefix}>{titlePrefix} </span>
          <span className={styles.titleEmphasis}>{titleEmphasis}</span>
        </h2>
      </header>

      <div className={styles.grid}>
        {articles.map((article) => (
          <ArticleTeaserCard
            key={article.id ?? article.slug}
            article={article}
            locale={locale}
            t={t}
            href="/articles"
          />
        ))}
      </div>
    </section>
  )
}
