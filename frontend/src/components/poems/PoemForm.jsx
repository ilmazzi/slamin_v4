import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import {
  createPoem,
  getPoemBySlug,
  listPoemCategories,
  listPoemTypes,
  updatePoem,
} from '../../app/api/poems.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import AppToast from '../shared/AppToast.jsx'
import styles from './PoemForm.module.css'

export default function PoemForm({ slug = null }) {
  const navigate = useNavigate()
  const { token, isAuthenticated, user } = useAuth()
  const { t } = useI18n()
  const isEditing = Boolean(slug)
  const [formValues, setFormValues] = useState({
    title: '',
    content: '',
    description: '',
    poem_category_id: '',
    poem_type_id: '',
    language: 'it',
    image: null,
  })
  const [categories, setCategories] = useState([])
  const [types, setTypes] = useState([])
  const [editingPoem, setEditingPoem] = useState(null)
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
        const [{ categories: nextCategories }, { types: nextTypes }, poem] = await Promise.all([
          listPoemCategories({ signal: controller.signal }),
          listPoemTypes({ signal: controller.signal }),
          isEditing
            ? getPoemBySlug(slug, { token, signal: controller.signal })
            : Promise.resolve(null),
        ])

        setCategories(nextCategories)
        setTypes(nextTypes)

        if (isEditing) {
          if (!poem || String(poem.author?.id) !== String(user?.id)) {
            throw new Error(t('poems.form.error_not_owner'))
          }

          setEditingPoem(poem)
          setFormValues({
            title: poem.title ?? '',
            content: poem.content ?? '',
            description: poem.description ?? '',
            poem_category_id: poem.poem_category_id ?? '',
            poem_type_id: poem.poem_type_id ?? '',
            language: poem.language ?? 'it',
            image: null,
          })
        }
      } catch (error) {
        if (error?.name !== 'AbortError') {
          setErrorMessage(error?.message || t('poems.form.error_loading_lookups'))
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
    const { name, value, type, checked, files } = event.target

    setFormValues((prev) => ({
      ...prev,
      [name]:
        type === 'checkbox'
          ? checked
          : type === 'file'
            ? files?.[0] ?? null
            : value,
    }))
  }

  async function handleSubmit(event) {
    event.preventDefault()

    if (!isAuthenticated || !token) {
      setErrorMessage(t('poems.form.error_not_authenticated'))
      return
    }

    if (isEditing && !editingPoem) {
      setErrorMessage(t('poems.form.error_updating_poem'))
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
        poem_category_id: formValues.poem_category_id || null,
        poem_type_id: formValues.poem_type_id || null,
      }
      const savedPoem = isEditing
        ? await updatePoem(editingPoem.id, payload, { token })
        : await createPoem(payload, { token })

      setToastMessage(t(isEditing ? 'poems.form.success_updating_poem' : 'poems.form.success_creating_poem'))

      const nextId = savedPoem?.slug ?? savedPoem?.id
      navigate(nextId ? `/poems/${nextId}` : '/poems')
    } catch (error) {
      setErrorMessage(
        error?.message
          || t(isEditing ? 'poems.form.error_updating_poem' : 'poems.form.error_creating_poem'),
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  const loadingLabel = t('common.loading')

  return (
    <>
      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.header}>
          <h2 className={styles.title}>{t(isEditing ? 'poems.form.edit_title' : 'poems.form.title')}</h2>
          <p className={styles.lead}>{t(isEditing ? 'poems.form.edit_lead' : 'poems.form.lead')}</p>
        </div>

        {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}
        {isLoadingLookups ? <p className={styles.loading}>{loadingLabel}</p> : null}

        <div className={styles.grid}>
          <label className={styles.field}>
            <span className={styles.label}>{t('poems.form.label_title')} *</span>
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
            <span className={styles.label}>{t('poems.form.label_language')}</span>
            <input
              className={styles.input}
              name="language"
              value={formValues.language}
              onChange={handleFieldChange}
              maxLength={10}
            />
          </label>

          <label className={styles.field}>
            <span className={styles.label}>{t('poems.form.label_poem_category_id')}</span>
            <select
              className={styles.input}
              name="poem_category_id"
              value={formValues.poem_category_id}
              onChange={handleFieldChange}
            >
              <option value="">Nessuna</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </label>

          <label className={styles.field}>
            <span className={styles.label}>{t('poems.form.label_poem_type_id')}</span>
            <select
              className={styles.input}
              name="poem_type_id"
              value={formValues.poem_type_id}
              onChange={handleFieldChange}
            >
              <option value="">Nessuno</option>
              {types.map((type) => (
                <option key={type.id} value={type.id}>
                  {type.name}
                </option>
              ))}
            </select>
          </label>

          <label className={`${styles.field} ${styles.full}`}>
            <span className={styles.label}>{t('poems.form.label_description')}</span>
            <textarea
              className={styles.textarea}
              name="description"
              value={formValues.description}
              onChange={handleFieldChange}
              rows={3}
            />
          </label>

          <label className={`${styles.field} ${styles.full}`}>
            <span className={styles.label}>{t('poems.form.label_content')} *</span>
            <textarea
              className={styles.textarea}
              name="content"
              value={formValues.content}
              onChange={handleFieldChange}
              rows={12}
              required
            />
          </label>

          <label className={`${styles.field} ${styles.full}`}>
            <span className={styles.label}>{t('poems.form.label_image')}</span>
            <input
              className={styles.fileInput}
              name="image"
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
            <span>{isSubmitting ? t('common.loading') : t('poems.form.publish_button')}</span>
            <small>{t('poems.form.publish_button_hint')}</small>
          </button>
          <button
            className={styles.actionButton}
            type="submit"
            name="status"
            value="draft"
            disabled={isSubmitting || isLoadingLookups}
          >
            <span>{isSubmitting ? t('common.loading') : t('poems.form.save_draft_button')}</span>
            <small>{t('poems.form.save_draft_button_hint')}</small>
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
