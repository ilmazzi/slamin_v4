import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  adminDeleteGroupCategory,
  adminListGroupCategories,
  adminStoreGroupCategory,
  adminUpdateGroupCategory,
} from '../../app/api/groups.js'
import { listI18nLanguages } from '../../app/api/i18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './EventCategoriesAdminPage.module.css'

const emptyCategory = {
  id: null,
  slug: '',
  sort_order: 0,
  is_active: true,
  translations: {},
}

function slugify(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 120)
}

function categoryFromItem(item, languages) {
  const existingTranslations = Object.fromEntries(
    (item.translations || []).map((translation) => [translation.locale, translation.name || '']),
  )

  return {
    id: item.id,
    slug: item.slug || '',
    sort_order: item.sort_order ?? 0,
    is_active: item.is_active !== false,
    translations: Object.fromEntries(
      languages.map((language) => [language.code, existingTranslations[language.code] || '']),
    ),
  }
}

function categoryPayload(form, languages) {
  const translations = languages
    .map((language) => ({
      locale: language.code,
      name: String(form.translations[language.code] || '').trim(),
    }))
    .filter((translation) => translation.name)
  const defaultLanguage = languages.find((language) => language.is_default)
  const slugSource = form.translations[defaultLanguage?.code]
    || translations[0]?.name
    || ''

  return {
    slug: form.slug.trim() || slugify(slugSource),
    is_active: Boolean(form.is_active),
    sort_order: Number(form.sort_order) || 0,
    translations,
  }
}

export default function GroupCategoriesAdminPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const [categories, setCategories] = useState([])
  const [languages, setLanguages] = useState([])
  const [form, setForm] = useState(emptyCategory)
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')

  const load = useCallback(async ({ signal } = {}) => {
    const [nextCategories, nextLanguages] = await Promise.all([
      adminListGroupCategories({ token, signal }),
      listI18nLanguages(token, { signal }),
    ])
    setCategories(nextCategories)
    setLanguages(nextLanguages)
  }, [token])

  useEffect(() => {
    if (!token) return undefined

    const controller = new AbortController()
    setIsLoading(true)
    setError('')

    load({ signal: controller.signal })
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('admin.group_categories.load_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) setIsLoading(false)
      })

    return () => controller.abort()
  }, [load, t, token])

  const resetForm = () => setForm(emptyCategory)

  const handleSave = async (event) => {
    event.preventDefault()
    const payload = categoryPayload(form, languages)
    if (!payload.slug || payload.translations.length === 0) {
      setError(t('admin.group_categories.save_error'))
      return
    }

    setIsSaving(true)
    setError('')
    setMessage('')
    try {
      if (form.id) {
        await adminUpdateGroupCategory(form.id, payload, { token })
      } else {
        await adminStoreGroupCategory(payload, { token })
      }
      await load()
      resetForm()
      setMessage(t('admin.group_categories.saved'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.group_categories.save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  const handleDelete = async (item) => {
    if (!window.confirm(t('admin.group_categories.confirm_delete'))) return

    setError('')
    setMessage('')
    try {
      await adminDeleteGroupCategory(item.id, { token })
      if (form.id === item.id) resetForm()
      await load()
      setMessage(t('admin.group_categories.deleted'))
    } catch (deleteError) {
      setError(deleteError?.message || t('admin.group_categories.delete_error'))
    }
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/admin">{t('admin.hub.back')}</Link>
          </p>
          <h1 className={styles.title}>{t('admin.group_categories.title')}</h1>
          <p className={styles.subtitle}>{t('admin.group_categories.subtitle')}</p>
        </header>

        {isLoading ? <p className={styles.status}>{t('admin.group_categories.loading')}</p> : null}
        {message ? <p className={styles.message}>{message}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        <div className={styles.grid}>
          <section className={styles.card}>
            <h2>{t('admin.group_categories.categories')}</h2>
            {categories.length === 0 ? (
              <p className={styles.status}>{t('admin.group_categories.empty')}</p>
            ) : (
              <ul className={styles.list}>
                {categories.map((item) => (
                  <li key={item.id}>
                    <button
                      type="button"
                      className={styles.row}
                      onClick={() => setForm(categoryFromItem(item, languages))}
                    >
                      <strong>{item.name || item.slug}</strong>
                      <span>{item.slug}</span>
                      <span>
                        {item.is_active
                          ? t('admin.group_categories.active')
                          : t('admin.group_categories.inactive')}
                      </span>
                    </button>
                    <button
                      type="button"
                      className={styles.delete}
                      onClick={() => handleDelete(item)}
                    >
                      {t('admin.group_categories.delete')}
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className={styles.card}>
            <form className={styles.form} onSubmit={handleSave}>
              <h2>
                {form.id
                  ? t('admin.group_categories.edit')
                  : t('admin.group_categories.create')}
              </h2>
              <label>
                <span>{t('admin.group_categories.slug')}</span>
                <input
                  value={form.slug}
                  onChange={(event) => setForm((current) => ({ ...current, slug: event.target.value }))}
                />
                <small>{t('admin.group_categories.slug_help')}</small>
              </label>
              <label>
                <span>{t('admin.group_categories.sort')}</span>
                <input
                  type="number"
                  min="0"
                  value={form.sort_order}
                  onChange={(event) => setForm((current) => ({ ...current, sort_order: event.target.value }))}
                />
              </label>
              {languages.map((language) => (
                <label key={language.code}>
                  <span>
                    {language.flag} {language.name} ({language.code})
                    {language.is_default ? ' *' : ''}
                  </span>
                  <input
                    value={form.translations[language.code] || ''}
                    required={language.is_default}
                    onChange={(event) => setForm((current) => ({
                      ...current,
                      translations: {
                        ...current.translations,
                        [language.code]: event.target.value,
                      },
                    }))}
                  />
                </label>
              ))}
              <label className={styles.check}>
                <input
                  type="checkbox"
                  checked={form.is_active}
                  onChange={(event) => setForm((current) => ({ ...current, is_active: event.target.checked }))}
                />
                {t('admin.group_categories.active')}
              </label>
              <div className={styles.actions}>
                <button type="submit" disabled={isSaving}>
                  {isSaving ? t('admin.group_categories.saving') : t('admin.group_categories.save')}
                </button>
                {form.id ? (
                  <button type="button" className={styles.secondary} onClick={resetForm}>
                    {t('admin.group_categories.cancel')}
                  </button>
                ) : null}
              </div>
            </form>
          </section>
        </div>
      </section>
    </MainLayout>
  )
}
