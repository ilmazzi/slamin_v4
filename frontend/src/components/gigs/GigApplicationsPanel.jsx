import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import styles from '../../pages/Gigs/GigWorkspace.module.css'
import { formatGigDate } from './gigFormat.js'
import { listGigApplications, openGigNegotiation, updateGigApplicationStatus } from '../../app/api/gigs.js'

function ApplicantAvatar({ user }) {
  return (
    <span
      className={styles.avatar}
      style={user?.avatar_url ? { backgroundImage: `url("${user.avatar_url}")` } : undefined}
      aria-hidden="true"
    />
  )
}

export default function GigApplicationsPanel({ gig, token, t, locale, onGigChange, onNotify }) {
  const navigate = useNavigate()
  const [applications, setApplications] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [busyId, setBusyId] = useState(null)
  const isTranslation = gig.type === 'translator' && gig.parent?.type === 'poem'
  const isActive = gig.status === 'open' || gig.status === 'filled'

  useEffect(() => {
    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    listGigApplications(gig.id, { token, signal: controller.signal })
      .then((items) => setApplications(items))
      .catch((err) => {
        if (err?.name !== 'AbortError') {
          setError(err.message)
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      })
    return () => controller.abort()
  }, [gig.id, token])

  async function changeStatus(application, status) {
    setBusyId(application.id)
    setError('')
    try {
      const result = await updateGigApplicationStatus(gig.id, application.id, status, { token })
      if (result.application) {
        setApplications((prev) => prev.map((item) => (
          item.id === result.application.id ? { ...item, ...result.application, user: item.user } : item
        )))
      }
      if (result.gig) {
        onGigChange(result.gig)
      }
      onNotify(t('gigs.manage.updated'))
    } catch (err) {
      setError(err.message)
    } finally {
      setBusyId(null)
    }
  }

  async function startNegotiation(application) {
    setBusyId(application.id)
    setError('')
    try {
      const negotiation = await openGigNegotiation(gig.id, application.id, {}, { token })
      if (negotiation?.id) {
        navigate(`/gig-negotiations/${negotiation.id}`)
        return
      }
    } catch (err) {
      setError(err.message)
    }
    setBusyId(null)
  }

  return (
    <section className={styles.panel}>
      <header className={styles.panelHead}>
        <h2 className={styles.panelTitle}>{t('gigs.manage.applications')}</h2>
        {!isLoading && !error ? (
          <span className={styles.panelMeta}>
            {t('gigs.manage.applications_count', {
              count: applications.length,
              pending: applications.filter((item) => item.status === 'pending').length,
            })}
          </span>
        ) : null}
      </header>
      <div className={styles.panelBody}>
        {error ? <p className={styles.error} role="alert">{error}</p> : null}
        {isLoading ? <p className={styles.muted}>{t('gigs.common.loading')}</p> : null}
        {!isLoading && applications.length === 0 ? <p className={styles.muted}>{t('gigs.manage.applications_empty')}</p> : null}

        {applications.length > 0 ? (
          <ul className={styles.list}>
            {applications.map((application) => {
              const busy = busyId === application.id
              const negotiation = application.negotiation
              const canReview = isActive && ['pending', 'accepted'].includes(application.status)
              return (
                <li key={application.id} className={styles.listItem}>
                  <div className={styles.listItemHead}>
                    <span className={styles.person}>
                      <ApplicantAvatar user={application.user} />
                      {application.user?.display_name || application.user?.nickname || `#${application.user_id}`}
                    </span>
                    <span className={styles.badge}>{t(`gigs.application_statuses.${application.status}`)}</span>
                  </div>
                  <span className={styles.muted}>
                    {t('gigs.manage.applied_on', { date: formatGigDate(application.created_at, locale) })}
                  </span>
                  {application.message ? <p className={styles.text}>{application.message}</p> : null}

                  <div className={styles.actions}>
                    {isTranslation ? (
                      negotiation ? (
                        <>
                          <Link className={[styles.btn, styles.btnAccent].join(' ')} to={`/gig-negotiations/${negotiation.id}`}>
                            {t('gigs.manage.view_negotiation')}
                          </Link>
                          {negotiation.translation_job ? (
                            <Link className={styles.btn} to={`/translation-jobs/${negotiation.translation_job.id}`}>
                              {t('gigs.manage.view_job')}
                            </Link>
                          ) : null}
                        </>
                      ) : canReview ? (
                        <>
                          <button type="button" className={[styles.btn, styles.btnDark].join(' ')} disabled={busy} onClick={() => startNegotiation(application)}>
                            {t('gigs.manage.open_negotiation')}
                          </button>
                          <button type="button" className={[styles.btn, styles.btnDanger].join(' ')} disabled={busy} onClick={() => changeStatus(application, 'rejected')}>
                            {t('gigs.manage.reject')}
                          </button>
                        </>
                      ) : null
                    ) : canReview ? (
                      <>
                        {application.status === 'pending' ? (
                          <button type="button" className={styles.btn} disabled={busy} onClick={() => changeStatus(application, 'accepted')}>
                            {t('gigs.manage.accept')}
                          </button>
                        ) : null}
                        <button
                          type="button"
                          className={[styles.btn, styles.btnDark].join(' ')}
                          disabled={busy || gig.filled_quantity >= gig.quantity}
                          onClick={() => changeStatus(application, 'confirmed')}
                        >
                          {t('gigs.manage.confirm')}
                        </button>
                        <button type="button" className={[styles.btn, styles.btnDanger].join(' ')} disabled={busy} onClick={() => changeStatus(application, 'rejected')}>
                          {t('gigs.manage.reject')}
                        </button>
                      </>
                    ) : null}
                  </div>
                </li>
              )
            })}
          </ul>
        ) : null}
      </div>
    </section>
  )
}
