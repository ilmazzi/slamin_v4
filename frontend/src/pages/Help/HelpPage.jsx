import { useEffect, useMemo, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { listHelp } from '../../app/api/help.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './HelpPage.module.css'

export default function HelpPage({ section = 'help' }) {
  const { t } = useI18n()
  const [searchParams, setSearchParams] = useSearchParams()
  const [categories, setCategories] = useState([])
  const [articles, setArticles] = useState([])
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [openIds, setOpenIds] = useState([])

  const query = searchParams.get('q') || ''
  const category = searchParams.get('category') || ''
  const [draftQuery, setDraftQuery] = useState(query)

  useEffect(() => {
    setDraftQuery(query)
  }, [query])

  useEffect(() => {
    const controller = new AbortController()

    async function load() {
      setIsLoading(true)
      setError('')
      try {
        const result = await listHelp({
          signal: controller.signal,
          q: query,
          category,
          section,
        })
        setCategories(result.categories)
        setArticles(result.articles)
        if (query) {
          setOpenIds(result.articles.map((item) => item.id))
        }
      } catch (loadError) {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('help.ui.load_error'))
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      }
    }

    load()
    return () => controller.abort()
  }, [query, category, section, t])

  const updateParams = (next) => {
    const params = new URLSearchParams(searchParams)
    Object.entries(next).forEach(([key, value]) => {
      if (value) {
        params.set(key, String(value))
      } else {
        params.delete(key)
      }
    })
    setSearchParams(params)
  }

  const toggle = (id) => {
    setOpenIds((current) => (
      current.includes(id) ? current.filter((item) => item !== id) : [...current, id]
    ))
  }

  const visibleCategories = useMemo(
    () => categories.filter((item) => item?.slug),
    [categories],
  )

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <h1 className={styles.title}>
            {t(section === 'faq' ? 'help.ui.faq_title' : 'help.ui.title')}
          </h1>
          <p className={styles.subtitle}>
            {t(section === 'faq' ? 'help.ui.faq_subtitle' : 'help.ui.subtitle')}
          </p>
        </header>

        <form
          className={styles.filters}
          onSubmit={(event) => {
            event.preventDefault()
            updateParams({ q: draftQuery.trim() })
          }}
        >
          <label className={styles.field}>
            <span className={styles.fieldLabel}>
              <svg className={styles.searchIcon} viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" strokeWidth="1.6" />
                <path d="M16 16.5 20 20.5" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
              </svg>
              {t('help.ui.search')}
            </span>
            <input
              value={draftQuery}
              onChange={(event) => setDraftQuery(event.target.value)}
              onBlur={() => updateParams({ q: draftQuery.trim() })}
              placeholder={t('help.ui.search_placeholder')}
            />
          </label>
          <label className={styles.field}>
            <span className={styles.fieldLabel}>{t('help.ui.category')}</span>
            <span className={styles.selectWrap}>
              <select
                value={category}
                onChange={(event) => updateParams({ category: event.target.value })}
              >
                <option value="">{t('help.ui.category_all')}</option>
                {visibleCategories.map((item) => (
                  <option key={item.id} value={item.slug}>{item.name}</option>
                ))}
              </select>
            </span>
          </label>
        </form>

        {isLoading ? <p className={styles.status}>{t('help.ui.loading')}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}
        {!isLoading && articles.length === 0 ? <p className={styles.status}>{t('help.ui.empty')}</p> : null}

        <div className={styles.list}>
          {articles.map((item) => {
            const isOpen = openIds.includes(item.id)
            return (
              <article key={item.id} className={`${styles.item} ${isOpen ? styles.itemOpen : ''}`}>
                <button
                  type="button"
                  className={styles.trigger}
                  aria-expanded={isOpen}
                  onClick={() => toggle(item.id)}
                >
                  <span className={styles.infoIcon} aria-hidden="true">i</span>
                  <span className={styles.triggerMain}>
                    <span className={styles.itemTitle}>{item.title}</span>
                    {item.category?.slug ? (
                      <span className={styles.pill}>{item.category.slug}</span>
                    ) : null}
                  </span>
                  <span className={`${styles.chevron} ${isOpen ? styles.chevronOpen : ''}`} aria-hidden="true" />
                </button>
                {isOpen ? (
                  <div className={styles.body}>
                    {(item.body || '').split('\n').filter(Boolean).map((paragraph, index) => (
                      <p key={`${item.id}-${index}`}>{paragraph}</p>
                    ))}
                  </div>
                ) : null}
              </article>
            )
          })}
        </div>
      </section>
    </MainLayout>
  )
}
