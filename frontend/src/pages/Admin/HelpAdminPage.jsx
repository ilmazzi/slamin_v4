import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  adminDeleteHelpArticle,
  adminDeleteHelpCategory,
  adminListHelpArticles,
  adminListHelpCategories,
  adminStoreHelpArticle,
  adminStoreHelpCategory,
  adminUpdateHelpArticle,
  adminUpdateHelpCategory,
} from '../../app/api/help.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './HelpAdminPage.module.css'

const emptyCategory = {
  id: null,
  slug: '',
  sort_order: 0,
  is_active: true,
  name_it: '',
  name_en: '',
}

const emptyArticle = {
  id: null,
  slug: '',
  help_category_id: '',
  sort_order: 0,
  is_published: true,
  title_it: '',
  body_it: '',
  title_en: '',
  body_en: '',
}

function slugify(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, 160)
}

function translationValue(translations, locale, key) {
  const match = (translations || []).find((item) => item.locale === locale)
  return match?.[key] || ''
}

function categoryFromItem(item) {
  return {
    id: item.id,
    slug: item.slug || '',
    sort_order: item.sort_order ?? 0,
    is_active: item.is_active !== false,
    name_it: translationValue(item.translations, 'it', 'name') || item.name || '',
    name_en: translationValue(item.translations, 'en', 'name') || '',
  }
}

function articleFromItem(item) {
  return {
    id: item.id,
    slug: item.slug || '',
    help_category_id: item.help_category_id || item.category?.id || '',
    sort_order: item.sort_order ?? 0,
    is_published: item.is_published !== false,
    title_it: translationValue(item.translations, 'it', 'title') || item.title || '',
    body_it: translationValue(item.translations, 'it', 'body') || '',
    title_en: translationValue(item.translations, 'en', 'title') || '',
    body_en: translationValue(item.translations, 'en', 'body') || '',
  }
}

function categoryPayload(form) {
  const translations = [
    form.name_it.trim() ? { locale: 'it', name: form.name_it.trim() } : null,
    form.name_en.trim() ? { locale: 'en', name: form.name_en.trim() } : null,
  ].filter(Boolean)

  return {
    slug: (form.slug.trim() || slugify(form.name_it || form.name_en)),
    is_active: Boolean(form.is_active),
    sort_order: Number(form.sort_order) || 0,
    translations,
  }
}

function articlePayload(form) {
  const translations = [
    form.title_it.trim() && form.body_it.trim()
      ? { locale: 'it', title: form.title_it.trim(), body: form.body_it.trim() }
      : null,
    form.title_en.trim() && form.body_en.trim()
      ? { locale: 'en', title: form.title_en.trim(), body: form.body_en.trim() }
      : null,
  ].filter(Boolean)

  return {
    help_category_id: Number(form.help_category_id),
    slug: (form.slug.trim() || slugify(form.title_it || form.title_en)),
    is_published: Boolean(form.is_published),
    sort_order: Number(form.sort_order) || 0,
    translations,
  }
}

export default function HelpAdminPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const [categories, setCategories] = useState([])
  const [articles, setArticles] = useState([])
  const [categoryForm, setCategoryForm] = useState(emptyCategory)
  const [articleForm, setArticleForm] = useState(emptyArticle)
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [isSavingCategory, setIsSavingCategory] = useState(false)
  const [isSavingArticle, setIsSavingArticle] = useState(false)

  const load = useCallback(async ({ signal } = {}) => {
    const [nextCategories, nextArticles] = await Promise.all([
      adminListHelpCategories({ token, signal }),
      adminListHelpArticles({ token, signal }),
    ])
    setCategories(nextCategories)
    setArticles(nextArticles)
  }, [token])

  useEffect(() => {
    if (!token) {
      return undefined
    }

    const controller = new AbortController()

    async function start() {
      setIsLoading(true)
      setError('')
      try {
        await load({ signal: controller.signal })
      } catch (loadError) {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('admin.help.load_error'))
        }
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      }
    }

    start()
    return () => controller.abort()
  }, [token, load, t])

  const flash = (text) => {
    setMessage(text)
    setError('')
  }

  const handleSaveCategory = async (event) => {
    event.preventDefault()
    const payload = categoryPayload(categoryForm)
    if (!payload.slug || payload.translations.length === 0) {
      setError(t('admin.help.save_error'))
      return
    }

    setIsSavingCategory(true)
    setError('')
    try {
      if (categoryForm.id) {
        await adminUpdateHelpCategory(categoryForm.id, payload, { token })
      } else {
        await adminStoreHelpCategory(payload, { token })
      }
      await load()
      setCategoryForm(emptyCategory)
      flash(t('admin.help.saved'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.help.save_error'))
    } finally {
      setIsSavingCategory(false)
    }
  }

  const handleDeleteCategory = async (item) => {
    if (!window.confirm(t('admin.help.confirm_delete_category'))) {
      return
    }

    setError('')
    try {
      await adminDeleteHelpCategory(item.id, { token })
      if (categoryForm.id === item.id) {
        setCategoryForm(emptyCategory)
      }
      await load()
      flash(t('admin.help.deleted'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.help.save_error'))
    }
  }

  const handleSaveArticle = async (event) => {
    event.preventDefault()
    const payload = articlePayload(articleForm)
    if (!payload.slug || !payload.help_category_id || payload.translations.length === 0) {
      setError(t('admin.help.save_error'))
      return
    }

    setIsSavingArticle(true)
    setError('')
    try {
      if (articleForm.id) {
        await adminUpdateHelpArticle(articleForm.id, payload, { token })
      } else {
        await adminStoreHelpArticle(payload, { token })
      }
      await load()
      setArticleForm(emptyArticle)
      flash(t('admin.help.saved'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.help.save_error'))
    } finally {
      setIsSavingArticle(false)
    }
  }

  const handleDeleteArticle = async (item) => {
    if (!window.confirm(t('admin.help.confirm_delete_article'))) {
      return
    }

    setError('')
    try {
      await adminDeleteHelpArticle(item.id, { token })
      if (articleForm.id === item.id) {
        setArticleForm(emptyArticle)
      }
      await load()
      flash(t('admin.help.deleted'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.help.save_error'))
    }
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}>
            <Link to="/admin">{t('admin.help.back')}</Link>
          </p>
          <h1 className={styles.title}>{t('admin.help.title')}</h1>
          <p className={styles.subtitle}>{t('admin.help.subtitle')}</p>
        </header>

        {isLoading ? <p className={styles.status}>{t('admin.help.loading')}</p> : null}
        {message ? <p className={styles.message}>{message}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        <div className={styles.grid}>
          <section className={styles.card}>
            <h2>{t('admin.help.categories')}</h2>
            {categories.length === 0 ? <p className={styles.status}>{t('admin.help.empty_categories')}</p> : (
              <ul className={styles.list}>
                {categories.map((item) => (
                  <li key={item.id}>
                    <button type="button" className={styles.row} onClick={() => setCategoryForm(categoryFromItem(item))}>
                      <strong>{item.name || item.slug}</strong>
                      <span>{item.slug}</span>
                      <span>{t('admin.help.used_by', { count: item.articles_count || 0 })}</span>
                    </button>
                    <button type="button" className={styles.delete} onClick={() => handleDeleteCategory(item)}>
                      {t('admin.help.delete')}
                    </button>
                  </li>
                ))}
              </ul>
            )}

            <form className={styles.form} onSubmit={handleSaveCategory}>
              <h3>{categoryForm.id ? t('admin.help.edit_category') : t('admin.help.new_category')}</h3>
              <label>
                <span>{t('admin.help.slug')}</span>
                <input
                  value={categoryForm.slug}
                  onChange={(event) => setCategoryForm((current) => ({ ...current, slug: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.help.sort')}</span>
                <input
                  type="number"
                  min="0"
                  value={categoryForm.sort_order}
                  onChange={(event) => setCategoryForm((current) => ({ ...current, sort_order: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.help.name_it')}</span>
                <input
                  value={categoryForm.name_it}
                  onChange={(event) => setCategoryForm((current) => ({ ...current, name_it: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.help.name_en')}</span>
                <input
                  value={categoryForm.name_en}
                  onChange={(event) => setCategoryForm((current) => ({ ...current, name_en: event.target.value }))}
                />
              </label>
              <label className={styles.check}>
                <input
                  type="checkbox"
                  checked={categoryForm.is_active}
                  onChange={(event) => setCategoryForm((current) => ({ ...current, is_active: event.target.checked }))}
                />
                {t('admin.help.active')}
              </label>
              <div className={styles.actions}>
                <button type="submit" disabled={isSavingCategory}>
                  {isSavingCategory ? t('admin.help.saving') : t('admin.help.save')}
                </button>
                {categoryForm.id ? (
                  <button type="button" className={styles.secondary} onClick={() => setCategoryForm(emptyCategory)}>
                    {t('admin.help.cancel')}
                  </button>
                ) : null}
              </div>
            </form>
          </section>

          <section className={styles.card}>
            <h2>{t('admin.help.articles')}</h2>
            {articles.length === 0 ? <p className={styles.status}>{t('admin.help.empty_articles')}</p> : (
              <ul className={styles.list}>
                {articles.map((item) => (
                  <li key={item.id}>
                    <button type="button" className={styles.row} onClick={() => setArticleForm(articleFromItem(item))}>
                      <strong>{item.title || item.slug}</strong>
                      <span>{item.category?.slug || ''}</span>
                      <span>{item.is_published ? t('admin.help.published') : t('admin.help.draft')}</span>
                    </button>
                    <button type="button" className={styles.delete} onClick={() => handleDeleteArticle(item)}>
                      {t('admin.help.delete')}
                    </button>
                  </li>
                ))}
              </ul>
            )}

            <form className={styles.form} onSubmit={handleSaveArticle}>
              <h3>{articleForm.id ? t('admin.help.edit_article') : t('admin.help.new_article')}</h3>
              <label>
                <span>{t('admin.help.category')}</span>
                <select
                  value={articleForm.help_category_id}
                  onChange={(event) => setArticleForm((current) => ({ ...current, help_category_id: event.target.value }))}
                >
                  <option value="">—</option>
                  {categories.map((item) => (
                    <option key={item.id} value={item.id}>{item.name || item.slug}</option>
                  ))}
                </select>
              </label>
              <label>
                <span>{t('admin.help.slug')}</span>
                <input
                  value={articleForm.slug}
                  onChange={(event) => setArticleForm((current) => ({ ...current, slug: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.help.sort')}</span>
                <input
                  type="number"
                  min="0"
                  value={articleForm.sort_order}
                  onChange={(event) => setArticleForm((current) => ({ ...current, sort_order: event.target.value }))}
                />
              </label>
              <label className={styles.check}>
                <input
                  type="checkbox"
                  checked={articleForm.is_published}
                  onChange={(event) => setArticleForm((current) => ({ ...current, is_published: event.target.checked }))}
                />
                {t('admin.help.published')}
              </label>
              <label>
                <span>{t('admin.help.title_it')}</span>
                <input
                  value={articleForm.title_it}
                  onChange={(event) => setArticleForm((current) => ({ ...current, title_it: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.help.body_it')}</span>
                <textarea
                  rows={6}
                  value={articleForm.body_it}
                  onChange={(event) => setArticleForm((current) => ({ ...current, body_it: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.help.title_en')}</span>
                <input
                  value={articleForm.title_en}
                  onChange={(event) => setArticleForm((current) => ({ ...current, title_en: event.target.value }))}
                />
              </label>
              <label>
                <span>{t('admin.help.body_en')}</span>
                <textarea
                  rows={6}
                  value={articleForm.body_en}
                  onChange={(event) => setArticleForm((current) => ({ ...current, body_en: event.target.value }))}
                />
              </label>
              <div className={styles.actions}>
                <button type="submit" disabled={isSavingArticle}>
                  {isSavingArticle ? t('admin.help.saving') : t('admin.help.save')}
                </button>
                {articleForm.id ? (
                  <button type="button" className={styles.secondary} onClick={() => setArticleForm(emptyArticle)}>
                    {t('admin.help.cancel')}
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
