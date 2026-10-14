import { useEffect, useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { searchCatalog } from '../../app/api/search.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import SearchBar from '../../components/home/SearchBar.jsx'
import styles from './SearchPage.module.css'

export default function SearchPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const [searchParams] = useSearchParams()
  const query = (searchParams.get('q') ?? '').trim()
  const [groups, setGroups] = useState([])
  const [loadedQuery, setLoadedQuery] = useState('')
  const [errorMessage, setErrorMessage] = useState('')
  const isLoading = query.length >= 2 && loadedQuery !== query

  useEffect(() => {
    if (query.length < 2) {
      return undefined
    }

    const controller = new AbortController()
    searchCatalog(query, { signal: controller.signal, token })
      .then((result) => {
        setGroups(result.groups)
        setErrorMessage('')
        setLoadedQuery(query)
      })
      .catch((error) => {
        if (error?.name !== 'AbortError') {
          setErrorMessage(error?.message || t('search.ui.empty'))
          setGroups([])
          setLoadedQuery(query)
        }
      })

    return () => controller.abort()
  }, [query, t, token])

  const visibleGroups = useMemo(
    () => groups.filter((group) => Array.isArray(group.items) && group.items.length > 0),
    [groups],
  )

  return (
    <MainLayout>
      <section className={styles.hero}>
        <div className={styles.heroInner}>
          <h1 className={styles.title}>{t('search.ui.title')}</h1>
          <SearchBar
            key={query}
            placeholder={t('home.hero.search_placeholder')}
            initialQuery={query}
          />
        </div>
      </section>

      <section className={styles.results}>
        {query.length < 2 ? (
          <p className={styles.status}>{t('search.ui.too_short')}</p>
        ) : null}
        {query.length >= 2 ? (
          <h2 className={styles.heading}>{t('search.ui.results_for', { query })}</h2>
        ) : null}
        {isLoading ? <p className={styles.status}>{t('search.ui.searching')}</p> : null}
        {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}
        {!isLoading && query.length >= 2 && visibleGroups.length === 0 ? (
          <p className={styles.status}>{t('search.ui.empty')}</p>
        ) : null}
        {visibleGroups.map((group) => (
          <section key={group.type} className={styles.group}>
            <header className={styles.groupHead}>
              <h3>{t(`search.groups.${group.type}`)}</h3>
              {group.href ? (
                <Link to={group.href} className={styles.seeAll}>
                  {t('search.ui.see_all')}
                </Link>
              ) : null}
            </header>
            <ul className={styles.list}>
              {group.items.map((item) => (
                <li key={`${item.type}-${item.id}`}>
                  <Link to={item.href} className={styles.item}>
                    {item.image_url ? (
                      <img src={resolvePublicUrl(item.image_url)} alt="" className={styles.thumb} />
                    ) : (
                      <span className={styles.thumb} aria-hidden="true" />
                    )}
                    <span className={styles.meta}>
                      <strong>{item.title}</strong>
                      {item.subtitle ? <small>{item.subtitle}</small> : null}
                    </span>
                  </Link>
                </li>
              ))}
            </ul>
          </section>
        ))}
      </section>
    </MainLayout>
  )
}
