import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { getApiUrl } from '../../app/api/url.js'
import { readApiErrorMessage, readJsonSafe } from '../../app/api/slaminApi.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './LoginPage.module.css'

import {
  loginCalendarIcon,
  loginCommunityIcon,
  loginPerformanceIcon,
  loginCompetitionIcon,
} from '../../assets/icons/login/index.js'


export function LoginPage() {
  const { t } = useI18n()
  const { login } = useAuth()
  const navigate = useNavigate()
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    remember: false,
  })
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')

  const benefits = [
    { id: 'events', label: t('login.events_and_shows'), icon: loginCalendarIcon },
    { id: 'community', label: t('login.poets_community'), icon: loginCommunityIcon },
    { id: 'performances', label: t('login.share_your_performances'), icon: loginPerformanceIcon },
    { id: 'competitions', label: t('login.participate_in_competitions'), icon: loginCompetitionIcon },
  ]

  const handleInputChange = (event) => {
    const { name, value, type, checked } = event.target

    setFormData((current) => ({
      ...current,
      [name]: type === 'checkbox' ? checked : value,
    }))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setErrorMessage('')
    setIsSubmitting(true)

    try {
      const response = await fetch(getApiUrl('/api/v1/login'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: formData.email,
          password: formData.password,
        }),
      })

      const payload = await readJsonSafe(response)

      if (!response.ok) {
        setErrorMessage(
          readApiErrorMessage(payload, t('auth.credentials_invalid')),
        )
        return
      }

      const data = payload?.data
      const token = data?.token
      const user = data?.user
      if (!token || !user) {
        setErrorMessage(t('common.no_results'))
        return
      }

      await login(token, user)
      navigate(user.email_verified || user.email_verified_at ? '/dashboard' : '/verify-email', { replace: true })
    } catch {
      setErrorMessage(t('common.no_results'))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <MainLayout variant="guest" pageClassName={styles.page} mainClassName={styles.main}>
        <section className={styles.body}>
          <div className={styles.heroPanel}>
            <div className={styles.heroContent}>
              <h1 className={styles.heroTitle}>{t('login.hero_heading')}</h1>

              <div className={styles.benefitsList}>
              {benefits.map(({ id, label, icon }) => (
  <div key={id} className={styles.benefitRow}>
    <span className={styles.benefitIcon}>
      <img src={icon} alt="" className={styles.benefitIconImage} aria-hidden="true" />
    </span>
    <span className={styles.benefitLabel}>{label}</span>
  </div>
))}

              </div>
            </div>
          </div>

          <div className={styles.formPanel}>
            <div className={styles.formIntro}>
              <h2 className={styles.formTitle}>{t('login.login_to_your_account')}</h2>
              <p className={styles.formSubtitle}>
                {t('login.enter_slam_in_and_discover_the_italian_slam_world')}
              </p>
            </div>

            <form className={styles.form} onSubmit={handleSubmit}>
              <div className={styles.fieldGroup}>
                <label className={styles.fieldLabel} htmlFor="login-email">
                  {t('login.email')}
                </label>
                <input
                  id="login-email"
                  name="email"
                  type="email"
                  className={styles.fieldInput}
                  placeholder={t('login.email_placeholder')}
                  autoComplete="email"
                  value={formData.email}
                  onChange={handleInputChange}
                  disabled={isSubmitting}
                  required
                />
              </div>

              <div className={styles.fieldGroup}>
                <label className={styles.fieldLabel} htmlFor="login-password">
                  {t('login.password')}
                </label>
                <input
                  id="login-password"
                  name="password"
                  type="password"
                  className={styles.fieldInput}
                  placeholder={t('login.password_placeholder')}
                  autoComplete="current-password"
                  value={formData.password}
                  onChange={handleInputChange}
                  disabled={isSubmitting}
                  required
                />
              </div>

              <div className={styles.formMeta}>
                <label className={styles.rememberMe}>
                  <input
                    type="checkbox"
                    name="remember"
                    className={styles.checkboxInput}
                    checked={formData.remember}
                    onChange={handleInputChange}
                    disabled={isSubmitting}
                  />
                  <span className={styles.checkboxBox} aria-hidden="true" />
                  <span className={styles.rememberLabel}>{t('login.remember_me')}</span>
                </label>

                <Link to="/forgot-password" className={styles.forgotPassword}>
                  {t('auth.forgot_password')}
                </Link>
              </div>

              {errorMessage ? <p className={styles.errorMessage}>{errorMessage}</p> : null}

              <button type="submit" className={styles.submitButton} disabled={isSubmitting}>
                {isSubmitting ? t('common.loading') : t('login.enter_slam_in')}
              </button>
            </form>

            <p className={styles.registerPrompt}>
              <span className={styles.registerPromptBase}>{t('login.no_account')} </span>
              <Link to="/register" className={styles.registerLink}>
                {t('login.register_here')}
              </Link>
            </p>
          </div>
        </section>
    </MainLayout>
  )
}
