import { useEffect, useMemo, useState } from 'react'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import {
  createMediaComment,
  createArticleComment,
  createPoemComment,
  createEventComment,
  deleteComment,
  listMediaComments,
  listArticleComments,
  listPoemComments,
  listEventComments,
  updateComment,
} from '../../app/api/comments.js'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import styles from './CommentsThread.module.css'

const PAGE_SIZE = 10

function authorLabel(comment, fallback) {
  return comment?.author?.display_name || comment?.author?.nickname || fallback
}

function canManage(comment, user) {
  const me = String(user?.public_id || '')
  const author = String(comment?.author?.public_id || '')
  return Boolean(me && author && me === author)
}

export default function CommentsThread({
  contentType,
  contentId,
  title,
}) {
  const { t, locale } = useI18n()
  const { isAuthenticated, token, user } = useAuth()
  const [comments, setComments] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1 })
  const [page, setPage] = useState(1)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [draft, setDraft] = useState('')
  const [replyParentId, setReplyParentId] = useState(null)
  const [replyDraft, setReplyDraft] = useState('')
  const [editingId, setEditingId] = useState(null)
  const [editDraft, setEditDraft] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  const isPoem = contentType === 'poem'
  const isMedia = contentType === 'media'
  const isEvent = contentType === 'event'
  const resolvedTitle = title ?? t(
    isPoem ? 'comment.ui.title_poem' : isMedia ? 'comment.ui.title_media' : isEvent ? 'comment.ui.title_event' : contentType === 'article' ? 'comment.ui.title_article' : 'comment.ui.title',
  )
  const unknownUserLabel = t('common.unknown_user')
  const listFn = useMemo(
    () => (isPoem ? listPoemComments : isMedia ? listMediaComments : isEvent ? listEventComments : listArticleComments),
    [isEvent, isMedia, isPoem],
  )
  const createFn = useMemo(
    () => (isPoem ? createPoemComment : isMedia ? createMediaComment : isEvent ? createEventComment : createArticleComment),
    [isEvent, isMedia, isPoem],
  )

  async function loadComments() {
    if (!contentId) {
      setComments([])
      setMeta({ current_page: 1, last_page: 1 })
      setIsLoading(false)
      return
    }
    setIsLoading(true)
    setError('')
    try {
      const result = await listFn(contentId, { page, perPage: PAGE_SIZE })
      setComments(Array.isArray(result?.data) ? result.data : [])
      setMeta(result?.meta || { current_page: 1, last_page: 1 })
    } catch (nextError) {
      setComments([])
      setMeta({ current_page: 1, last_page: 1 })
      setError(nextError?.message || t('comment.ui.load_error'))
    } finally {
      setIsLoading(false)
    }
  }

  useEffect(() => {
    loadComments()
  }, [contentId, page, listFn]) // eslint-disable-line react-hooks/exhaustive-deps

  async function handleCreate(event) {
    event.preventDefault()
    if (!isAuthenticated || !token || !contentId) return
    const content = draft.trim()
    if (!content) return
    setIsSubmitting(true)
    setError('')
    try {
      await createFn(contentId, { content }, { token })
      setDraft('')
      setPage(1)
      await loadComments()
    } catch (nextError) {
      setError(nextError?.message || t('comment.ui.post_error'))
    } finally {
      setIsSubmitting(false)
    }
  }

  async function handleCreateReply(event) {
    event.preventDefault()
    if (!isAuthenticated || !token || !contentId || !replyParentId) return
    const content = replyDraft.trim()
    if (!content) return
    setIsSubmitting(true)
    setError('')
    try {
      await createFn(contentId, { content, parent_id: replyParentId }, { token })
      setReplyDraft('')
      setReplyParentId(null)
      await loadComments()
    } catch (nextError) {
      setError(nextError?.message || t('comment.ui.reply_error'))
    } finally {
      setIsSubmitting(false)
    }
  }

  async function handleUpdate(commentId) {
    if (!isAuthenticated || !token || !commentId) return
    const content = editDraft.trim()
    if (!content) return
    setIsSubmitting(true)
    setError('')
    try {
      await updateComment(commentId, { content }, { token })
      setEditingId(null)
      setEditDraft('')
      await loadComments()
    } catch (nextError) {
      setError(nextError?.message || t('comment.ui.update_error'))
    } finally {
      setIsSubmitting(false)
    }
  }

  async function handleDelete(commentId) {
    if (!isAuthenticated || !token || !commentId) return
    setIsSubmitting(true)
    setError('')
    try {
      await deleteComment(commentId, { token })
      await loadComments()
    } catch (nextError) {
      setError(nextError?.message || t('comment.ui.delete_error'))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <section className={styles.section}>
      <h3 className={styles.title}>{resolvedTitle}</h3>

      {isAuthenticated ? (
        <form className={styles.form} onSubmit={handleCreate}>
          <textarea
            className={styles.textarea}
            value={draft}
            onChange={(event) => setDraft(event.target.value)}
            placeholder={t('comment.ui.placeholder')}
            maxLength={5000}
            disabled={isSubmitting}
          />
          <div className={styles.actions}>
            <button type="submit" className={`${styles.btn} ${styles.btnPrimary}`} disabled={isSubmitting}>
              {isSubmitting ? t('common.submitting') : t('comment.ui.publish')}
            </button>
          </div>
        </form>
      ) : (
        <p className={styles.muted}>{t('comment.ui.login_to_comment')}</p>
      )}

      {error ? <p className={styles.error}>{error}</p> : null}
      {isLoading ? <p className={styles.muted}>{t('comment.ui.loading')}</p> : null}

      {!isLoading && comments.length === 0 ? (
        <p className={styles.muted}>{t('comment.ui.empty')}</p>
      ) : null}

      <div className={styles.list}>
        {comments.map((comment) => (
          <article key={comment.id} className={styles.item}>
            <div className={styles.itemHeader}>
              <div className={styles.authorRow}>
                <span className={styles.authorDot} aria-hidden="true" />
                <span className={styles.author}>{authorLabel(comment, unknownUserLabel)}</span>
              </div>
              <span className={styles.meta}>
                {formatShortPublishedDate(comment.created_at, locale, '')}
              </span>
            </div>

            {editingId === comment.id ? (
              <div className={styles.replyForm}>
                <textarea
                  className={styles.textarea}
                  value={editDraft}
                  onChange={(event) => setEditDraft(event.target.value)}
                  maxLength={5000}
                  disabled={isSubmitting}
                />
                <div className={styles.inlineActions}>
                  <button
                    type="button"
                    className={`${styles.btn} ${styles.btnPrimary}`}
                    disabled={isSubmitting}
                    onClick={() => handleUpdate(comment.id)}
                  >
                    {t('common.save')}
                  </button>
                  <button type="button" className={styles.btn} onClick={() => setEditingId(null)} disabled={isSubmitting}>
                    {t('common.cancel')}
                  </button>
                </div>
              </div>
            ) : (
              <p className={styles.content}>{comment.content}</p>
            )}

            <div className={styles.inlineActions}>
              {isAuthenticated ? (
                <button
                  type="button"
                  className={styles.btn}
                  onClick={() => {
                    setReplyParentId(comment.id)
                    setReplyDraft('')
                  }}
                  disabled={isSubmitting}
                >
                  {t('comment.ui.reply')}
                </button>
              ) : null}
              {canManage(comment, user) ? (
                <>
                  <button
                    type="button"
                    className={styles.btn}
                    disabled={isSubmitting}
                    onClick={() => {
                      setEditingId(comment.id)
                      setEditDraft(comment.content || '')
                    }}
                  >
                    {t('comment.ui.edit')}
                  </button>
                  <button
                    type="button"
                    className={styles.btn}
                    disabled={isSubmitting}
                    onClick={() => handleDelete(comment.id)}
                  >
                    {t('comment.ui.delete')}
                  </button>
                </>
              ) : null}
            </div>

            {replyParentId === comment.id && isAuthenticated ? (
              <form className={styles.replyForm} onSubmit={handleCreateReply}>
                <textarea
                  className={styles.textarea}
                  value={replyDraft}
                  onChange={(event) => setReplyDraft(event.target.value)}
                  placeholder={t('comment.ui.reply_placeholder')}
                  maxLength={5000}
                  disabled={isSubmitting}
                />
                <div className={styles.inlineActions}>
                  <button type="submit" className={`${styles.btn} ${styles.btnPrimary}`} disabled={isSubmitting}>
                    {isSubmitting ? t('common.submitting') : t('comment.ui.send_reply')}
                  </button>
                  <button
                    type="button"
                    className={styles.btn}
                    onClick={() => {
                      setReplyParentId(null)
                      setReplyDraft('')
                    }}
                    disabled={isSubmitting}
                  >
                    {t('common.cancel')}
                  </button>
                </div>
              </form>
            ) : null}

            {Array.isArray(comment.replies) && comment.replies.length > 0 ? (
              <div className={styles.replies}>
                {comment.replies.map((reply) => (
                  <div key={reply.id}>
                    <div className={styles.itemHeader}>
                      <div className={styles.authorRow}>
                        <span className={styles.authorDot} aria-hidden="true" />
                        <span className={styles.author}>{authorLabel(reply, unknownUserLabel)}</span>
                      </div>
                      <span className={styles.meta}>
                        {formatShortPublishedDate(reply.created_at, locale, '')}
                      </span>
                    </div>
                    <p className={styles.content}>{reply.content}</p>
                  </div>
                ))}
              </div>
            ) : null}
          </article>
        ))}
      </div>

      {(meta?.last_page || 1) > 1 ? (
        <div className={styles.pager}>
          <button
            type="button"
            className={styles.btn}
            onClick={() => setPage((current) => Math.max(1, current - 1))}
            disabled={isLoading || page <= 1}
          >
            {t('comment.ui.prev')}
          </button>
          <span className={styles.meta}>
            {t('comment.ui.page', { current: meta?.current_page || page, last: meta?.last_page || 1 })}
          </span>
          <button
            type="button"
            className={styles.btn}
            onClick={() => setPage((current) => Math.min(meta?.last_page || 1, current + 1))}
            disabled={isLoading || page >= (meta?.last_page || 1)}
          >
            {t('comment.ui.next')}
          </button>
        </div>
      ) : null}
    </section>
  )
}
