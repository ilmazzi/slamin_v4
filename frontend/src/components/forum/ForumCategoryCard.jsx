import { Link } from 'react-router-dom'
import iconPosts from '../../assets/forum/icon-posts.svg'
import iconReplies from '../../assets/forum/icon-replies.svg'
import styles from './ForumCategoryCard.module.css'

export default function ForumCategoryCard({ category, t, interactive = true }) {
  const href = category?.slug ? `/forum/${encodeURIComponent(category.slug)}` : '/forum'
  const name = category?.name || category?.slug || ''
  const postsCount = Number(category?.posts_count || 0)
  const repliesCount = Number(category?.comments_count || 0)

  const inner = (
    <>
      <div className={styles.header}>
        <h2 className={styles.title}>r/{name}</h2>
        <div className={styles.stats}>
          <span className={styles.stat}>
            <img src={iconPosts} alt="" />
            <span>
              <strong>{postsCount}</strong>
              {t('forum.ui.posts_stat')}
            </span>
          </span>
          <span className={styles.stat}>
            <img src={iconReplies} alt="" />
            <span>
              <strong>{repliesCount}</strong>
              {t('forum.ui.replies_stat')}
            </span>
          </span>
        </div>
      </div>
      {category?.description ? (
        <p className={styles.description}>{category.description}</p>
      ) : null}
    </>
  )

  if (!interactive) {
    return <article className={styles.card}>{inner}</article>
  }

  return (
    <Link to={href} className={styles.card}>
      {inner}
    </Link>
  )
}
