import { useEffect, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import {
  confirmNewsletter,
  subscribeNewsletter,
  unsubscribeNewsletter,
} from '../../app/api/newsletter.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './NewsletterPage.module.css'

export default function NewsletterPage() {
  const { t, locale } = useI18n()
  const [searchParams, setSearchParams] = useSearchParams()
  const [email, setEmail] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const processedToken = useRef('')
  const confirmToken = searchParams.get('confirm') || ''
  const unsubscribeToken = searchParams.get('unsubscribe') || ''

  useEffect(() => {
    const token = confirmToken || unsubscribeToken
    const action = confirmToken ? 'confirm' : unsubscribeToken ? 'unsubscribe' : ''
    if (!token || processedToken.current === `${action}:${token}`) {
      return
    }

    processedToken.current = `${action}:${token}`
    setMessage(t(action === 'confirm' ? 'newsletter.confirming' : 'newsletter.unsubscribing'))
    setError('')

    const request = action === 'confirm'
      ? confirmNewsletter(token)
      : unsubscribeNewsletter(token)

    request
      .then(() => {
        setMessage(t(action === 'confirm' ? 'newsletter.confirmed' : 'newsletter.unsubscribed'))
        setSearchParams({}, { replace: true })
      })
      .catch((requestError) => {
        setMessage('')
        setError(requestError?.message || t(
          action === 'confirm' ? 'newsletter.confirm_error' : 'newsletter.unsubscribe_error',
        ))
      })
  }, [confirmToken, setSearchParams, t, unsubscribeToken])

  async function handleSubmit(event) {
    event.preventDefault()
    setIsSubmitting(true)
    setMessage('')
    setError('')

    try {
      await subscribeNewsletter(email, locale)
      setEmail('')
      setMessage(t('newsletter.subscribe_success'))
    } catch (submitError) {
      setError(submitError?.message || t('newsletter.subscribe_error'))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <MainLayout>
      <main className={styles.page}>
        <section className={styles.card}>
          <p className={styles.eyebrow}>Slam In</p>
          <h1>{t('newsletter.page_title')}</h1>
          <p className={styles.lead}>{t('newsletter.page_lead')}</p>

          <form className={styles.form} onSubmit={handleSubmit}>
            <label htmlFor="newsletter-email">{t('newsletter.email')}</label>
            <div className={styles.inputRow}>
              <input
                id="newsletter-email"
                type="email"
                required
                autoComplete="email"
                value={email}
                placeholder={t('newsletter.email_placeholder')}
                onChange={(event) => setEmail(event.target.value)}
              />
              <button type="submit" disabled={isSubmitting}>
                {t(isSubmitting ? 'newsletter.subscribing' : 'newsletter.subscribe')}
              </button>
            </div>
          </form>

          <p className={styles.privacy}>{t('newsletter.privacy')}</p>
          {message ? <p className={styles.success} role="status">{message}</p> : null}
          {error ? <p className={styles.error} role="alert">{error}</p> : null}
        </section>
      </main>
    </MainLayout>
  )
}
