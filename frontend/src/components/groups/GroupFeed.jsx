import { useEffect, useMemo, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  createGroupAnnouncement,
  deleteGroupAnnouncement,
  listGroupAnnouncements,
} from '../../app/api/groups.js'
import styles from './GroupFeed.module.css'

function authorName(author) {
  return author?.display_name || author?.nickname || ''
}

function formatDate(value, locale) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString(locale || 'it-IT', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function eventSortAt(event) {
  return event?.published_at || event?.occurrences?.[0]?.starts_at || event?.created_at || ''
}

export default function GroupFeed({
  group,
  token,
  t,
  locale,
  locked = false,
  events = [],
  poems = [],
  articles = [],
}) {
  const [posts, setPosts] = useState([])
  const [isLoading, setIsLoading] = useState(!locked)
  const [error, setError] = useState('')
  const [isComposerOpen, setIsComposerOpen] = useState(false)
  const [title, setTitle] = useState('')
  const [content, setContent] = useState('')
  const [formError, setFormError] = useState('')
  const [isSaving, setIsSaving] = useState(false)
  const [busyId, setBusyId] = useState(null)
  const titleInputRef = useRef(null)
  const canPublish = Boolean(group?.permissions?.publish_announcement)

  const closeComposer = () => {
    if (isSaving) return
    setIsComposerOpen(false)
    setFormError('')
  }

  useEffect(() => {
    if (!group?.slug || !token || locked) {
      setPosts([])
      setIsLoading(false)
      return undefined
    }

    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    listGroupAnnouncements(group.slug, { token, signal: controller.signal })
      .then(setPosts)
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('group.ui.feed_load_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) setIsLoading(false)
      })

    return () => controller.abort()
  }, [group?.slug, locked, t, token])

  const items = useMemo(() => {
    const announcements = posts.map((post) => ({
      kind: 'announcement',
      id: `announcement-${post.id}`,
      sortAt: post.published_at || post.created_at || '',
      post,
    }))
    const affiliated = (events || []).map((event) => ({
      kind: 'event',
      id: `event-${event.id}`,
      sortAt: eventSortAt(event),
      event,
    }))
    const poemItems = (poems || []).map((poem) => ({
      kind: 'poem',
      id: `poem-${poem.id}`,
      sortAt: poem.published_at || poem.created_at || '',
      poem,
    }))
    const articleItems = (articles || []).map((article) => ({
      kind: 'article',
      id: `article-${article.id}`,
      sortAt: article.published_at || article.created_at || '',
      article,
    }))

    return [...announcements, ...affiliated, ...poemItems, ...articleItems]
      .sort((left, right) => String(right.sortAt).localeCompare(String(left.sortAt)))
  }, [articles, events, poems, posts])

  useEffect(() => {
    if (!isComposerOpen) return undefined

    const onKeyDown = (event) => {
      if (event.key === 'Escape') closeComposer()
    }

    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onKeyDown)
    window.requestAnimationFrame(() => titleInputRef.current?.focus())

    return () => {
      document.body.style.overflow = previousOverflow
      window.removeEventListener('keydown', onKeyDown)
    }
  }, [isComposerOpen, isSaving])

  const handlePublish = async (event) => {
    event.preventDefault()
    if (!title.trim() || !content.trim()) {
      setFormError(t('group.ui.feed_publish_error'))
      return
    }

    setIsSaving(true)
    setFormError('')
    try {
      const post = await createGroupAnnouncement(group.slug, {
        title: title.trim(),
        content: content.trim(),
      }, { token })
      setPosts((current) => [post, ...current.filter((item) => item.id !== post.id)])
      setTitle('')
      setContent('')
      setIsComposerOpen(false)
    } catch (saveError) {
      setFormError(saveError?.message || t('group.ui.feed_publish_error'))
    } finally {
      setIsSaving(false)
    }
  }

  const handleDelete = async (post) => {
    if (!window.confirm(t('group.ui.feed_delete_confirm', { title: post.title }))) return
    setBusyId(post.id)
    setError('')
    try {
      await deleteGroupAnnouncement(group.slug, post.id, { token })
      setPosts((current) => current.filter((item) => item.id !== post.id))
    } catch (deleteError) {
      setError(deleteError?.message || t('group.ui.feed_delete_error'))
    } finally {
      setBusyId(null)
    }
  }

  return (
    <section className={styles.panel}>
      <header className={styles.head}>
        <h2 className={styles.title}>{t('group.ui.feed')}</h2>
        {canPublish && !locked ? (
          <button
            type="button"
            className={styles.headBtn}
            onClick={() => {
              setFormError('')
              setIsComposerOpen(true)
            }}
          >
            {t('group.ui.feed_compose')}
          </button>
        ) : null}
      </header>

      <div className={styles.feed}>
        {error ? <p className={styles.error} role="alert">{error}</p> : null}
        {locked ? (
          <p className={styles.muted}>{t('group.ui.feed_locked')}</p>
        ) : isLoading ? (
          <p className={styles.muted}>{t('group.ui.loading')}</p>
        ) : items.length === 0 ? (
          <p className={styles.muted}>{t('group.ui.feed_empty')}</p>
        ) : (
          <ul className={styles.list}>
            {items.map((item) => item.kind === 'event' || item.kind === 'poem' || item.kind === 'article' ? (
              <li key={item.id} className={styles.item}>
                <div className={styles.itemHead}>
                  <h3 className={styles.itemTitle}>
                    <span className={styles.pin}>
                      {item.kind === 'poem'
                        ? t('group.ui.feed_poem')
                        : item.kind === 'article'
                          ? t('group.ui.feed_article')
                          : t('group.ui.feed_event')}
                    </span>
                    <Link
                      to={
                        item.kind === 'poem'
                          ? `/poems/${item.poem.slug}`
                          : item.kind === 'article'
                            ? `/articles/${item.article.slug}`
                            : `/events/${item.event.slug}`
                      }
                      className={styles.itemLink}
                    >
                      {item.kind === 'poem'
                        ? item.poem.title
                        : item.kind === 'article'
                          ? item.article.title
                          : item.event.title}
                    </Link>
                  </h3>
                </div>
                <p className={styles.itemMeta}>
                  {formatDate(
                    item.kind === 'poem'
                      ? (item.poem.published_at || item.poem.created_at)
                      : item.kind === 'article'
                        ? (item.article.published_at || item.article.created_at)
                        : (item.event.occurrences?.[0]?.starts_at || item.event.published_at || item.event.created_at),
                    locale,
                  )}
                </p>
                {item.kind === 'poem' && item.poem.description ? (
                  <p className={styles.itemBody}>{item.poem.description}</p>
                ) : null}
                {item.kind === 'article' && item.article.excerpt ? (
                  <p className={styles.itemBody}>{item.article.excerpt}</p>
                ) : null}
                {item.kind === 'event' && (item.event.subtitle || item.event.description) ? (
                  <p className={styles.itemBody}>{item.event.subtitle || item.event.description}</p>
                ) : null}
              </li>
            ) : (
              <li key={item.id} className={styles.item}>
                <div className={styles.itemHead}>
                  <h3 className={styles.itemTitle}>
                    {item.post.is_pinned ? <span className={styles.pin}>{t('group.ui.feed_pinned')}</span> : null}
                    {item.post.title}
                  </h3>
                  {canPublish ? (
                    <button
                      type="button"
                      className={styles.delete}
                      disabled={busyId === item.post.id}
                      onClick={() => handleDelete(item.post)}
                    >
                      {t('group.ui.feed_delete')}
                    </button>
                  ) : null}
                </div>
                <p className={styles.itemMeta}>
                  {[authorName(item.post.author), formatDate(item.post.published_at || item.post.created_at, locale)]
                    .filter(Boolean)
                    .join(' · ')}
                </p>
                <p className={styles.itemBody}>{item.post.content}</p>
              </li>
            ))}
          </ul>
        )}
      </div>

      {isComposerOpen ? (
        <div className={styles.overlay} role="presentation" onClick={closeComposer}>
          <section
            className={styles.modal}
            role="dialog"
            aria-modal="true"
            aria-labelledby="group-feed-compose-title"
            onClick={(event) => event.stopPropagation()}
          >
            <h3 id="group-feed-compose-title" className={styles.modalTitle}>
              {t('group.ui.feed_compose_title')}
            </h3>
            <form className={styles.composer} onSubmit={handlePublish}>
              {formError ? <p className={styles.error} role="alert">{formError}</p> : null}
              <label className={styles.field}>
                {t('group.ui.feed_title')}
                <input
                  ref={titleInputRef}
                  className={styles.input}
                  value={title}
                  onChange={(event) => setTitle(event.target.value)}
                  maxLength={255}
                  required
                />
              </label>
              <label className={styles.field}>
                {t('group.ui.feed_content')}
                <textarea
                  className={styles.textarea}
                  value={content}
                  onChange={(event) => setContent(event.target.value)}
                  rows={5}
                  required
                />
              </label>
              <div className={styles.modalActions}>
                <button type="button" className={styles.cancel} onClick={closeComposer} disabled={isSaving}>
                  {t('common.cancel')}
                </button>
                <button type="submit" className={styles.btn} disabled={isSaving}>
                  {isSaving ? t('group.ui.feed_publishing') : t('group.ui.feed_publish')}
                </button>
              </div>
            </form>
          </section>
        </div>
      ) : null}
    </section>
  )
}
