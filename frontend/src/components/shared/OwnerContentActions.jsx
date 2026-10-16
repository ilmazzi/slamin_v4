import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ActivityDeleteIcon, PenIcon } from '../icons/ActionIcons.jsx'
import styles from './OwnerContentActions.module.css'

export default function OwnerContentActions({
  editHref,
  editLabel,
  deleteLabel,
  deletingLabel,
  confirmMessage,
  deleteErrorMessage,
  onDelete,
}) {
  const [isDeleting, setIsDeleting] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')

  async function handleDelete() {
    if (isDeleting || !window.confirm(confirmMessage)) {
      return
    }

    setIsDeleting(true)
    setErrorMessage('')

    try {
      await onDelete()
    } catch (error) {
      setErrorMessage(error?.message || deleteErrorMessage)
      setIsDeleting(false)
    }
  }

  return (
    <div className={styles.wrapper}>
      <div className={styles.actions} role="group" aria-label={editLabel}>
        <Link className={styles.editButton} to={editHref}>
          <PenIcon className={styles.icon} />
          <span>{editLabel}</span>
        </Link>
        <button
          type="button"
          className={styles.deleteButton}
          disabled={isDeleting}
          onClick={handleDelete}
        >
          <ActivityDeleteIcon className={styles.icon} />
          <span>{isDeleting ? deletingLabel : deleteLabel}</span>
        </button>
      </div>
      {errorMessage ? <p className={styles.error} role="alert">{errorMessage}</p> : null}
    </div>
  )
}
