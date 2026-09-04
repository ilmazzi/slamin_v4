import { useEffect } from 'react'
import styles from './AppToast.module.css'

const AUTO_HIDE_MS = 2600

export default function AppToast({ message, isVisible, onClose }) {
  useEffect(() => {
    if (!isVisible) {
      return undefined
    }

    const timeoutId = window.setTimeout(() => {
      onClose()
    }, AUTO_HIDE_MS)

    return () => window.clearTimeout(timeoutId)
  }, [isVisible, onClose])

  if (!isVisible || !message) {
    return null
  }

  return (
    <div className={styles.toast} role="status" aria-live="polite">
      {message}
    </div>
  )
}
