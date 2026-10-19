import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  adminListFeaturedArticles,
  adminSearchArticles,
  adminSyncFeaturedArticles,
} from '../../app/api/articles.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './FeaturedArticlesAdminPage.module.css'

const SEARCH_DEBOUNCE_MS = 300
const FEATURED_LIMIT = 20

export default function FeaturedArticlesAdminPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const [featured, setFeatured] = useState([])
  const [results, setResults] = useState([])
  const [search, setSearch] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [isSearching, setIsSearching] = useState(false)
  const [isSaving, setIsSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')

  const featuredIds = useMemo(
    () => new Set(featured.map((article) => article.id)),
    [featured],
  )

  const loadFeatured = useCallback(async ({ signal } = {}) => {
    const articles = await adminListFeaturedArticles({ token, signal })
    setFeatured(articles)
  }, [token])

  useEffect(() => {
    if (!token) {
      return undefined
    }

    const controller = new AbortController()
    setIsLoading(true)
    setError('')

    loadFeatured({ signal: controller.signal })
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('admin.featured_articles.load_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      })

    return () => controller.abort()
  }, [loadFeatured, t, token])

  useEffect(() => {
    if (!token) {
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(() => {
      setIsSearching(true)
      adminSearchArticles({ search: search.trim(), perPage: 20 }, { token, signal: controller.signal })
        .then(({ items }) => setResults(items))
        .catch((searchError) => {
          if (searchError?.name !== 'AbortError') {
            setError(searchError?.message || t('admin.featured_articles.load_error'))
          }
        })
        .finally(() => {
          if (!controller.signal.aborted) {
            setIsSearching(false)
          }
        })
    }, SEARCH_DEBOUNCE_MS)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [search, t, token])

  const move = (index, direction) => {
    const nextIndex = index + direction
    if (nextIndex < 0 || nextIndex >= featured.length) {
      return
    }

    setFeatured((current) => {
      const next = [...current]
      const [item] = next.splice(index, 1)
      next.splice(nextIndex, 0, item)
      return next
    })
    setMessage('')
  }

  const addArticle = (article) => {
    if (featuredIds.has(article.id)) {
      return
    }
    if (featured.length >= FEATURED_LIMIT) {
      setError(t('admin.featured_articles.limit'))
      return
    }

    setFeatured((current) => [...current, article])
    setMessage('')
    setError('')
  }

  const removeArticle = (articleId) => {
    setFeatured((current) => current.filter((article) => article.id !== articleId))
    setMessage('')
  }

  const handleSave = async () => {
    setIsSaving(true)
    setError('')
    setMessage('')

    try {
      const articles = await adminSyncFeaturedArticles(
        featured.map((article) => article.id),
        { token },
      )
      setFeatured(articles)
      setMessage(t('admin.featured_articles.saved'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.featured_articles.save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/admin">{t('admin.hub.back')}</Link>
          </p>
          <h1 className={styles.title}>{t('admin.featured_articles.title')}</h1>
          <p className={styles.subtitle}>{t('admin.featured_articles.subtitle')}</p>
        </header>

        {isLoading ? <p className={styles.status}>{t('admin.featured_articles.loading')}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}
        {message ? <p className={styles.message}>{message}</p> : null}

        <div className={styles.grid}>
          <section className={styles.card}>
            <h2>{t('admin.featured_articles.featured')}</h2>
            {featured.length === 0 ? (
              <p className={styles.status}>{t('admin.featured_articles.empty')}</p>
            ) : (
              <ol className={styles.list}>
                {featured.map((article, index) => (
                  <li key={article.id} className={styles.item}>
                    <div className={styles.itemTitle}>
                      <strong>{article.title || article.slug}</strong>
                      <span>{article.slug}</span>
                    </div>
                    <div className={styles.actions}>
                      <button type="button" className={styles.secondary} onClick={() => move(index, -1)} disabled={index === 0}>
                        {t('admin.featured_articles.move_up')}
                      </button>
                      <button
                        type="button"
                        className={styles.secondary}
                        onClick={() => move(index, 1)}
                        disabled={index === featured.length - 1}
                      >
                        {t('admin.featured_articles.move_down')}
                      </button>
                      <button type="button" className={styles.danger} onClick={() => removeArticle(article.id)}>
                        {t('admin.featured_articles.remove')}
                      </button>
                    </div>
                  </li>
                ))}
              </ol>
            )}
            <button type="button" className={styles.save} onClick={handleSave} disabled={isSaving}>
              {isSaving ? t('admin.featured_articles.saving') : t('admin.featured_articles.save')}
            </button>
          </section>

          <section className={styles.card}>
            <h2>{t('admin.featured_articles.search')}</h2>
            <label className={styles.search}>
              <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder={t('admin.featured_articles.search_placeholder')}
              />
            </label>
            {isSearching ? <p className={styles.status}>{t('admin.featured_articles.loading')}</p> : null}
            {!isSearching && results.length === 0 ? (
              <p className={styles.status}>{t('admin.featured_articles.search_empty')}</p>
            ) : (
              <ul className={styles.list}>
                {results.map((article) => {
                  const alreadyFeatured = featuredIds.has(article.id)
                  return (
                    <li key={article.id} className={styles.item}>
                      <div className={styles.itemTitle}>
                        <strong>{article.title || article.slug}</strong>
                        <span>{article.slug}</span>
                      </div>
                      <div className={styles.actions}>
                        <button
                          type="button"
                          onClick={() => addArticle(article)}
                          disabled={alreadyFeatured}
                        >
                          {alreadyFeatured
                            ? t('admin.featured_articles.already_featured')
                            : t('admin.featured_articles.add')}
                        </button>
                      </div>
                    </li>
                  )
                })}
              </ul>
            )}
          </section>
        </div>
      </section>
    </MainLayout>
  )
}
