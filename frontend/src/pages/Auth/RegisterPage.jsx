import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { getApiUrl } from '../../app/api/url.js'
import { readApiErrorMessage, readJsonSafe } from '../../app/api/slaminApi.js'
import styles from './RegisterPage.module.css'

const ACCOUNT_TYPES = [
  { value: 'audience', label: 'Audience' },
  { value: 'poet_organizer', label: 'Poet/Organizer' },
]

export function RegisterPage() {
  const { t, locale } = useI18n()
  const navigate = useNavigate()
  const { login } = useAuth()
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')
  const [formData, setFormData] = useState({
    email: '',
    nickname: '',
    display_name: '',
    account_type: 'audience',
    password: '',
    password_confirmation: '',
  })

  const onChange = (event) => {
    const { name, value } = event.target
    setFormData((current) => ({ ...current, [name]: value }))
  }

  const onSubmit = async (event) => {
    event.preventDefault()
    setErrorMessage('')
    setIsSubmitting(true)

    try {
      const response = await fetch(getApiUrl('/api/v1/register'), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          ...formData,
          preferred_locale: locale || 'it',
        }),
      })

      const payload = await readJsonSafe(response)

      if (!response.ok) {
        setErrorMessage(readApiErrorMessage(payload, t('common.no_results')))
        return
      }

      const data = payload?.data
      if (!data?.token || !data?.user) {
        setErrorMessage(t('common.no_results'))
        return
      }

      await login(data.token, data.user)
      navigate('/verify-email', { replace: true })
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
          <h1 className={styles.title}>{t('register.create_account')}</h1>
          <p className={styles.subtitle}>{t('register.complete_profile')}</p>
          {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}
          <form className={styles.form} onSubmit={onSubmit}>
            <label className={styles.field}>
              <span className={styles.label}>{t('register.nickname')}</span>
              <input
                className={styles.input}
                name="nickname"
                value={formData.nickname}
                onChange={onChange}
                placeholder={t('register.nickname_placeholder')}
                required
              />
            </label>
            <label className={styles.field}>
              <span className={styles.label}>{t('register.first_name')}</span>
              <input
                className={styles.input}
                name="display_name"
                value={formData.display_name}
                onChange={onChange}
                placeholder={t('register.first_name_placeholder')}
              />
            </label>
            <label className={styles.field}>
              <span className={styles.label}>{t('register.email')}</span>
              <input
                className={styles.input}
                name="email"
                type="email"
                value={formData.email}
                onChange={onChange}
                placeholder={t('register.email_placeholder')}
                required
              />
            </label>
            <label className={styles.field}>
              <span className={styles.label}>{t('register.choose_role')}</span>
              <select
                className={styles.select}
                name="account_type"
                value={formData.account_type}
                onChange={onChange}
                required
              >
                {ACCOUNT_TYPES.map((accountType) => (
                  <option key={accountType.value} value={accountType.value}>
                    {accountType.label}
                  </option>
                ))}
              </select>
            </label>
            <div className={styles.gridTwo}>
              <label className={styles.field}>
                <span className={styles.label}>{t('register.password')}</span>
                <input
                  className={styles.input}
                  name="password"
                  type="password"
                  value={formData.password}
                  onChange={onChange}
                  required
                />
              </label>
              <label className={styles.field}>
                <span className={styles.label}>{t('register.confirm_password')}</span>
                <input
                  className={styles.input}
                  name="password_confirmation"
                  type="password"
                  value={formData.password_confirmation}
                  onChange={onChange}
                  required
                />
              </label>
            </div>
            <button className={styles.submit} type="submit" disabled={isSubmitting}>
              {isSubmitting ? t('common.loading') : t('register.join_slam_in')}
            </button>
          </form>
          <p className={styles.footer}>
            {t('register.already_have_account')}{' '}
            <Link className={styles.link} to="/login">
              {t('register.login')}
            </Link>
          </p>
        </section>
      </main>
    </MainLayout>
  )
}
