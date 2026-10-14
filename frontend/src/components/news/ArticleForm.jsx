import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { createArticle, listArticleCategories } from '../../app/api/articles.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import AppToast from '../shared/AppToast.jsx'
import styles from './ArticleForm.module.css'

export default function ArticleForm() {
  const navigate = useNavigate()
  const { token, isAuthenticated } = useAuth()
  const { t } = useI18n()
  const [formValues, setFormValues] = useState({
    title: '',
    content: '',
    excerpt: '',
    article_category_id: '',
    language: 'it',
    cover_image: null,
  })
  const [categories, setCategories] = useState([])
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
        const { categories: nextCategories } = await listArticleCategories({ signal: controller.signal })
        setCategories(nextCategories)
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
  }, [t])

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

    setIsSubmitting(true)
    setErrorMessage('')
    const requestedStatus = event.nativeEvent?.submitter?.value
    const status = requestedStatus === 'published' ? 'published' : 'draft'

    try {
      const createdArticle = await createArticle(
        {
          ...formValues,
          status,
          is_public: true,
          article_category_id: formValues.article_category_id || null,
        },
        { token },
      )

      setToastMessage(t('articles.form.success_creating_article'))

      const nextSlug = createdArticle?.slug
      navigate(nextSlug ? `/articles/${nextSlug}` : '/articles')
    } catch (error) {
      setErrorMessage(error?.message || t('articles.form.error_creating_article'))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <>
      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.header}>
          <h2 className={styles.title}>{t('articles.form.title')}</h2>
          <p className={styles.lead}>{t('articles.form.lead')}</p>
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
            disabled={isSubmitting}
          >
            <span>{isSubmitting ? t('common.loading') : t('articles.form.publish_button')}</span>
            <small>{t('articles.form.publish_button_hint')}</small>
          </button>
          <button
            className={styles.actionButton}
            type="submit"
            name="status"
            value="draft"
            disabled={isSubmitting}
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
