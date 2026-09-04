import { useEffect, useState } from 'react'
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
  reasonLabel = 'Motivo',
  detailsLabel = 'Dettagli (opzionale)',
  detailsPlaceholder = 'Aggiungi dettagli utili',
  isSubmitting = false,
  onClose,
  onConfirm,
}) {
  const [reason, setReason] = useState('spam')
  const [details, setDetails] = useState('')

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
        <label className={styles.field}>
          <span className={styles.fieldLabel}>{reasonLabel}</span>
          <select
            className={styles.fieldInput}
            value={reason}
            onChange={(event) => setReason(event.target.value)}
            disabled={isSubmitting}
          >
            <option value="spam">Spam</option>
            <option value="abuse">Abuse</option>
            <option value="harassment">Harassment</option>
            <option value="other">Other</option>
          </select>
        </label>
        <label className={styles.field}>
          <span className={styles.fieldLabel}>{detailsLabel}</span>
          <textarea
            className={styles.fieldInput}
            rows={3}
            maxLength={5000}
            value={details}
            onChange={(event) => setDetails(event.target.value)}
            placeholder={detailsPlaceholder}
            disabled={isSubmitting}
          />
        </label>

        <div className={styles.actions}>
          <button type="button" className={styles.cancelButton} onClick={onClose} disabled={isSubmitting}>
            {cancelLabel}
          </button>
          <PillButton
            label={isSubmitting ? 'Invio...' : confirmLabel}
            variant="accent"
            size="compact"
            icon="arrow"
            onClick={() => onConfirm?.({ reason, details: details.trim() || null })}
          />
        </div>
      </section>
    </div>
  )
}
