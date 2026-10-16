import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { deleteArticle, listArticles, listMyArticles, updateArticle } from '../../app/api/articles.js'
import { deleteEvent, listEvents, listMyEvents, updateEvent } from '../../app/api/events.js'
import { deleteMediaItem, listMedia, listMyMedia, updateMediaItem } from '../../app/api/media.js'
import { deletePoem, listMyPoems, listPoems, updatePoem } from '../../app/api/poems.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './DashboardPublications.module.css'

const KINDS = ['poems', 'events', 'articles', 'media']
const PAGE_SIZE = 50

function isOwnedEvent(item) {
  return Boolean(item?.is_owner)
}

function isPublishedItem(kind, item) {
  if (kind === 'media') {
    return item?.status === 'ready' && item?.visibility === 'public'
  }

  return item?.status === 'published'
}

function canPublishItem(kind, item) {
  if (kind === 'media') {
    return item?.status === 'ready' && item?.visibility !== 'public'
  }

  return item?.status === 'draft' || item?.status === 'rejected'
}

function itemHref(kind, item) {
  if (kind === 'poems' && item?.slug) {
    return `/poems/${encodeURIComponent(item.slug)}`
  }
  if (kind === 'events' && item?.slug) {
    return `/events/${encodeURIComponent(item.slug)}`
  }
  if (kind === 'articles' && item?.slug) {
    return `/articles/${encodeURIComponent(item.slug)}`
  }
  if (kind === 'media' && item?.id) {
    return `/media/${encodeURIComponent(item.id)}`
  }
  return null
}

function itemEditHref(kind, item) {
  if (!item?.slug || !['poems', 'events', 'articles'].includes(kind)) {
    return null
  }

  return `/${kind}/${encodeURIComponent(item.slug)}/edit`
}

function itemKey(kind, item, index) {
  return `${kind}-${item?.id ?? item?.slug ?? index}`
}

function statusKey(kind, item) {
  if (kind === 'media') {
    if (item?.status === 'processing') {
      return 'processing'
    }
    if (item?.visibility === 'private') {
      return 'private'
    }
    if (item?.visibility === 'unlisted') {
      return 'unlisted'
    }
    if (item?.visibility === 'public' && item?.status === 'ready') {
      return 'published'
    }
    return item?.status || 'draft'
  }

  return item?.status || 'draft'
}

async function loadPublic(kind, nickname, signal) {
  if (kind === 'poems') {
    return listPoems({ nickname, perPage: PAGE_SIZE, page: 1 }, { signal })
  }
  if (kind === 'events') {
    return listEvents({ nickname, perPage: PAGE_SIZE, page: 1 }, { signal })
  }
  if (kind === 'articles') {
    return listArticles({ nickname, perPage: PAGE_SIZE, page: 1 }, { signal })
  }
  return listMedia({ nickname, perPage: PAGE_SIZE, page: 1 }, { signal })
}

async function loadOwned(kind, token, signal) {
  if (kind === 'poems') {
    return listMyPoems({ perPage: PAGE_SIZE, page: 1 }, { token, signal })
  }
  if (kind === 'events') {
    const items = await listMyEvents({ token, signal, perPage: PAGE_SIZE })
    return { items: Array.isArray(items) ? items.filter(isOwnedEvent) : [] }
  }
  if (kind === 'articles') {
    return listMyArticles({ perPage: PAGE_SIZE, page: 1 }, { token, signal })
  }
  return listMyMedia({ perPage: PAGE_SIZE, page: 1 }, { token, signal })
}

async function publishItem(kind, item, token) {
  if (kind === 'poems') {
    return updatePoem(item.id, {
      title: item.title,
      content: item.content,
      description: item.description,
      language: item.language,
      poem_category_id: item.poem_category_id,
      poem_type_id: item.poem_type_id,
      status: 'published',
      is_public: true,
    }, { token })
  }
  if (kind === 'articles') {
    return updateArticle(item.id, {
      title: item.title,
      content: item.content,
      excerpt: item.excerpt,
      language: item.language,
      article_category_id: item.article_category_id,
      status: 'published',
      is_public: true,
    }, { token })
  }
  if (kind === 'events') {
    return updateEvent(item.id, { status: 'published' }, { token })
  }
  return updateMediaItem(item.id, { visibility: 'public' }, { token })
}

async function removeItem(kind, item, token) {
  if (kind === 'poems') {
    await deletePoem(item.id, { token })
    return
  }
  if (kind === 'articles') {
    await deleteArticle(item.id, { token })
    return
  }
  if (kind === 'events') {
    await deleteEvent(item.id, { token })
    return
  }
  await deleteMediaItem(item.id, { token })
}

export default function DashboardPublications({ nickname, canManage = false, token = null }) {
  const { t } = useI18n()
  const [kind, setKind] = useState('poems')
  const [bucket, setBucket] = useState('published')
  const [items, setItems] = useState([])
  const [loadedKey, setLoadedKey] = useState('')
  const [errorMessage, setErrorMessage] = useState('')
  const [notice, setNotice] = useState('')
  const [busyId, setBusyId] = useState('')

  const requestKey = `${nickname || ''}:${kind}:${canManage ? 'owner' : 'public'}`
  const isLoading = loadedKey !== requestKey

  useEffect(() => {
    if (!nickname) {
      return undefined
    }

    const controller = new AbortController()
    const currentKey = `${nickname}:${kind}:${canManage ? 'owner' : 'public'}`

    async function load() {
      try {
        const result = canManage && token
          ? await loadOwned(kind, token, controller.signal)
          : await loadPublic(kind, nickname, controller.signal)

        if (controller.signal.aborted) {
          return
        }

        setItems(Array.isArray(result?.items) ? result.items : [])
        setErrorMessage('')
        setLoadedKey(currentKey)
      } catch (error) {
        if (error?.name === 'AbortError' || controller.signal.aborted) {
          return
        }

        setItems([])
        setErrorMessage(error?.message || t('profile.works.load_error'))
        setLoadedKey(currentKey)
      }
    }

    load()

    return () => controller.abort()
  }, [nickname, kind, canManage, token, t])

  const visibleItems = useMemo(() => {
    if (!canManage || bucket === 'published') {
      return items.filter((item) => isPublishedItem(kind, item))
    }

    return items.filter((item) => !isPublishedItem(kind, item))
  }, [bucket, canManage, items, kind])

  async function handlePublish(item) {
    if (!canManage || !token || busyId) {
      return
    }

    setBusyId(`publish-${item.id}`)
    setNotice('')
    setErrorMessage('')

    try {
      const updated = await publishItem(kind, item, token)
      setItems((current) => current.map((entry) => (
        entry.id === item.id
          ? { ...entry, ...(updated && typeof updated === 'object' ? updated : { status: 'published', visibility: 'public', is_public: true }) }
          : entry
      )))
      setNotice(t('profile.works.published_ok'))
    } catch (error) {
      setErrorMessage(error?.message || t('profile.works.action_error'))
    } finally {
      setBusyId('')
    }
  }

  async function handleDelete(item) {
    if (!canManage || !token || busyId) {
      return
    }

    if (!window.confirm(t('profile.works.confirm_delete'))) {
      return
    }

    setBusyId(`delete-${item.id}`)
    setNotice('')
    setErrorMessage('')

    try {
      await removeItem(kind, item, token)
      setItems((current) => current.filter((entry) => entry.id !== item.id))
      setNotice(t('profile.works.deleted_ok'))
    } catch (error) {
      setErrorMessage(error?.message || t('profile.works.action_error'))
    } finally {
      setBusyId('')
    }
  }

  if (!nickname) {
    return null
  }

  return (
    <section className={styles.section}>
      <div className={styles.header}>
        {canManage ? (
          <div className={styles.buckets} role="tablist" aria-label={t('profile.works.title')}>
            <button
              type="button"
              role="tab"
              aria-selected={bucket === 'published'}
              className={bucket === 'published' ? styles.tabActive : styles.tab}
              onClick={() => setBucket('published')}
            >
              {t('profile.works.published')}
            </button>
            <button
              type="button"
              role="tab"
              aria-selected={bucket === 'drafts'}
              className={bucket === 'drafts' ? styles.tabActive : styles.tab}
              onClick={() => setBucket('drafts')}
            >
              {t('profile.works.drafts')}
            </button>
          </div>
        ) : null}
      </div>

      <div className={styles.kinds} role="tablist" aria-label={t('profile.works.title')}>
        {KINDS.map((nextKind) => (
          <button
            key={nextKind}
            type="button"
            role="tab"
            aria-selected={kind === nextKind}
            data-kind={nextKind}
            className={kind === nextKind ? styles.kindActive : styles.kind}
            onClick={() => setKind(nextKind)}
          >
            {t(`profile.works.${nextKind}`)}
          </button>
        ))}
      </div>

      {notice ? <p className={styles.notice}>{notice}</p> : null}
      {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}

      {isLoading ? <p className={styles.status}>{t('common.loading')}</p> : null}

      {!isLoading && visibleItems.length === 0 ? (
        <p className={styles.status}>
          {canManage && bucket === 'drafts'
            ? t('profile.works.empty_drafts')
            : t('profile.works.empty_published')}
        </p>
      ) : null}

      {!isLoading && visibleItems.length > 0 ? (
        <ul className={styles.list}>
          {visibleItems.map((item, index) => {
            const href = itemHref(kind, item)
            const editHref = itemEditHref(kind, item)
            const status = statusKey(kind, item)
            const rowBusy = busyId.endsWith(String(item.id))

            return (
              <li key={itemKey(kind, item, index)} className={styles.row}>
                <div className={styles.meta}>
                  <p className={styles.itemTitle}>{item.title || t('common.no_results')}</p>
                  <p className={styles.itemStatus}>{t(`profile.works.status_${status}`)}</p>
                </div>
                <div className={styles.rowActions}>
                  {href ? (
                    <Link className={styles.link} to={href}>
                      {t('profile.works.open')}
                    </Link>
                  ) : null}
                  {canManage && editHref ? (
                    <Link className={styles.link} to={editHref}>
                      {t('profile.works.edit')}
                    </Link>
                  ) : null}
                  {canManage && canPublishItem(kind, item) ? (
                    <button
                      type="button"
                      className={styles.primary}
                      disabled={Boolean(busyId)}
                      onClick={() => handlePublish(item)}
                    >
                      {rowBusy && busyId.startsWith('publish-')
                        ? t('profile.works.publishing')
                        : t('profile.works.publish')}
                    </button>
                  ) : null}
                  {canManage ? (
                    <button
                      type="button"
                      className={styles.danger}
                      disabled={Boolean(busyId)}
                      onClick={() => handleDelete(item)}
                    >
                      {rowBusy && busyId.startsWith('delete-')
                        ? t('profile.works.deleting')
                        : t('profile.works.delete')}
                    </button>
                  ) : null}
                </div>
              </li>
            )
          })}
        </ul>
      ) : null}
    </section>
  )
}
