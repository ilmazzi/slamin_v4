import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  adminDeleteEventCategory,
  adminListEventCategories,
  adminStoreEventCategory,
  adminUpdateEventCategory,
} from '../../app/api/events.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './EventCategoriesAdminPage.module.css'

const SYSTEM_SLUGS = new Set(['festival', 'poetry-slam'])

const emptyCategory = {
  id: null,
  slug: '',
  sort_order: 0,
  is_active: true,
  name_it: '',
  name_en: '',
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

function translationValue(translations, locale) {
  return (translations || []).find((item) => item.locale === locale)?.name || ''
}

function categoryFromItem(item) {
  return {
    id: item.id,
    slug: item.slug || '',
    sort_order: item.sort_order ?? 0,
    is_active: item.is_active !== false,
    name_it: translationValue(item.translations, 'it') || item.name || '',
    name_en: translationValue(item.translations, 'en'),
  }
}

function categoryPayload(form) {
  const translations = [
    form.name_it.trim() ? { locale: 'it', name: form.name_it.trim() } : null,
    form.name_en.trim() ? { locale: 'en', name: form.name_en.trim() } : null,
  ].filter(Boolean)

  return {
    slug: form.slug.trim() || slugify(form.name_it || form.name_en),
    is_active: Boolean(form.is_active),
    sort_order: Number(form.sort_order) || 0,
    translations,
  }
}

export default function EventCategoriesAdminPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const [categories, setCategories] = useState([])
  const [form, setForm] = useState(emptyCategory)
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')

  const load = useCallback(async ({ signal } = {}) => {
    setCategories(await adminListEventCategories({ token, signal }))
  }, [token])

  useEffect(() => {
    if (!token) return undefined

    const controller = new AbortController()
    setIsLoading(true)
    setError('')

    load({ signal: controller.signal })
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('admin.event_categories.load_error'))
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
    const payload = categoryPayload(form)
    if (!payload.slug || payload.translations.length === 0) {
      setError(t('admin.event_categories.save_error'))
      return
    }

    setIsSaving(true)
    setError('')
    setMessage('')
    try {
      if (form.id) {
        await adminUpdateEventCategory(form.id, payload, { token })
      } else {
        await adminStoreEventCategory(payload, { token })
      }
      await load()
      resetForm()
      setMessage(t('admin.event_categories.saved'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.event_categories.save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  const handleDelete = async (item) => {
    if (!window.confirm(t('admin.event_categories.confirm_delete'))) return

    setError('')
    setMessage('')
    try {
      await adminDeleteEventCategory(item.id, { token })
      if (form.id === item.id) resetForm()
      await load()
      setMessage(t('admin.event_categories.deleted'))
    } catch (deleteError) {
      setError(deleteError?.message || t('admin.event_categories.delete_error'))
    }
  }

  const isSystemCategory = SYSTEM_SLUGS.has(form.slug)

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/admin">{t('admin.hub.back')}</Link>
          </p>
          <h1 className={styles.title}>{t('admin.event_categories.title')}</h1>
          <p className={styles.subtitle}>{t('admin.event_categories.subtitle')}</p>
        </header>

        {isLoading ? <p className={styles.status}>{t('admin.event_categories.loading')}</p> : null}
        {message ? <p className={styles.message}>{message}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        <div className={styles.grid}>
          <section className={styles.card}>
            <h2>{t('admin.event_categories.categories')}</h2>
            {categories.length === 0 ? (
              <p className={styles.status}>{t('admin.event_categories.empty')}</p>
            ) : (
              <ul className={styles.list}>
                {categories.map((item) => {
                  const system = SYSTEM_SLUGS.has(item.slug)
                  return (
                    <li key={item.id}>
                      <button type="button" className={styles.row} onClick={() => setForm(categoryFromItem(item))}>
                        <strong>{item.name || item.slug}</strong>
                        <span>{item.slug}</span>
                        <span>
                          {item.is_active
                            ? t('admin.event_categories.active')
                            : t('admin.event_categories.inactive')}
                          {system ? ` · ${t('admin.event_categories.system')}` : ''}
                        </span>
                      </button>
                      <button
                        type="button"
                        className={styles.delete}
                        disabled={system}
                        title={system ? t('admin.event_categories.system_help') : undefined}
                        onClick={() => handleDelete(item)}
                      >
                        {t('admin.event_categories.delete')}
                      </button>
                    </li>
                  )
                })}
              </ul>
            )}
          </section>

          <section className={styles.card}>
            <form className={styles.form} onSubmit={handleSave}>
              <h2>
                {form.id
                  ? t('admin.event_categories.edit')
                  : t('admin.event_categories.create')}
              </h2>
              <label>
                <span>{t('admin.event_categories.slug')}</span>
                <input
                  value={form.slug}
                  disabled={isSystemCategory}
                  onChange={(event) => setForm((current) => ({ ...current, slug: event.target.value }))}
                />
                <small>
                  {isSystemCategory
                    ? t('admin.event_categories.system_help')
                    : t('admin.event_categories.slug_help')}
                </small>
              </label>
              <label>
                <span>{t('admin.event_categories.sort')}</span>
                <input
                  type="number"
                  min="0"
                  value={form.sort_order}
                  onChange={(event) => setForm((current) => ({ ...current, sort_order: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.event_categories.name_it')}</span>
                <input
                  value={form.name_it}
                  onChange={(event) => setForm((current) => ({ ...current, name_it: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.event_categories.name_en')}</span>
                <input
                  value={form.name_en}
                  onChange={(event) => setForm((current) => ({ ...current, name_en: event.target.value }))}
                />
              </label>
              <label className={styles.check}>
                <input
                  type="checkbox"
                  checked={form.is_active}
                  onChange={(event) => setForm((current) => ({ ...current, is_active: event.target.checked }))}
                />
                {t('admin.event_categories.active')}
              </label>
              <div className={styles.actions}>
                <button type="submit" disabled={isSaving}>
                  {isSaving ? t('admin.event_categories.saving') : t('admin.event_categories.save')}
                </button>
                {form.id ? (
                  <button type="button" className={styles.secondary} onClick={resetForm}>
                    {t('admin.event_categories.cancel')}
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
