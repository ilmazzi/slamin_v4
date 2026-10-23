import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  deleteForumPost,
  getForumPostBySlug,
  updateForumPost,
  voteForumPost,
} from '../../app/api/forum.js'
import { createForumPostReport } from '../../app/api/reports.js'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import VoteControl from '../../components/forum/VoteControl.jsx'
import MainLayout from '../../components/layout/MainLayout.jsx'
import CommentsThread from '../../components/shared/CommentsThread.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import styles from './ForumPostDetailPage.module.css'

export default function ForumPostDetailPage() {
  const { categorySlug, postSlug } = useParams()
  const navigate = useNavigate()
  const { t, locale } = useI18n()
  const { token, isAuthenticated } = useAuth()
  const [post, setPost] = useState(null)
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [isEditing, setIsEditing] = useState(false)
  const [draftTitle, setDraftTitle] = useState('')
  const [draftBody, setDraftBody] = useState('')
  const [reportOpen, setReportOpen] = useState(false)

  useEffect(() => {
    const controller = new AbortController()
    setIsLoading(true)
    getForumPostBySlug(categorySlug, postSlug, { token, signal: controller.signal })
      .then((nextPost) => {
        setPost(nextPost)
        setDraftTitle(nextPost?.title || '')
        setDraftBody(nextPost?.body || '')
        setError('')
      })
      .catch((loadError) => {
        setPost(null)
        setError(loadError?.message || t('forum.ui.not_found'))
      })
      .finally(() => {
        if (!controller.signal.aborted) setIsLoading(false)
      })
    return () => controller.abort()
  }, [categorySlug, postSlug, t, token])

  const handleVote = async (value) => {
    if (!token || !post?.public_id) return
    try {
      const next = await voteForumPost(post.public_id, value, { token })
      setPost(next)
    } catch (voteError) {
      setError(voteError?.message || t('forum.ui.vote_error'))
    }
  }

  const handleSave = async (event) => {
    event.preventDefault()
    if (!token || !post?.public_id) return
    try {
      const next = await updateForumPost(post.public_id, {
        title: draftTitle.trim(),
        body: draftBody.trim(),
      }, { token })
      setPost(next)
      setIsEditing(false)
      if (next?.slug && next.slug !== postSlug) {
        navigate(`/forum/${encodeURIComponent(next.category?.slug || categorySlug)}/${encodeURIComponent(next.slug)}`, { replace: true })
      }
    } catch (saveError) {
      setError(saveError?.message || t('forum.ui.post_error'))
    }
  }

  const moderate = async (payload) => {
    if (!token || !post?.public_id) return
    const next = await updateForumPost(post.public_id, payload, { token })
    setPost(next)
  }

  const handleDelete = async () => {
    if (!token || !post?.public_id) return
    if (!window.confirm(t('forum.ui.confirm_delete'))) return
    await deleteForumPost(post.public_id, { token })
    navigate(`/forum/${encodeURIComponent(categorySlug)}`)
  }

  return (
    <MainLayout>
      <article className={styles.page}>
        <p className={styles.back}>
          <Link to={`/forum/${encodeURIComponent(categorySlug)}`}>{t('forum.ui.back_to_forum')}</Link>
        </p>

        {isLoading ? <p className={styles.state}>{t('forum.ui.loading')}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        {post ? (
          <>
            <div className={styles.hero}>
              <VoteControl
                score={post.score}
                myVote={post.my_vote}
                disabled={!token}
                upvoteLabel={t('forum.ui.upvote')}
                downvoteLabel={t('forum.ui.downvote')}
                onVote={handleVote}
              />
              <div>
                <p className={styles.meta}>
                  <Link to={`/forum/${encodeURIComponent(post.category?.slug || categorySlug)}`}>
                    {post.category?.name || categorySlug}
                  </Link>
                  <span>{post.author?.display_name || post.author?.nickname}</span>
                  <span>{formatShortPublishedDate(post.created_at, locale, '')}</span>
                  {post.is_pinned ? <span>{t('forum.ui.pinned')}</span> : null}
                  {post.is_locked ? <span>{t('forum.ui.locked')}</span> : null}
                  {post.status === 'hidden' ? <span>{t('forum.ui.hidden')}</span> : null}
                </p>
                {isEditing ? (
                  <form className={styles.edit} onSubmit={handleSave}>
                    <input value={draftTitle} onChange={(event) => setDraftTitle(event.target.value)} required />
                    <textarea value={draftBody} onChange={(event) => setDraftBody(event.target.value)} required />
                    <div className={styles.actions}>
                      <button type="submit">{t('forum.ui.save')}</button>
                      <button type="button" onClick={() => setIsEditing(false)}>{t('common.cancel')}</button>
                    </div>
                  </form>
                ) : (
                  <>
                    <h1>{post.title}</h1>
                    <p className={styles.body}>{post.body}</p>
                  </>
                )}
                <div className={styles.actions}>
                  {post.can_update ? (
                    <button type="button" onClick={() => setIsEditing(true)}>{t('comment.ui.edit')}</button>
                  ) : null}
                  {post.can_delete ? (
                    <button type="button" onClick={handleDelete}>{t('forum.ui.delete')}</button>
                  ) : null}
                  {post.can_moderate ? (
                    <>
                      <button type="button" onClick={() => moderate({ is_locked: !post.is_locked })}>
                        {post.is_locked ? t('forum.ui.unlock') : t('forum.ui.lock')}
                      </button>
                      <button type="button" onClick={() => moderate({ is_pinned: !post.is_pinned })}>
                        {post.is_pinned ? t('forum.ui.unpin') : t('forum.ui.pin')}
                      </button>
                      <button type="button" onClick={() => moderate({ status: post.status === 'hidden' ? 'published' : 'hidden' })}>
                        {post.status === 'hidden' ? t('forum.ui.unhide') : t('forum.ui.hide')}
                      </button>
                    </>
                  ) : null}
                  {isAuthenticated ? (
                    <button type="button" onClick={() => setReportOpen(true)}>{t('forum.ui.report')}</button>
                  ) : null}
                </div>
              </div>
            </div>

            <CommentsThread
              contentType="forum"
              contentId={post.public_id}
              title={t('comment.ui.title_forum')}
              locked={post.is_locked}
            />
          </>
        ) : null}
      </article>

      <ContentReportModal
        isOpen={Boolean(reportOpen && post)}
        item={post}
        title={t('forum.ui.report')}
        onClose={() => setReportOpen(false)}
        onConfirm={async ({ reason, details }) => {
          if (!post?.public_id) return
          await createForumPostReport(post.public_id, { reason: reason || 'spam', details: details || null }, { token })
          setReportOpen(false)
        }}
      />
    </MainLayout>
  )
}
