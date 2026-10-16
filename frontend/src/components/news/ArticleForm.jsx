import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  createArticle,
  getArticleBySlug,
  listArticleCategories,
  updateArticle,
} from '../../app/api/articles.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import AppToast from '../shared/AppToast.jsx'
import styles from './ArticleForm.module.css'

export default function ArticleForm({ slug = null }) {
  const navigate = useNavigate()
  const { token, isAuthenticated, user } = useAuth()
  const { t } = useI18n()
  const isEditing = Boolean(slug)
  const [formValues, setFormValues] = useState({
    title: '',
    content: '',
    excerpt: '',
    article_category_id: '',
    language: 'it',
    cover_image: null,
  })
  const [categories, setCategories] = useState([])
  const [editingArticle, setEditingArticle] = useState(null)
  const [isLoadingLookups, setIsLoadingLookups] = useState(true)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')
  const [toastMessage, setToastMessage] = useState('')

  useEffect(() => {
    const controller = new AbortController()

    async function loadLookups() {
      setIsLoadingLookups(true)
      setErrorMessage('')

      try {
        const [{ categories: nextCategories }, article] = await Promise.all([
          listArticleCategories({ signal: controller.signal }),
          isEditing
            ? getArticleBySlug(slug, { token, signal: controller.signal })
            : Promise.resolve(null),
        ])
        setCategories(nextCategories)

        if (isEditing) {
          if (!article || String(article.author?.id) !== String(user?.id)) {
            throw new Error(t('articles.form.error_not_owner'))
          }

          setEditingArticle(article)
          setFormValues({
            title: article.title ?? '',
            content: article.content ?? '',
            excerpt: article.excerpt ?? '',
            article_category_id: article.article_category_id ?? '',
            language: article.language ?? 'it',
            cover_image: null,
          })
        }
      } catch (error) {
        if (error?.name !== 'AbortError') {
          setErrorMessage(error?.message || t('articles.form.error_loading_lookups'))
        }
      } finally {
        setIsLoadingLookups(false)
      }
    }

    loadLookups()

    return () => {
      controller.abort()
    }
  }, [isEditing, slug, t, token, user?.id])

  function handleFieldChange(event) {
    const { name, value, type, files } = event.target

    setFormValues((prev) => ({
      ...prev,
      [name]: type === 'file' ? files?.[0] ?? null : value,
    }))
  }

  async function handleSubmit(event) {
    event.preventDefault()

    if (!isAuthenticated || !token) {
      setErrorMessage(t('articles.form.error_not_authenticated'))
      return
    }

    if (isEditing && !editingArticle) {
      setErrorMessage(t('articles.form.error_updating_article'))
      return
    }

    setIsSubmitting(true)
    setErrorMessage('')
    const requestedStatus = event.nativeEvent?.submitter?.value
    const status = requestedStatus === 'published' ? 'published' : 'draft'

    try {
      const payload = {
        ...formValues,
        status,
        is_public: true,
        article_category_id: formValues.article_category_id || null,
      }
      const savedArticle = isEditing
        ? await updateArticle(editingArticle.id, payload, { token })
        : await createArticle(payload, { token })

      setToastMessage(t(
        isEditing ? 'articles.form.success_updating_article' : 'articles.form.success_creating_article',
      ))

      const nextSlug = savedArticle?.slug
      navigate(nextSlug ? `/articles/${nextSlug}` : '/articles')
    } catch (error) {
      setErrorMessage(
        error?.message
          || t(isEditing ? 'articles.form.error_updating_article' : 'articles.form.error_creating_article'),
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <>
      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.header}>
          <h2 className={styles.title}>{t(isEditing ? 'articles.form.edit_title' : 'articles.form.title')}</h2>
          <p className={styles.lead}>{t(isEditing ? 'articles.form.edit_lead' : 'articles.form.lead')}</p>
        </div>

        {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}
        {isLoadingLookups ? <p className={styles.loading}>{t('common.loading')}</p> : null}

        <div className={styles.grid}>
          <label className={`${styles.field} ${styles.full}`}>
            <span className={styles.label}>{t('articles.form.label_title')} *</span>
            <input
              className={styles.input}
              name="title"
              value={formValues.title}
              onChange={handleFieldChange}
              required
              maxLength={255}
            />
          </label>

          <label className={styles.field}>
            <span className={styles.label}>{t('articles.form.label_language')}</span>
            <input
              className={styles.input}
              name="language"
              value={formValues.language}
              onChange={handleFieldChange}
              maxLength={10}
            />
          </label>

          <label className={styles.field}>
            <span className={styles.label}>{t('articles.form.label_category')}</span>
            <select
              className={styles.input}
              name="article_category_id"
              value={formValues.article_category_id}
              onChange={handleFieldChange}
            >
              <option value="">{t('articles.form.no_category')}</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </label>

          <label className={`${styles.field} ${styles.full}`}>
            <span className={styles.label}>{t('articles.form.label_excerpt')}</span>
            <textarea
              className={styles.textarea}
              name="excerpt"
              value={formValues.excerpt}
              onChange={handleFieldChange}
              rows={3}
            />
          </label>

          <label className={`${styles.field} ${styles.full}`}>
            <span className={styles.label}>{t('articles.form.label_content')} *</span>
            <textarea
              className={styles.textarea}
              name="content"
              value={formValues.content}
              onChange={handleFieldChange}
              rows={16}
              required
            />
          </label>

          <label className={`${styles.field} ${styles.full}`}>
            <span className={styles.label}>{t('articles.form.label_cover_image')}</span>
            <input
              className={styles.fileInput}
              name="cover_image"
              type="file"
              accept="image/*"
              onChange={handleFieldChange}
            />
          </label>
        </div>

        <div className={styles.actions}>
          <button
            className={`${styles.actionButton} ${styles.publishButton}`}
            type="submit"
            name="status"
            value="published"
            disabled={isSubmitting || isLoadingLookups}
          >
            <span>{isSubmitting ? t('common.loading') : t('articles.form.publish_button')}</span>
            <small>{t('articles.form.publish_button_hint')}</small>
          </button>
          <button
            className={styles.actionButton}
            type="submit"
            name="status"
            value="draft"
            disabled={isSubmitting || isLoadingLookups}
          >
            <span>{isSubmitting ? t('common.loading') : t('articles.form.save_draft_button')}</span>
            <small>{t('articles.form.save_draft_button_hint')}</small>
          </button>
        </div>
      </form>

      <AppToast
        message={toastMessage}
        isVisible={Boolean(toastMessage)}
        onClose={() => setToastMessage('')}
      />
    </>
  )
}
