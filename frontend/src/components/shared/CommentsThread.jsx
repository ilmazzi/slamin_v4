import { useEffect, useMemo, useState } from 'react'
import { useAuth } from '../../app/hooks/useAuth.js'
import {
  createMediaComment,
  createArticleComment,
  createPoemComment,
  deleteComment,
  listMediaComments,
  listArticleComments,
  listPoemComments,
  updateComment,
} from '../../app/api/comments.js'
import styles from './CommentsThread.module.css'

const PAGE_SIZE = 10

function authorLabel(comment) {
  return comment?.author?.display_name || comment?.author?.nickname || 'User'
}

function canManage(comment, user) {
  const me = String(user?.public_id || '')
  const author = String(comment?.author?.public_id || '')
  return Boolean(me && author && me === author)
}

export default function CommentsThread({
  contentType,
  contentId,
  title = 'Commenti',
}) {
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
  const listFn = useMemo(
    () => (isPoem ? listPoemComments : isMedia ? listMediaComments : listArticleComments),
    [isMedia, isPoem],
  )
  const createFn = useMemo(
    () => (isPoem ? createPoemComment : isMedia ? createMediaComment : createArticleComment),
    [isMedia, isPoem],
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
      setError(nextError?.message || 'Unable to load comments')
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
      setError(nextError?.message || 'Unable to post comment')
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
      setError(nextError?.message || 'Unable to post reply')
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
      setError(nextError?.message || 'Unable to update comment')
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
      setError(nextError?.message || 'Unable to delete comment')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <section className={styles.section}>
      <h3 className={styles.title}>{title}</h3>

      {isAuthenticated ? (
        <form className={styles.form} onSubmit={handleCreate}>
          <textarea
            className={styles.textarea}
            value={draft}
            onChange={(event) => setDraft(event.target.value)}
            placeholder="Scrivi un commento..."
            maxLength={5000}
            disabled={isSubmitting}
          />
          <div className={styles.actions}>
            <button type="submit" className={`${styles.btn} ${styles.btnPrimary}`} disabled={isSubmitting}>
              {isSubmitting ? 'Invio...' : 'Pubblica commento'}
            </button>
          </div>
        </form>
      ) : (
        <p className={styles.muted}>Accedi e verifica l'email per commentare.</p>
      )}

      {error ? <p className={styles.error}>{error}</p> : null}
      {isLoading ? <p className={styles.muted}>Caricamento commenti...</p> : null}

      {!isLoading && comments.length === 0 ? (
        <p className={styles.muted}>Nessun commento per ora.</p>
      ) : null}

      <div className={styles.list}>
        {comments.map((comment) => (
          <article key={comment.id} className={styles.item}>
            <div>
              <span className={styles.author}>{authorLabel(comment)}</span>
              <span className={styles.meta}>
                {comment.created_at ? new Date(comment.created_at).toLocaleString() : ''}
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
                    Salva
                  </button>
                  <button type="button" className={styles.btn} onClick={() => setEditingId(null)} disabled={isSubmitting}>
                    Annulla
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
                  Rispondi
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
                    Modifica
                  </button>
                  <button
                    type="button"
                    className={styles.btn}
                    disabled={isSubmitting}
                    onClick={() => handleDelete(comment.id)}
                  >
                    Elimina
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
                  placeholder="Scrivi una risposta..."
                  maxLength={5000}
                  disabled={isSubmitting}
                />
                <div className={styles.inlineActions}>
                  <button type="submit" className={`${styles.btn} ${styles.btnPrimary}`} disabled={isSubmitting}>
                    {isSubmitting ? 'Invio...' : 'Invia risposta'}
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
                    Annulla
                  </button>
                </div>
              </form>
            ) : null}

            {Array.isArray(comment.replies) && comment.replies.length > 0 ? (
              <div className={styles.replies}>
                {comment.replies.map((reply) => (
                  <div key={reply.id}>
                    <div>
                      <span className={styles.author}>{authorLabel(reply)}</span>
                      <span className={styles.meta}>
                        {reply.created_at ? new Date(reply.created_at).toLocaleString() : ''}
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

      <div className={styles.pager}>
        <button
          type="button"
          className={styles.btn}
          onClick={() => setPage((current) => Math.max(1, current - 1))}
          disabled={isLoading || page <= 1}
        >
          Precedente
        </button>
        <span className={styles.meta}>
          Pagina {meta?.current_page || page} / {meta?.last_page || 1}
        </span>
        <button
          type="button"
          className={styles.btn}
          onClick={() => setPage((current) => Math.min(meta?.last_page || 1, current + 1))}
          disabled={isLoading || page >= (meta?.last_page || 1)}
        >
          Successiva
        </button>
      </div>
    </section>
  )
}
