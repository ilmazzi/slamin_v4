import { useState } from 'react'
import { followGroup, leaveGroup, requestGroupRole } from '../../app/api/groups.js'
import styles from './GroupMembershipActions.module.css'

export default function GroupMembershipActions({
  group,
  token,
  t,
  onUpdated,
  compact = false,
}) {
  const [isBusy, setIsBusy] = useState(false)
  const [error, setError] = useState('')
  const membership = group?.current_user_membership
  const permissions = group?.permissions || {}

  const run = async (event, action) => {
    event?.preventDefault()
    event?.stopPropagation()
    if (!group?.slug || !token) return
    setIsBusy(true)
    setError('')
    try {
      await action()
      await onUpdated?.()
    } catch (actionError) {
      setError(actionError?.message || t('group.ui.load_error'))
    } finally {
      setIsBusy(false)
    }
  }

  return (
    <div className={compact ? styles.compact : styles.wrap}>
      {permissions.follow ? (
        <button
          type="button"
          className={`${styles.btn} ${styles.btnAccent}`}
          disabled={isBusy}
          onClick={(event) => run(event, () => followGroup(group.slug, { token }))}
        >
          {t('group.ui.follow')}
        </button>
      ) : null}

      {membership?.status === 'pending' && !membership?.requested_role ? (
        <span className={styles.status}>{t('group.ui.follow_pending')}</span>
      ) : null}

      {membership?.status === 'active' && membership?.role === 'follower' ? (
        <span className={styles.status}>{t('group.ui.following')}</span>
      ) : null}

      {membership?.status === 'active' && membership?.role && membership.role !== 'follower' ? (
        <span className={styles.status}>{t(`group.roles.${membership.role}`)}</span>
      ) : null}

      {membership?.requested_role ? (
        <span className={styles.status}>{t('group.ui.role_pending')}</span>
      ) : null}

      {permissions.request_role && !compact ? (
        <>
          {membership?.role !== 'editor' ? (
            <button
              type="button"
              className={styles.btn}
              disabled={isBusy}
              onClick={(event) => run(event, () => requestGroupRole(group.slug, 'editor', { token }))}
            >
              {t('group.ui.request_editor')}
            </button>
          ) : null}
          <button
            type="button"
            className={styles.btn}
            disabled={isBusy}
            onClick={(event) => run(event, () => requestGroupRole(group.slug, 'admin', { token }))}
          >
            {t('group.ui.request_admin')}
          </button>
        </>
      ) : null}

      {permissions.leave && membership?.role === 'follower' ? (
        <button
          type="button"
          className={`${styles.btn} ${styles.btnGhost}`}
          disabled={isBusy}
          onClick={(event) => run(event, () => leaveGroup(group.slug, { token }))}
        >
          {t('group.ui.leave')}
        </button>
      ) : null}

      {error ? <p className={styles.error} role="alert">{error}</p> : null}
    </div>
  )
}
