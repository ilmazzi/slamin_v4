import { useState } from 'react'
import { Link } from 'react-router-dom'
import styles from '../../pages/Gigs/GigWorkspace.module.css'
import { applyToGig, withdrawGigApplication } from '../../app/api/gigs.js'
import { useAuth } from '../../app/hooks/useAuth.js'

export default function GigApplyPanel({ gig, token, t, onGigChange, onNotify }) {
  const { user } = useAuth()
  const [message, setMessage] = useState('')
  const [isBusy, setIsBusy] = useState(false)
  const [error, setError] = useState('')
  const application = gig.my_application ?? null
  const canWithdraw = application
    && !['withdrawn', 'rejected'].includes(application.status)
    && gig.status !== 'closed'
    && gig.status !== 'cancelled'

  async function handleApply(event) {
    event.preventDefault()
    setIsBusy(true)
    setError('')
    try {
      const created = await applyToGig(gig.id, message.trim(), { token })
      if (created) {
        onGigChange({ my_application: { id: created.id, status: created.status } })
      }
      setMessage('')
      onNotify(t('gigs.apply.sent'))
    } catch (err) {
      setError(err.message)
    } finally {
      setIsBusy(false)
    }
  }

  async function handleWithdraw() {
    if (!window.confirm(t('gigs.apply.withdraw_confirm'))) {
      return
    }
    setIsBusy(true)
    setError('')
    try {
      const updated = await withdrawGigApplication(gig.id, application.id, { token })
      onGigChange({
        my_application: { id: application.id, status: updated?.status ?? 'withdrawn' },
        confirmed_users: (gig.confirmed_users ?? []).filter((person) => Number(person.id) !== Number(user?.id)),
      })
      onNotify(t('gigs.apply.withdrawn'))
    } catch (err) {
      setError(err.message)
    } finally {
      setIsBusy(false)
    }
  }

  return (
    <section className={styles.panel}>
      <header className={styles.panelHead}>
        <h2 className={styles.panelTitle}>{t('gigs.apply.title')}</h2>
      </header>
      <div className={styles.panelBody}>
        {error ? <p className={styles.error} role="alert">{error}</p> : null}

        {application ? (
          <>
            <p className={styles.text}>
              {t('gigs.apply.your_status', { status: t(`gigs.application_statuses.${application.status}`) })}
            </p>
            <div className={styles.actions}>
              <Link to="/gigs/mine" className={[styles.btn, styles.btnAccent].join(' ')}>
                {t('gigs.apply.follow_up')}
              </Link>
              {canWithdraw ? (
                <button type="button" className={[styles.btn, styles.btnDanger].join(' ')} disabled={isBusy} onClick={handleWithdraw}>
                  {t('gigs.apply.withdraw')}
                </button>
              ) : null}
            </div>
          </>
        ) : gig.status === 'open' ? (
          <form className={styles.form} onSubmit={handleApply}>
            <label className={styles.field}>
              {t('gigs.apply.message_label')}
              <textarea
                className={styles.textarea}
                value={message}
                onChange={(event) => setMessage(event.target.value)}
                placeholder={t('gigs.apply.message_placeholder')}
                maxLength={5000}
              />
            </label>
            <div className={styles.actions}>
              <button type="submit" className={[styles.btn, styles.btnDark].join(' ')} disabled={isBusy}>
                {isBusy ? t('gigs.apply.sending') : t('gigs.apply.submit')}
              </button>
            </div>
          </form>
        ) : (
          <p className={styles.muted}>{t('gigs.apply.closed')}</p>
        )}
      </div>
    </section>
  )
}
