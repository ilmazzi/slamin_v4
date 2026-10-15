import { useEffect, useRef, useState } from 'react'
import { useLocation } from 'react-router-dom'
import { getNotificationEmailPreferences, updateNotificationEmailPreferences } from '../../app/api/notifications.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './ProfilePage.module.css'

const NOTIFICATIONS_ANCHOR = 'notifications'

export default function NotificationEmailPreferences({ onMessage }) {
  const { token } = useAuth()
  const { t } = useI18n()
  const { hash } = useLocation()
  const sectionRef = useRef(null)
  const [preferences, setPreferences] = useState(null)
  const [loadError, setLoadError] = useState('')
  const [pendingKey, setPendingKey] = useState('')

  useEffect(() => {
    const controller = new AbortController()

    getNotificationEmailPreferences({ token, signal: controller.signal })
      .then(setPreferences)
      .catch((error) => {
        if (error?.name !== 'AbortError') setLoadError(t('notifications.preferences.load_error'))
      })

    return () => controller.abort()
  }, [t, token])

  useEffect(() => {
    if (preferences && hash === `#${NOTIFICATIONS_ANCHOR}`) {
      sectionRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, [hash, preferences])

  const toggle = async (key) => {
    const enabled = !preferences[key]
    setPreferences((current) => ({ ...current, [key]: enabled }))
    setPendingKey(key)

    try {
      setPreferences(await updateNotificationEmailPreferences({ [key]: enabled }, { token }))
      onMessage(t('notifications.preferences.saved'))
    } catch {
      setPreferences((current) => ({ ...current, [key]: !enabled }))
      onMessage(t('notifications.preferences.save_error'))
    } finally {
      setPendingKey('')
    }
  }

  return (
    <fieldset id={NOTIFICATIONS_ANCHOR} ref={sectionRef} className={styles.visibility}>
      <legend className={styles.visibilityTitle}>{t('notifications.preferences.title')}</legend>
      <p className={styles.hint}>{t('notifications.preferences.description')}</p>
      {loadError ? <p className={styles.error}>{loadError}</p> : null}
      {preferences
        ? Object.entries(preferences).map(([key, enabled]) => (
          <label key={key} className={styles.switchRow}>
            <input
              type="checkbox"
              checked={enabled}
              disabled={pendingKey === key}
              onChange={() => toggle(key)}
            />
            <span>
              <strong>{t(`notifications.preferences.categories.${key}.label`)}</strong>
              <span className={styles.hint}>{t(`notifications.preferences.categories.${key}.description`)}</span>
            </span>
          </label>
        ))
        : null}
    </fieldset>
  )
}
