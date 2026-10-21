import { useState } from 'react'
import { updateMyPassword } from '../../app/api/profile.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './ProfilePage.module.css'

const EMPTY_FORM = {
  current_password: '',
  password: '',
  password_confirmation: '',
}

export default function ChangePasswordForm({ onMessage }) {
  const { token } = useAuth()
  const { t } = useI18n()
  const [form, setForm] = useState(EMPTY_FORM)
  const [errorMessage, setErrorMessage] = useState('')
  const [isSaving, setIsSaving] = useState(false)

  const onFieldChange = (event) => {
    const { name, value } = event.target
    setForm((current) => ({ ...current, [name]: value }))
  }

  const onSubmit = async (event) => {
    event.preventDefault()
    if (!token || isSaving) {
      return
    }

    setErrorMessage('')
    setIsSaving(true)

    try {
      await updateMyPassword(form, { token })
      setForm(EMPTY_FORM)
      onMessage(t('profile.page.password_saved'))
    } catch (error) {
      setErrorMessage(error?.message || t('profile.page.password_save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <form id="password" className={`${styles.form} ${styles.settingsCard}`} onSubmit={onSubmit}>
      <fieldset className={styles.visibility}>
        <legend className={styles.visibilityTitle}>{t('profile.page.password_title')}</legend>
        <p className={styles.hint}>{t('profile.page.password_hint')}</p>
        {errorMessage ? <p className={styles.error} role="alert">{errorMessage}</p> : null}

        <label className={styles.field}>
          <span className={styles.label}>{t('profile.page.current_password')}</span>
          <input
            className={styles.input}
            name="current_password"
            type="password"
            autoComplete="current-password"
            value={form.current_password}
            onChange={onFieldChange}
            required
          />
        </label>

        <div className={styles.grid}>
          <label className={styles.field}>
            <span className={styles.label}>{t('auth.new_password')}</span>
            <input
              className={styles.input}
              name="password"
              type="password"
              autoComplete="new-password"
              value={form.password}
              onChange={onFieldChange}
              minLength={8}
              required
            />
            <span className={styles.hint}>{t('auth.password_requirements')}</span>
          </label>
          <label className={styles.field}>
            <span className={styles.label}>{t('auth.confirm_password')}</span>
            <input
              className={styles.input}
              name="password_confirmation"
              type="password"
              autoComplete="new-password"
              value={form.password_confirmation}
              onChange={onFieldChange}
              minLength={8}
              required
            />
          </label>
        </div>
      </fieldset>

      <div className={styles.formActions}>
        <button className={styles.submit} type="submit" disabled={isSaving}>
          {isSaving ? t('profile.page.changing_password') : t('profile.page.change_password')}
        </button>
      </div>
    </form>
  )
}
