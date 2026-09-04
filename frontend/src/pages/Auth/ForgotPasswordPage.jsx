import { useState } from 'react'
import { Link } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import { getApiUrl } from '../../app/api/url.js'
import { readApiErrorMessage, readJsonSafe } from '../../app/api/slaminApi.js'
import styles from './RegisterPage.module.css'

export function ForgotPasswordPage() {
  const { t } = useI18n()
  const [email, setEmail] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')
  const [successMessage, setSuccessMessage] = useState('')

  const onSubmit = async (event) => {
    event.preventDefault()
    setErrorMessage('')
    setSuccessMessage('')
    setIsSubmitting(true)

    try {
      const response = await fetch(getApiUrl('/api/v1/forgot-password'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email }),
      })

      const payload = await readJsonSafe(response)

      if (!response.ok) {
        setErrorMessage(readApiErrorMessage(payload, t('common.no_results')))
        return
      }

      setSuccessMessage(payload?.data?.message || t('auth.forgot_password_sent'))
    } catch {
      setErrorMessage(t('common.no_results'))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <MainLayout variant="guest">
      <main className={styles.page}>
        <section className={styles.card}>
          <h1 className={styles.title}>{t('auth.forgot_password')}</h1>
          <p className={styles.subtitle}>{t('register.email_placeholder')}</p>
          {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}
          {successMessage ? <p>{successMessage}</p> : null}
          <form className={styles.form} onSubmit={onSubmit}>
            <label className={styles.field}>
              <span className={styles.label}>{t('register.email')}</span>
              <input
                className={styles.input}
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                placeholder={t('register.email_placeholder')}
                required
              />
            </label>
            <button className={styles.submit} type="submit" disabled={isSubmitting}>
              {isSubmitting ? t('common.loading') : t('auth.forgot_password')}
            </button>
          </form>
          <p className={styles.footer}>
            <Link className={styles.link} to="/login">
              {t('register.login')}
            </Link>
          </p>
        </section>
      </main>
    </MainLayout>
  )
}
