import { Link } from 'react-router-dom'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import VoteControl from './VoteControl.jsx'
import styles from './ForumPostCard.module.css'

export default function ForumPostCard({ post, locale, t, onVote, canVote }) {
  const categorySlug = post?.category?.slug
  const href = categorySlug && post?.slug
    ? `/forum/${encodeURIComponent(categorySlug)}/${encodeURIComponent(post.slug)}`
    : '/forum'
  const author = post?.author?.display_name || post?.author?.nickname || t('common.unknown_user')

  return (
    <article className={styles.card}>
      <VoteControl
        score={post?.score}
        myVote={post?.my_vote}
        disabled={!canVote}
        upvoteLabel={t('forum.ui.upvote')}
        downvoteLabel={t('forum.ui.downvote')}
        onVote={(value) => onVote?.(post, value)}
      />
      <div className={styles.body}>
        <p className={styles.meta}>
          {categorySlug ? (
            <Link to={`/forum/${encodeURIComponent(categorySlug)}`} className={styles.category}>
              {post?.category?.name || categorySlug}
            </Link>
          ) : null}
          <span>{author}</span>
          <span>{formatShortPublishedDate(post?.created_at, locale, '')}</span>
          {post?.is_pinned ? <span className={styles.badge}>{t('forum.ui.pinned')}</span> : null}
          {post?.is_locked ? <span className={styles.badge}>{t('forum.ui.locked')}</span> : null}
        </p>
        <h2 className={styles.title}>
          <Link to={href}>{post?.title}</Link>
        </h2>
        <p className={styles.excerpt}>{(post?.body || '').slice(0, 220)}</p>
        <p className={styles.footer}>
          {t('forum.ui.comments', { count: Number(post?.comments_count || 0) })}
        </p>
      </div>
    </article>
  )
}
