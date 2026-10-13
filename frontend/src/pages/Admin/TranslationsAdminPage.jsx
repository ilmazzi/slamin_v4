import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { createI18nKey, createI18nLanguage, listI18nKeys, listI18nLanguages, upsertI18nTranslation } from '../../app/api/i18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './TranslationsAdminPage.module.css'

export default function TranslationsAdminPage() {
  const { t, locale: uiLocale } = useI18n()
  const { token, hasRole } = useAuth()
  const isAdmin = hasRole('admin')

  const [languages, setLanguages] = useState([])
  const [locale, setLocale] = useState(uiLocale || 'it')
  const [group, setGroup] = useState('')
  const [search, setSearch] = useState('')
  const [missingOnly, setMissingOnly] = useState(false)
  const [page, setPage] = useState(1)
  const [groups, setGroups] = useState([])
  const [keys, setKeys] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [drafts, setDrafts] = useState({})
  const [savingKey, setSavingKey] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [newLanguage, setNewLanguage] = useState({ code: '', name: '', flag: '' })
  const [newKey, setNewKey] = useState({ key: '', value: '' })

  const selectedLanguage = useMemo(
    () => languages.find((language) => language.code === locale) || null,
    [languages, locale],
  )

  const loadLanguages = useCallback(async () => {
    const nextLanguages = await listI18nLanguages(token)
    setLanguages(nextLanguages)
    setLocale((current) => {
      if (nextLanguages.some((language) => language.code === current)) {
        return current
      }
      return nextLanguages.find((language) => language.is_default)?.code || nextLanguages[0]?.code || current
    })
  }, [token])

  const loadKeys = useCallback(async () => {
    if (!locale) {
      return
    }

    setIsLoading(true)
    setError('')

    try {
      const result = await listI18nKeys(token, {
        locale,
        group,
        q: search,
        missing: missingOnly,
        page,
        perPage: 40,
      })
      setKeys(result.keys)
      setGroups(result.groups)
      setMeta(result.meta)
      setDrafts({})
    } catch (loadError) {
      setError(loadError?.message || t('i18n.panel.load_error'))
    } finally {
      setIsLoading(false)
    }
  }, [token, locale, group, search, missingOnly, page, t])

  useEffect(() => {
    if (!token) {
      return undefined
    }

    let cancelled = false

    loadLanguages().catch((loadError) => {
      if (!cancelled) {
        setError(loadError?.message || t('i18n.panel.load_error'))
        setIsLoading(false)
      }
    })

    return () => {
      cancelled = true
    }
  }, [loadLanguages, t, token])

  useEffect(() => {
    if (!token || !locale) {
      return undefined
    }

    loadKeys()
    return undefined
  }, [loadKeys, locale, token])

  const handleSave = async (key) => {
    const value = drafts[key] ?? keys.find((item) => item.key === key)?.value ?? ''
    setSavingKey(key)
    setMessage('')
    setError('')

    try {
      await upsertI18nTranslation(token, { key, locale, value })
      setKeys((current) => current.map((item) => (
        item.key === key ? { ...item, value, is_missing: false } : item
      )))
      setDrafts((current) => {
        const next = { ...current }
        delete next[key]
        return next
      })
      setMessage(t('i18n.panel.saved'))
      await loadLanguages()
    } catch (saveError) {
      setError(saveError?.message || t('i18n.panel.save_error'))
    } finally {
      setSavingKey('')
    }
  }

  const handleCreateLanguage = async (event) => {
    event.preventDefault()
    setMessage('')
    setError('')

    try {
      await createI18nLanguage(token, newLanguage)
      setNewLanguage({ code: '', name: '', flag: '' })
      setMessage(t('i18n.panel.language_created'))
      await loadLanguages()
    } catch (createError) {
      setError(createError?.message || t('i18n.panel.save_error'))
    }
  }

  const handleCreateKey = async (event) => {
    event.preventDefault()
    setMessage('')
    setError('')

    const key = newKey.key.trim()
    const value = newKey.value.trim()

    try {
      await createI18nKey(token, {
        key,
        translations: value ? { [locale]: value } : {},
      })
      setNewKey({ key: '', value: '' })
      setGroup('')
      setMissingOnly(false)
      setSearch(key)
      setPage(1)
      setMessage(t('i18n.panel.key_created'))
      await loadLanguages()
    } catch (createError) {
      setError(createError?.message || t('i18n.panel.save_error'))
    }
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <div>
            <p className={styles.back}>
              <Link to="/admin">{t('admin.hub.back')}</Link>
            </p>
            <h1 className={styles.title}>{t('i18n.panel.title')}</h1>
            <p className={styles.subtitle}>{t('i18n.panel.subtitle')}</p>
          </div>
          {selectedLanguage ? (
            <p className={styles.coverage}>
              {t('i18n.panel.coverage', {
                translated: selectedLanguage.translated_count,
                total: selectedLanguage.total_keys,
              })}
            </p>
          ) : null}
        </header>

        <div className={styles.toolbar}>
          <label className={styles.field}>
            <span>{t('i18n.panel.language')}</span>
            <select value={locale} onChange={(event) => { setPage(1); setLocale(event.target.value) }}>
              {languages.map((language) => (
                <option key={language.code} value={language.code}>
                  {language.flag} {language.name} ({language.code})
                </option>
              ))}
            </select>
          </label>

          <label className={styles.field}>
            <span>{t('i18n.panel.group')}</span>
            <select value={group} onChange={(event) => { setPage(1); setGroup(event.target.value) }}>
              <option value="">{t('i18n.panel.all_groups')}</option>
              {groups.map((item) => (
                <option key={item} value={item}>{item}</option>
              ))}
            </select>
          </label>

          <label className={styles.fieldGrow}>
            <span>{t('i18n.panel.search')}</span>
            <input
              value={search}
              onChange={(event) => { setPage(1); setSearch(event.target.value) }}
              placeholder={t('i18n.panel.search')}
            />
          </label>

          <label className={styles.checkbox}>
            <input
              type="checkbox"
              checked={missingOnly}
              onChange={(event) => { setPage(1); setMissingOnly(event.target.checked) }}
            />
            {t('i18n.panel.missing_only')}
          </label>
        </div>

        {isAdmin ? (
          <form className={styles.newLanguage} onSubmit={handleCreateLanguage}>
            <strong>{t('i18n.panel.add_language')}</strong>
            <input
              value={newLanguage.code}
              onChange={(event) => setNewLanguage((current) => ({ ...current, code: event.target.value }))}
              placeholder={t('i18n.panel.code')}
              required
            />
            <input
              value={newLanguage.name}
              onChange={(event) => setNewLanguage((current) => ({ ...current, name: event.target.value }))}
              placeholder={t('i18n.panel.name')}
              required
            />
            <input
              value={newLanguage.flag}
              onChange={(event) => setNewLanguage((current) => ({ ...current, flag: event.target.value }))}
              placeholder={t('i18n.panel.flag')}
            />
            <button type="submit">{t('i18n.panel.create_language')}</button>
          </form>
        ) : null}

        {isAdmin ? (
          <form className={styles.newLanguage} onSubmit={handleCreateKey}>
            <strong>{t('i18n.panel.add_key')}</strong>
            <input
              value={newKey.key}
              onChange={(event) => setNewKey((current) => ({ ...current, key: event.target.value }))}
              placeholder={t('i18n.panel.key_placeholder')}
              required
            />
            <input
              value={newKey.value}
              onChange={(event) => setNewKey((current) => ({ ...current, value: event.target.value }))}
              placeholder={t('i18n.panel.value')}
            />
            <button type="submit">{t('i18n.panel.create_key')}</button>
            <p className={styles.formHint}>{t('i18n.panel.key_hint')}</p>
          </form>
        ) : null}

        {message ? <p className={styles.message}>{message}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        {isLoading ? (
          <p>{t('common.loading')}</p>
        ) : keys.length === 0 ? (
          <p>{t('i18n.panel.empty')}</p>
        ) : (
          <div className={styles.tableWrap}>
            <table className={styles.table}>
              <thead>
                <tr>
                  <th>{t('i18n.panel.key')}</th>
                  <th>{t('i18n.panel.source')}</th>
                  <th>{t('i18n.panel.value')}</th>
                  <th />
                </tr>
              </thead>
              <tbody>
                {keys.map((item) => (
                  <tr key={item.key} className={item.is_missing ? styles.missing : undefined}>
                    <td><code>{item.key}</code></td>
                    <td>{item.source_value || '—'}</td>
                    <td>
                      <textarea
                        value={drafts[item.key] ?? item.value ?? ''}
                        onChange={(event) => setDrafts((current) => ({ ...current, [item.key]: event.target.value }))}
                      />
                    </td>
                    <td>
                      <button
                        type="button"
                        onClick={() => handleSave(item.key)}
                        disabled={savingKey === item.key}
                      >
                        {t('i18n.panel.save')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {meta.last_page > 1 ? (
          <div className={styles.pagination}>
            <button type="button" disabled={page <= 1} onClick={() => setPage((current) => current - 1)}>
              {t('articles.pagination.prev')}
            </button>
            <span>{page} / {meta.last_page}</span>
            <button type="button" disabled={page >= meta.last_page} onClick={() => setPage((current) => current + 1)}>
              {t('articles.pagination.next')}
            </button>
          </div>
        ) : null}
      </section>
    </MainLayout>
  )
}
