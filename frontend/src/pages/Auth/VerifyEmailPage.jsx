import { useEffect, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { resendEmailVerification, verifyEmail } from '../../app/api/emailVerification.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './VerifyEmailPage.module.css'

export default function VerifyEmailPage() {
  const { t } = useI18n()
  const { token, user, isAuthenticated, refreshUser } = useAuth()
  const [searchParams] = useSearchParams()
  const refreshUserRef = useRef(refreshUser)
  const tRef = useRef(t)
  refreshUserRef.current = refreshUser
  tRef.current = t
  const verificationPayload = searchParams.toString()
  const hasVerificationPayload = ['id', 'hash', 'expires', 'signature']
    .every((key) => Boolean(searchParams.get(key)))
  const [status, setStatus] = useState(
    user?.email_verified ? 'success' : hasVerificationPayload ? 'verifying' : 'pending',
  )
  const [message, setMessage] = useState('')
  const [isResending, setIsResending] = useState(false)

  useEffect(() => {
    if (!hasVerificationPayload) return undefined

    const controller = new AbortController()
    const params = new URLSearchParams(verificationPayload)

    async function runVerification() {
      setStatus('verifying')
      setMessage('')

      try {
        await verifyEmail(params, { signal: controller.signal })
        if (token) await refreshUserRef.current()
        setStatus('success')
      } catch (error) {
        if (error?.name === 'AbortError') return
        setMessage(error?.message || tRef.current('auth.verify_email_error'))
        setStatus('error')
      }
    }

    runVerification()
    return () => controller.abort()
  }, [hasVerificationPayload, verificationPayload, token])

  async function handleResend() {
    if (!token) return

    setIsResending(true)
    setMessage('')
    try {
      await resendEmailVerification({ token })
      setMessage(t('auth.verify_email_resent'))
      setStatus('pending')
    } catch (error) {
      setMessage(error?.message || t('auth.verify_email_error'))
    } finally {
      setIsResending(false)
    }
  }

  const body = status === 'verifying'
    ? t('auth.verify_email_verifying')
    : status === 'success'
      ? t('auth.verify_email_success')
      : status === 'error'
        ? t('auth.verify_email_error')
        : t('auth.verify_email_check_inbox')

  return (
    <MainLayout variant={isAuthenticated ? 'auth' : 'guest'}>
      <main className={styles.page}>
        <section className={styles.card}>
          <h1>{t('auth.verify_email_title')}</h1>
          <p>{body}</p>
          {message ? <p className={status === 'error' ? styles.error : styles.message}>{message}</p> : null}

          <div className={styles.actions}>
            {isAuthenticated && status !== 'success' ? (
              <button type="button" onClick={handleResend} disabled={isResending || status === 'verifying'}>
                {isResending ? t('auth.verify_email_resending') : t('auth.verify_email_resend')}
              </button>
            ) : null}
            {status === 'success' ? (
              <Link to={isAuthenticated ? '/dashboard' : '/login'}>
                {t('auth.verify_email_dashboard')}
              </Link>
            ) : null}
          </div>
        </section>
      </main>
    </MainLayout>
  )
}
