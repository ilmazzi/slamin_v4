import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './CookieNotice.module.css'

const STORAGE_KEY = 'slamin.cookie_notice_acknowledged.v1'

function wasAcknowledged() {
  try {
    return window.localStorage.getItem(STORAGE_KEY) === 'true'
  } catch {
    return false
  }
}

export default function CookieNotice() {
  const { t } = useI18n()
  const [isVisible, setIsVisible] = useState(() => !wasAcknowledged())

  if (!isVisible) {
    return null
  }

  function acknowledge() {
    try {
      window.localStorage.setItem(STORAGE_KEY, 'true')
    } catch {
      // The notice can still be dismissed when browser storage is unavailable.
    }

    setIsVisible(false)
  }

  return (
    <aside className={styles.notice} aria-label={t('cookies.notice_title')}>
      <div className={styles.copy}>
        <strong>{t('cookies.notice_title')}</strong>
        <p>{t('cookies.notice_body')}</p>
      </div>
      <div className={styles.actions}>
        <Link to="/cookies">{t('cookies.notice_link')}</Link>
        <button type="button" onClick={acknowledge}>{t('cookies.notice_acknowledge')}</button>
      </div>
    </aside>
  )
}
