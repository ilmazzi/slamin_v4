import { useEffect, useState } from 'react'
import { searchProposeableGroups } from '../../app/api/groups.js'
import styles from './GroupProposeField.module.css'

function selectedFromContent(content) {
  const affiliations = Array.isArray(content?.group_affiliations) ? content.group_affiliations : []
  const fromAffiliations = affiliations
    .filter((item) => item?.group && ['pending', 'accepted'].includes(item.status))
    .map((item) => item.group)
  if (fromAffiliations.length > 0) {
    return fromAffiliations
  }

  return Array.isArray(content?.groups) ? content.groups.filter((group) => group?.id) : []
}

export default function GroupProposeField({
  token,
  t,
  selectedGroups = [],
  onChange,
}) {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState([])
  const [isSearching, setIsSearching] = useState(false)
  const [isOpen, setIsOpen] = useState(false)
  const selectedKey = (selectedGroups || []).map((group) => group.id).join(',')

  useEffect(() => {
    const nextQuery = query.trim()
    const selectedIds = new Set(selectedKey ? selectedKey.split(',').map((id) => Number(id)) : [])
    if (!token || nextQuery.length < 2) {
      setResults([])
      setIsSearching(false)
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(async () => {
      setIsSearching(true)
      try {
        const groups = await searchProposeableGroups(nextQuery, { token, signal: controller.signal })
        setResults(groups.filter((group) => !selectedIds.has(group.id)))
      } catch (searchError) {
        if (searchError?.name !== 'AbortError') setResults([])
      } finally {
        if (!controller.signal.aborted) setIsSearching(false)
      }
    }, 300)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [query, selectedKey, token])

  return (
    <div className={styles.field}>
      <span className={styles.label}>{t('group.ui.propose_label')}</span>
      <div className={styles.combobox}>
        <input
          className={styles.input}
          value={query}
          onChange={(event) => {
            setQuery(event.target.value)
            setIsOpen(true)
          }}
          onFocus={() => setIsOpen(true)}
          onBlur={() => window.setTimeout(() => setIsOpen(false), 120)}
          placeholder={t('group.ui.propose_search')}
          autoComplete="off"
        />
        {isOpen && query.trim().length >= 2 ? (
          <div className={styles.suggestions} role="listbox">
            {isSearching ? (
              <p className={styles.status}>{t('common.loading')}</p>
            ) : results.length === 0 ? (
              <p className={styles.status}>{t('group.ui.propose_empty')}</p>
            ) : (
              results.map((group) => (
                <button
                  key={group.id}
                  type="button"
                  className={styles.suggestion}
                  onMouseDown={(event) => event.preventDefault()}
                  onClick={() => {
                    onChange([...(selectedGroups || []), group])
                    setQuery('')
                    setResults([])
                    setIsOpen(false)
                  }}
                >
                  <strong>{group.name}</strong>
                  {group.category?.name ? <small>{group.category.name}</small> : null}
                </button>
              ))
            )}
          </div>
        ) : null}
      </div>
      {(selectedGroups || []).length > 0 ? (
        <div className={styles.chips}>
          {(selectedGroups || []).map((group) => (
            <button
              key={group.id}
              type="button"
              className={styles.chip}
              onClick={() => onChange((selectedGroups || []).filter((item) => item.id !== group.id))}
            >
              {group.name} ×
            </button>
          ))}
        </div>
      ) : null}
    </div>
  )
}

export { selectedFromContent }
