import { useEffect, useState } from 'react'
import { useI18n } from '../../app/hooks/useI18n.js'
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
  reasonLabel,
  detailsLabel,
  detailsPlaceholder,
  isSubmitting = false,
  onClose,
  onConfirm,
}) {
  const { t } = useI18n()
  const [reason, setReason] = useState('spam')
  const [details, setDetails] = useState('')
  const resolvedReasonLabel = reasonLabel ?? t('report.ui.reason')
  const resolvedDetailsLabel = detailsLabel ?? t('report.ui.details')
  const resolvedDetailsPlaceholder = detailsPlaceholder ?? t('report.ui.details_placeholder')
  const resolvedCancelLabel = cancelLabel ?? t('common.cancel')
  const resolvedConfirmLabel = confirmLabel ?? t('poems.actions.report_confirm')

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
          <span className={styles.fieldLabel}>{resolvedReasonLabel}</span>
          <select
            className={styles.fieldInput}
            value={reason}
            onChange={(event) => setReason(event.target.value)}
            disabled={isSubmitting}
          >
            <option value="spam">{t('report.ui.reasons.spam')}</option>
            <option value="abuse">{t('report.ui.reasons.abuse')}</option>
            <option value="harassment">{t('report.ui.reasons.harassment')}</option>
            <option value="other">{t('report.ui.reasons.other')}</option>
          </select>
        </label>
        <label className={styles.field}>
          <span className={styles.fieldLabel}>{resolvedDetailsLabel}</span>
          <textarea
            className={styles.fieldInput}
            rows={3}
            maxLength={5000}
            value={details}
            onChange={(event) => setDetails(event.target.value)}
            placeholder={resolvedDetailsPlaceholder}
            disabled={isSubmitting}
          />
        </label>

        <div className={styles.actions}>
          <button type="button" className={styles.cancelButton} onClick={onClose} disabled={isSubmitting}>
            {resolvedCancelLabel}
          </button>
          <PillButton
            label={isSubmitting ? t('common.submitting') : resolvedConfirmLabel}
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
