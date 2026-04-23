import { useEffect } from 'react'
import PillButton from './PillButton.jsx'
import styles from './ArticleReportModal.module.css'

export default function ArticleReportModal({ isOpen, article, t, onClose, onConfirm }) {
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

  const articleTitle = article?.title ?? t('articles.all.no_title')

  return (
    <div className={styles.overlay} role="presentation" onClick={onClose}>
      <section
        className={styles.modal}
        role="dialog"
        aria-modal="true"
        aria-labelledby="article-report-modal-title"
        onClick={(event) => event.stopPropagation()}
      >
        <h3 id="article-report-modal-title" className={styles.title}>
          {t('articles.actions.report_title')}
        </h3>
        <p className={styles.description}>{t('articles.actions.report_description')}</p>
        <p className={styles.articleTitle}>{articleTitle}</p>

        <div className={styles.actions}>
          <button type="button" className={styles.cancelButton} onClick={onClose}>
            {t('articles.actions.report_cancel')}
          </button>
          <PillButton
            label={t('articles.actions.report_confirm')}
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
