import { useEffect, useRef, useState } from 'react'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { submitSupportRequest } from '../../app/api/support.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './SupportPage.module.css'

const categoryIds = ['technical', 'account', 'content', 'events', 'media', 'other']

export default function SupportPage() {
  const { t } = useI18n()
  const { token, user } = useAuth()
  const fileInputRef = useRef(null)
  const [form, setForm] = useState({
    name: '',
    email: '',
    category: '',
    subject: '',
    message: '',
  })
  const [attachments, setAttachments] = useState([])
  const [status, setStatus] = useState(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    setForm((current) => ({
      ...current,
      name: current.name || user?.display_name || user?.name || '',
      email: current.email || user?.email || '',
    }))
  }, [user])

  const updateField = (event) => {
    const { name, value } = event.target
    setForm((current) => ({ ...current, [name]: value }))
  }

  const resetForm = () => {
    setForm({
      name: user?.display_name || user?.name || '',
      email: user?.email || '',
      category: '',
      subject: '',
      message: '',
    })
    setAttachments([])
    setStatus(null)
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setIsSubmitting(true)
    setStatus(null)

    const payload = new FormData()
    Object.entries(form).forEach(([key, value]) => payload.append(key, value))
    attachments.forEach((file) => payload.append('attachments[]', file))

    try {
      await submitSupportRequest(payload, { token })
      resetForm()
      setStatus({ type: 'success', text: t('support.success') })
    } catch (error) {
      setStatus({ type: 'error', text: error?.message || t('support.error') })
    } finally {
      setIsSubmitting(false)
    }
  }

  const selectedFilesLabel = attachments.length
    ? attachments.map((file) => file.name).join(', ')
    : t('support.no_files')

  return (
    <MainLayout mainClassName={styles.page}>
      <header className={styles.header}>
        <h1>{t('support.title')}</h1>
        <p>{t('support.subtitle')}</p>
      </header>

      <form className={styles.form} onSubmit={handleSubmit}>
        <div className={styles.twoColumns}>
          <Field label={t('support.name')} required>
            <input
              name="name"
              value={form.name}
              onChange={updateField}
              placeholder={t('support.name_placeholder')}
              maxLength={120}
              required
            />
          </Field>
          <Field label={t('support.email')} required>
            <input
              type="email"
              name="email"
              value={form.email}
              onChange={updateField}
              placeholder={t('support.email_placeholder')}
              maxLength={255}
              required
            />
          </Field>
        </div>

        <Field label={t('support.category')} required>
          <select name="category" value={form.category} onChange={updateField} required>
            <option value="" disabled>{t('support.category_placeholder')}</option>
            {categoryIds.map((category) => (
              <option value={category} key={category}>
                {t(`support.categories.${category}`)}
              </option>
            ))}
          </select>
        </Field>

        <Field label={t('support.subject')} required>
          <input
            name="subject"
            value={form.subject}
            onChange={updateField}
            placeholder={t('support.subject_placeholder')}
            maxLength={255}
            required
          />
        </Field>

        <Field label={t('support.message')} required>
          <textarea
            name="message"
            value={form.message}
            onChange={updateField}
            placeholder={t('support.message_placeholder')}
            maxLength={10000}
            required
          />
          <small>{t('support.message_help')}</small>
        </Field>

        <div className={styles.attachments}>
          <label className={styles.attachmentLabel} htmlFor="support-attachments">
            <strong>{t('support.attachments')}</strong>
            <span>{t('support.optional')}</span>
          </label>
          <div className={styles.fileControl}>
            <label className={styles.fileButton} htmlFor="support-attachments">
              {t('support.choose_files')}
            </label>
            <span className={styles.fileNames}>{selectedFilesLabel}</span>
            <input
              ref={fileInputRef}
              id="support-attachments"
              type="file"
              multiple
              accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.log"
              onChange={(event) => setAttachments(Array.from(event.target.files || []).slice(0, 5))}
            />
          </div>
          <small>{t('support.attachments_help')}</small>
        </div>

        {status ? (
          <p className={`${styles.status} ${status.type === 'success' ? styles.success : styles.error}`} role="status">
            {status.text}
          </p>
        ) : null}

        <div className={styles.actions}>
          <button type="button" className={styles.cancel} onClick={resetForm}>
            {t('support.cancel')}
          </button>
          <button type="submit" className={styles.submit} disabled={isSubmitting}>
            <img src="/support/send-icon.svg" alt="" width="40" height="40" />
            {isSubmitting ? t('support.sending') : t('support.submit')}
          </button>
        </div>

        <aside className={styles.responseTime}>
          <img src="/support/info-icon.svg" alt="" width="50" height="50" />
          <div>
            <strong>{t('support.response_title')}</strong>
            <p>{t('support.response_text')} <a href="mailto:mail@slamin.it">mail@slamin.it</a></p>
          </div>
        </aside>
      </form>
    </MainLayout>
  )
}

function Field({ label, required = false, children }) {
  return (
    <label className={styles.field}>
      <strong>{label}{required ? ' *' : ''}</strong>
      {children}
    </label>
  )
}
