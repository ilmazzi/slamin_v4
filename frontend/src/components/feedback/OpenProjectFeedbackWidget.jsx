import { useMemo, useState } from 'react'
import { getApiUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import styles from './OpenProjectFeedbackWidget.module.css'

const isEnabled = String(import.meta.env.VITE_OPENPROJECT_FEEDBACK_ENABLED ?? 'true') !== 'false'
const authOnly = String(import.meta.env.VITE_OPENPROJECT_FEEDBACK_AUTH_ONLY ?? 'true') !== 'false'
const feedbackRoute = import.meta.env.VITE_OPENPROJECT_FEEDBACK_ROUTE ?? '/api/feedback'
const widgetLabel = import.meta.env.VITE_OPENPROJECT_FEEDBACK_TEXT ?? 'Feedback'

export default function OpenProjectFeedbackWidget() {
  const { isAuthenticated, token } = useAuth()
  const [isOpen, setIsOpen] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [status, setStatus] = useState(null)
  const [subject, setSubject] = useState('')
  const [description, setDescription] = useState('')
  const [screenshot, setScreenshot] = useState(null)

  const endpoint = useMemo(() => getApiUrl(feedbackRoute), [])

  if (!isEnabled) {
    return null
  }

  if (authOnly && !isAuthenticated) {
    return null
  }

  const resetForm = () => {
    setSubject('')
    setDescription('')
    setScreenshot(null)
  }

  const handleClose = () => {
    setIsOpen(false)
    setStatus(null)
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setIsSubmitting(true)
    setStatus(null)

    try {
      const formData = new FormData()
      formData.append('subject', subject)
      formData.append('description', description)
      formData.append('url', window.location.href)

      if (screenshot) {
        formData.append('screenshot', screenshot)
      }

      const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }

      if (token) {
        headers.Authorization = `Bearer ${token}`
      }

      const response = await fetch(endpoint, {
        method: 'POST',
        headers,
        body: formData,
      })

      if (!response.ok) {
        let details = ''

        try {
          const contentType = response.headers.get('content-type') || ''

          if (contentType.includes('application/json')) {
            const payload = await response.json()
            details = payload?.errors?.message || payload?.message || JSON.stringify(payload?.errors || {})
          } else {
            details = (await response.text())?.slice(0, 300)
          }
        } catch {
          details = ''
        }

        throw new Error(
          `Feedback request failed (${response.status})${details ? `: ${details}` : ''}`,
        )
      }

      setStatus({ type: 'success', text: 'Feedback inviato correttamente.' })
      resetForm()
    } catch (error) {
      console.error(error)
      setStatus({
        type: 'error',
        text: error?.message || 'Invio non riuscito. Verifica endpoint/configurazione OpenProject.',
      })
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <>
      <button type="button" className={styles.button} onClick={() => setIsOpen(true)}>
        {widgetLabel}
      </button>

      {isOpen ? (
        <div className={styles.overlay} onClick={handleClose}>
          <div
            className={styles.modal}
            onClick={(event) => {
              event.stopPropagation()
            }}
          >
            <h2 className={styles.title}>OpenProject feedback</h2>

            <form className={styles.form} onSubmit={handleSubmit}>
              <label className={styles.field}>
                <span>Oggetto</span>
                <input
                  required
                  value={subject}
                  onChange={(event) => setSubject(event.target.value)}
                  maxLength={120}
                />
              </label>

              <label className={styles.field}>
                <span>Descrizione</span>
                <textarea
                  required
                  rows={5}
                  value={description}
                  onChange={(event) => setDescription(event.target.value)}
                />
              </label>

              <label className={styles.field}>
                <span>Screenshot (opzionale)</span>
                <input
                  type="file"
                  accept="image/*"
                  onChange={(event) => setScreenshot(event.target.files?.[0] ?? null)}
                />
              </label>

              {status ? (
                <p
                  className={`${styles.message} ${status.type === 'success' ? styles.success : styles.error}`}
                >
                  {status.text}
                </p>
              ) : null}

              <div className={styles.actions}>
                <button type="button" className={styles.cancel} onClick={handleClose}>
                  Chiudi
                </button>
                <button type="submit" className={styles.submit} disabled={isSubmitting}>
                  {isSubmitting ? 'Invio...' : 'Invia'}
                </button>
              </div>
            </form>
          </div>
        </div>
      ) : null}
    </>
  )
}
