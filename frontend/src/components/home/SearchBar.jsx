import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { searchCatalog } from '../../app/api/search.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './SearchBar.module.css'

function flattenHits(groups) {
  return groups.flatMap((group) => (Array.isArray(group.items) ? group.items : []))
}

export default function SearchBar({ placeholder, initialQuery = '', tone = 'dark' }) {
  const navigate = useNavigate()
  const { t } = useI18n()
  const { token } = useAuth()
  const [query, setQuery] = useState(initialQuery)
  const [hits, setHits] = useState([])
  const [isSearching, setIsSearching] = useState(false)
  const [isOpen, setIsOpen] = useState(false)

  useEffect(() => {
    const trimmed = query.trim()
    if (trimmed.length < 2) {
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(() => {
      setIsSearching(true)
      searchCatalog(trimmed, { signal: controller.signal, token })
        .then((result) => {
          setHits(flattenHits(result.groups).slice(0, 8))
        })
        .catch((error) => {
          if (error?.name !== 'AbortError') {
            setHits([])
          }
        })
        .finally(() => {
          if (!controller.signal.aborted) {
            setIsSearching(false)
          }
        })
    }, 300)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [query, token])

  const canSearch = query.trim().length >= 2
  const showSuggestions = isOpen && canSearch
  const visibleHits = canSearch ? hits : []
  const className = useMemo(
    () => [styles.form, tone === 'light' ? styles.formLight : ''].filter(Boolean).join(' '),
    [tone],
  )

  function handleSubmit(event) {
    event.preventDefault()
    const trimmed = query.trim()
    if (trimmed.length < 2) {
      return
    }
    setIsOpen(false)
    navigate(`/search?q=${encodeURIComponent(trimmed)}`)
  }

  return (
    <form className={className} onSubmit={handleSubmit} role="search">
      <label className={styles.label}>
        <span className={styles.srOnly}>{placeholder}</span>
        <input
          type="search"
          className={styles.input}
          value={query}
          onChange={(event) => {
            setQuery(event.target.value)
            setIsOpen(true)
            if (event.target.value.trim().length >= 2) {
              setIsSearching(true)
            }
          }}
          onFocus={() => setIsOpen(true)}
          onBlur={() => {
            window.setTimeout(() => setIsOpen(false), 120)
          }}
          placeholder={placeholder}
          aria-label={placeholder}
          autoComplete="off"
          maxLength={100}
        />
      </label>
      <button type="submit" className={styles.button} aria-label={placeholder}>
        <span className={styles.icon} aria-hidden="true" />
      </button>
      {showSuggestions ? (
        <div className={styles.suggestions} role="listbox">
          {isSearching ? (
            <div className={styles.status}>{t('search.ui.searching')}</div>
          ) : visibleHits.length ? (
            visibleHits.map((hit) => (
              <Link
                key={`${hit.type}-${hit.id}`}
                to={hit.href}
                className={styles.suggestion}
                role="option"
                onMouseDown={(event) => event.preventDefault()}
              >
                {hit.image_url ? (
                  <img src={resolvePublicUrl(hit.image_url)} alt="" className={styles.suggestionThumb} />
                ) : (
                  <span className={styles.suggestionThumb} aria-hidden="true" />
                )}
                <span className={styles.suggestionMeta}>
                  <strong>{hit.title}</strong>
                  <small>
                    {t(`search.groups.${hit.type}`)}
                    {hit.subtitle ? ` · ${hit.subtitle}` : ''}
                  </small>
                </span>
              </Link>
            ))
          ) : (
            <div className={styles.status}>{t('search.ui.empty')}</div>
          )}
        </div>
      ) : null}
    </form>
  )
}
