import { useEffect } from 'react'
import PillButton from './PillButton.jsx'
import styles from './ArticleReportModal.module.css'

export default function ContentReportModal({
  isOpen,
  item,
  itemTitle,
  title,
  description,
  cancelLabel,
  confirmLabel,
  onClose,
  onConfirm,
}) {
  useEffect(() => {
    if (!isOpen) {
      return undefined
    }

    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        onClose()
      }
    }

    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [isOpen, onClose])

  if (!isOpen) {
    return null
  }

  const resolvedItemTitle = itemTitle ?? item?.title ?? ''

  return (
    <div className={styles.overlay} role="presentation" onClick={onClose}>
      <section
        className={styles.modal}
        role="dialog"
        aria-modal="true"
        aria-labelledby="content-report-modal-title"
        onClick={(event) => event.stopPropagation()}
      >
        <h3 id="content-report-modal-title" className={styles.title}>
          {title}
        </h3>
        <p className={styles.description}>{description}</p>
        <p className={styles.articleTitle}>{resolvedItemTitle}</p>

        <div className={styles.actions}>
          <button type="button" className={styles.cancelButton} onClick={onClose}>
            {cancelLabel}
          </button>
          <PillButton
            label={confirmLabel}
            variant="accent"
            size="compact"
            icon="arrow"
            onClick={onConfirm}
          />
        </div>
      </section>
    </div>
  )
}
