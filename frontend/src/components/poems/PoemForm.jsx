import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { createPoem, listPoemCategories, listPoemTypes } from '../../app/api/poems.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import AppToast from '../shared/AppToast.jsx'
import styles from './PoemForm.module.css'

export default function PoemForm() {
  const navigate = useNavigate()
  const { token, isAuthenticated } = useAuth()
  const { t } = useI18n()
  const [formValues, setFormValues] = useState({
    title: '',
    content: '',
    description: '',
    poem_category_id: '',
    poem_type_id: '',
    language: 'it',
    status: 'draft',
    is_public: true,
    image: null,
  })
  const [categories, setCategories] = useState([])
  const [types, setTypes] = useState([])
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
        const [{ categories: nextCategories }, { types: nextTypes }] = await Promise.all([
          listPoemCategories({ signal: controller.signal }),
          listPoemTypes({ signal: controller.signal }),
        ])

        setCategories(nextCategories)
        setTypes(nextTypes)
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
  }, [t])

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

    setIsSubmitting(true)
    setErrorMessage('')

    try {
      const createdPoem = await createPoem(
        {
          ...formValues,
          poem_category_id: formValues.poem_category_id || null,
          poem_type_id: formValues.poem_type_id || null,
        },
        { token },
      )

      setToastMessage(t('poems.form.success_creating_poem'))

      const nextId = createdPoem?.slug ?? createdPoem?.id
      navigate(nextId ? `/poems/${nextId}` : '/poems')
    } catch (error) {
      setErrorMessage(error?.message || t('poems.form.error_creating_poem'))
    } finally {
      setIsSubmitting(false)
    }
  }

  const loadingLabel = t('common.loading')

  return (
    <>
      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.header}>
          <h2 className={styles.title}>{t('poems.form.title')}</h2>
          <p className={styles.lead}>{t('poems.form.lead')}</p>
        </div>

        {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}
        {isLoadingLookups ? <p className={styles.loading}>{loadingLabel}</p> : null}

        <div className={styles.grid}>
          <label className={styles.field}>
            <span className={styles.label}>{t('poems.form.label_title')}</span>
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

          <label className={styles.field}>
            <span className={styles.label}>{t('poems.form.label_status')}</span>
            <select
              className={styles.input}
              name="status"
              value={formValues.status}
              onChange={handleFieldChange}
            >
              <option value="draft">Bozza</option>
              <option value="published">Pubblica</option>
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
            <span className={styles.label}>{t('poems.form.label_content')}</span>
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

          <label className={`${styles.field} ${styles.checkboxField}`}>
            <input
              name="is_public"
              type="checkbox"
              checked={formValues.is_public}
              onChange={handleFieldChange}
            />
            <span className={styles.checkboxLabel}>{t('poems.form.label_is_public')}</span>
          </label>
        </div>

        <div className={styles.actions}>
          <button className={styles.submitButton} type="submit" disabled={isSubmitting}>
            {isSubmitting ? t('common.loading') : t('poems.form.submit_button_label')}
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
