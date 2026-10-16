import { useMemo, useState } from 'react'
import {
  deleteContentTranslation,
  getContentTranslation,
  saveContentTranslation,
} from '../../app/api/contentTranslations.js'
import { isRichTextEmpty } from '../../app/content/richText.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { contentLanguageOptions, languageName } from '../../app/i18n/contentLanguages.js'
import RichTextEditor from './RichTextEditor.jsx'
import styles from './ContentTranslationsManager.module.css'

const EMPTY_FORM = { locale: '', title: '', content: '', summary: '' }

export default function ContentTranslationsManager({ contentType, item, withSummary = false, onSaved, onDeleted }) {
  const { t, locale: uiLocale } = useI18n()
  const { token } = useAuth()
  const [form, setForm] = useState(null)
  const [isBusy, setIsBusy] = useState(false)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')

  const translations = useMemo(
    () => (Array.isArray(item?.translations) ? item.translations : []),
    [item?.translations],
  )
  const originalLocale = String(item?.language ?? '').toLowerCase()
  const languageOptions = useMemo(
    () => contentLanguageOptions(uiLocale).filter((option) => option.value !== originalLocale),
    [originalLocale, uiLocale],
  )
  const isEditingExisting = Boolean(form?.locale) && translations.some((entry) => entry.locale === form.locale)

  function openNew() {
    setError('')
    setNotice('')
    setForm({ ...EMPTY_FORM, isNew: true })
  }

  async function openEdit(locale) {
    setError('')
    setNotice('')
    setIsBusy(true)
    try {
      const translation = await getContentTranslation(contentType, item.slug, locale, { token })
      setForm({
        locale,
        title: translation?.title ?? '',
        content: translation?.content ?? '',
        summary: translation?.summary ?? '',
        isNew: false,
      })
    } catch {
      setError(t('content_translations.load_error'))
    } finally {
      setIsBusy(false)
    }
  }

  function updateField(field, value) {
    setForm((prev) => ({ ...prev, [field]: value }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    if (!form?.locale || isBusy) {
      return
    }

    if (isRichTextEmpty(form.content)) {
      setError(t('rich_text.required'))
      return
    }

    setIsBusy(true)
    setError('')
    try {
      const translation = await saveContentTranslation(
        contentType,
        item.id,
        form.locale,
        {
          title: form.title.trim(),
          content: form.content,
          summary: withSummary && form.summary.trim() ? form.summary.trim() : null,
        },
        { token },
      )
      if (translation) {
        onSaved?.(translation)
      }
      setForm(null)
      setNotice(t('content_translations.manage.saved'))
    } catch (err) {
      setError(err?.message || t('content_translations.manage.error'))
    } finally {
      setIsBusy(false)
    }
  }

  async function handleDelete(locale) {
    if (isBusy || !window.confirm(t('content_translations.manage.delete_confirm', { language: languageName(locale, uiLocale) }))) {
      return
    }

    setIsBusy(true)
    setError('')
    try {
      await deleteContentTranslation(contentType, item.id, locale, { token })
      onDeleted?.(locale)
      if (form?.locale === locale) {
        setForm(null)
      }
      setNotice(t('content_translations.manage.deleted'))
    } catch (err) {
      setError(err?.message || t('content_translations.manage.error'))
    } finally {
      setIsBusy(false)
    }
  }

  return (
    <section className={styles.manager} aria-labelledby={`translations-${contentType}-${item.id}`}>
      <div className={styles.header}>
        <div>
          <h2 id={`translations-${contentType}-${item.id}`} className={styles.title}>
            {t('content_translations.manage.title')}
          </h2>
          <p className={styles.lead}>{t('content_translations.manage.lead')}</p>
        </div>
        {!form ? (
          <button type="button" className={styles.primaryButton} onClick={openNew} disabled={isBusy}>
            {t('content_translations.manage.add')}
          </button>
        ) : null}
      </div>

      {translations.length > 0 ? (
        <ul className={styles.list}>
          {translations.map((entry) => (
            <li key={entry.locale} className={styles.row}>
              <span className={styles.rowLanguage}>{languageName(entry.locale, uiLocale)}</span>
              <span className={styles.rowTitle}>{entry.title}</span>
              <span className={styles.rowActions}>
                <button type="button" className={styles.textButton} onClick={() => openEdit(entry.locale)} disabled={isBusy}>
                  {t('content_translations.manage.edit')}
                </button>
                <button type="button" className={styles.textButton} onClick={() => handleDelete(entry.locale)} disabled={isBusy}>
                  {t('content_translations.manage.delete')}
                </button>
              </span>
            </li>
          ))}
        </ul>
      ) : (
        <p className={styles.empty}>{t('content_translations.manage.empty')}</p>
      )}

      {form ? (
        <form className={styles.form} onSubmit={handleSubmit}>
          <label className={styles.field}>
            <span className={styles.label}>{t('content_translations.manage.language')}</span>
            {form.isNew ? (
              <select
                className={styles.input}
                value={form.locale}
                required
                onChange={(event) => updateField('locale', event.target.value)}
              >
                <option value="">{t('content_translations.manage.language_placeholder')}</option>
                {languageOptions.map((option) => (
                  <option key={option.value} value={option.value}>{option.label}</option>
                ))}
              </select>
            ) : (
              <span className={styles.fixedValue}>{languageName(form.locale, uiLocale)}</span>
            )}
          </label>
          {form.isNew && isEditingExisting ? (
            <p className={styles.hint}>{t('content_translations.manage.already_exists')}</p>
          ) : null}

          <label className={styles.field}>
            <span className={styles.label}>{t('content_translations.manage.title_field')}</span>
            <input
              className={styles.input}
              type="text"
              value={form.title}
              maxLength={255}
              required
              lang={form.locale || undefined}
              onChange={(event) => updateField('title', event.target.value)}
            />
          </label>

          {withSummary ? (
            <label className={styles.field}>
              <span className={styles.label}>{t('content_translations.manage.summary_field')}</span>
              <textarea
                className={[styles.input, styles.summary].join(' ')}
                value={form.summary}
                maxLength={5000}
                lang={form.locale || undefined}
                onChange={(event) => updateField('summary', event.target.value)}
              />
            </label>
          ) : null}

          <div className={styles.field}>
            <span className={styles.label}>{t('content_translations.manage.content_field')}</span>
            <RichTextEditor
              value={form.content}
              ariaLabel={t('content_translations.manage.content_field')}
              compactParagraphs={contentType === 'poem'}
              onChange={(content) => updateField('content', content)}
            />
          </div>

          <div className={styles.formActions}>
            <button type="button" className={styles.secondaryButton} onClick={() => setForm(null)} disabled={isBusy}>
              {t('content_translations.manage.cancel')}
            </button>
            <button type="submit" className={styles.primaryButton} disabled={isBusy}>
              {isBusy ? t('content_translations.manage.saving') : t('content_translations.manage.save')}
            </button>
          </div>
        </form>
      ) : null}

      {error ? <p className={styles.error} role="alert">{error}</p> : null}
      {notice && !error ? <p className={styles.notice} role="status">{notice}</p> : null}
    </section>
  )
}
