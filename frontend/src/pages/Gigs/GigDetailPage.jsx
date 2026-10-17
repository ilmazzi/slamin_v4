import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import GigAnnouncementsPanel from '../../components/gigs/GigAnnouncementsPanel.jsx'
import GigApplicationsPanel from '../../components/gigs/GigApplicationsPanel.jsx'
import GigApplyPanel from '../../components/gigs/GigApplyPanel.jsx'
import GigConfirmedPeople from '../../components/gigs/GigConfirmedPeople.jsx'
import { formatGigDate, formatGigDeadline, formatGigDateTime, formatGigMoney, gigTitle } from '../../components/gigs/gigFormat.js'
import SocialActions from '../../components/shared/SocialActions.jsx'
import styles from './GigWorkspace.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { closeGig, getGig } from '../../app/api/gigs.js'

function Fact({ label, value }) {
  if (!value && value !== 0) {
    return null
  }
  return (
    <div className={styles.fact}>
      <dt>{label}</dt>
      <dd>{value}</dd>
    </div>
  )
}

function offerLines(gig, t, locale) {
  const lines = []
  if (gig.cachet_enabled || gig.type === 'translator') {
    const amount = formatGigMoney(gig.proposed_price, gig.currency, locale)
    lines.push(amount ? t('gigs.detail.cachet_amount', { amount }) : t('gigs.detail.cachet_to_agree'))
  }
  if (gig.travel_expenses_enabled) {
    const amount = formatGigMoney(gig.travel_expenses_max, gig.currency, locale)
    lines.push(amount ? t('gigs.detail.travel_max', { amount }) : t('gigs.detail.travel_yes'))
  }
  if (gig.accommodation_enabled) {
    lines.push(gig.accommodation_details
      ? `${t('gigs.detail.accommodation_yes')}: ${gig.accommodation_details}`
      : t('gigs.detail.accommodation_yes'))
  }
  return lines
}

export default function GigDetailPage() {
  const { gigId } = useParams()
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const [gig, setGig] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [actionError, setActionError] = useState('')
  const [isClosing, setIsClosing] = useState(false)
  const [toast, setToast] = useState('')
  const clearToast = useCallback(() => setToast(''), [])

  useEffect(() => {
    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    getGig(gigId, { token, signal: controller.signal })
      .then((data) => setGig(data))
      .catch((err) => {
        if (err?.name !== 'AbortError') {
          setGig(null)
          setError(err.message)
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      })
    return () => controller.abort()
  }, [gigId, token])

  const mergeGig = useCallback((next) => {
    setGig((prev) => (prev ? { ...prev, ...next, parent: prev.parent, can_manage: prev.can_manage } : next))
  }, [])

  async function handleClose() {
    if (!window.confirm(t('gigs.manage.close_confirm'))) {
      return
    }
    setIsClosing(true)
    setActionError('')
    try {
      const updated = await closeGig(gig.id, { token })
      if (updated) {
        mergeGig(updated)
      }
      setToast(t('gigs.manage.closed'))
    } catch (err) {
      setActionError(err.message)
    } finally {
      setIsClosing(false)
    }
  }

  if (isLoading || !gig) {
    return (
      <MainLayout themeTone="gigs">
        <div className={styles.page}>
          <Link to="/gigs" className={styles.backLink}>{t('gigs.common.back_to_list')}</Link>
          <p className={styles.state} role={error ? 'alert' : undefined}>
            {isLoading ? t('gigs.common.loading') : (error || t('gigs.detail.not_found'))}
          </p>
        </div>
      </MainLayout>
    )
  }

  const parent = gig.parent
  const isTranslation = gig.type === 'translator' && parent?.type === 'poem'
  const offers = offerLines(gig, t, locale)
  const place = [parent?.place_name, parent?.city].filter(Boolean).join(', ')
  const organizer = parent?.owner?.display_name || parent?.owner?.nickname
  const editHref = `/gigs/${gig.id}/edit`

  return (
    <MainLayout themeTone="gigs">
      <div className={styles.page}>
        <Link to="/gigs" className={styles.backLink}>{t('gigs.common.back_to_list')}</Link>

        <div className={styles.layout}>
          <div className={styles.stack}>
            <article className={styles.panel}>
              <header className={styles.panelHead}>
                <h1 className={styles.panelTitle}>{gigTitle(gig, t)}</h1>
                {parent?.starts_at ? <time className={styles.panelMeta}>{formatGigDate(parent.starts_at, locale)}</time> : null}
              </header>
              <div className={styles.panelBody}>
                {gig.share_url ? (
                  <SocialActions
                    target={{ ...gig, title: gigTitle(gig, t) }}
                    contentType="gig"
                    apiBasePath="/gigs"
                    publicBasePath="/gigs"
                    showMetrics={false}
                    showBookmark={false}
                    showReport={false}
                    shareLabel={t('gigs.common.share')}
                  />
                ) : null}
                <div className={styles.listItemHead}>
                  <h2 className={[styles.sectionTitle, styles.grow].join(' ')}>{t(`gigs.types.${gig.type}`)}</h2>
                  <span className={[styles.badge, styles.badgeAccent].join(' ')}>{t(`gigs.statuses.${gig.status}`)}</span>
                </div>

                {gig.description ? <p className={styles.text}>{gig.description}</p> : null}

                <dl className={styles.facts}>
                  <Fact label={parent?.type === 'poem' ? t('gigs.detail.poem') : t('gigs.detail.event')} value={parent?.title} />
                  <Fact label={t('gigs.detail.date')} value={formatGigDateTime(parent?.starts_at, locale)} />
                  <Fact label={t('gigs.detail.place')} value={place} />
                  <Fact label={t('gigs.detail.deadline')} value={formatGigDeadline(gig.deadline, locale)} />
                  {isTranslation ? (
                    <>
                      <Fact label={t('gigs.detail.source_language')} value={parent?.language} />
                      <Fact label={t('gigs.detail.target_language')} value={gig.language} />
                    </>
                  ) : (
                    <Fact label={t('gigs.detail.language')} value={gig.language} />
                  )}
                  <Fact
                    label={t('gigs.detail.people')}
                    value={t('gigs.detail.filled', { filled: gig.filled_quantity ?? 0, quantity: gig.quantity })}
                  />
                  <Fact label={t('gigs.detail.organizer')} value={organizer} />
                </dl>

                <h3 className={styles.sectionTitle}>{t('gigs.confirmed.title')}</h3>
                {gig.confirmed_users?.length > 0 ? (
                  <GigConfirmedPeople users={gig.confirmed_users} t={t} />
                ) : (
                  <p className={styles.muted}>{t('gigs.confirmed.empty')}</p>
                )}

                <h3 className={styles.sectionTitle}>{t('gigs.detail.offer')}</h3>
                {offers.length > 0 ? (
                  <ul className={styles.offerList}>
                    {offers.map((line) => <li key={line}>{line}</li>)}
                  </ul>
                ) : (
                  <p className={styles.muted}>{t('gigs.detail.nothing_offered')}</p>
                )}

                {gig.special_requirements ? (
                  <>
                    <h3 className={styles.sectionTitle}>{t('gigs.detail.requirements')}</h3>
                    <p className={styles.text}>{gig.special_requirements}</p>
                  </>
                ) : null}

                {parent?.url ? (
                  <div className={styles.actions}>
                    <Link to={parent.url} className={[styles.btn, styles.btnAccent].join(' ')}>
                      {parent.type === 'poem' ? t('gigs.detail.open_parent_poem') : t('gigs.detail.open_parent_event')}
                    </Link>
                  </div>
                ) : null}
              </div>
            </article>

            <GigAnnouncementsPanel
              gigId={gig.id}
              canManage={Boolean(gig.can_manage)}
              token={token}
              t={t}
              locale={locale}
              onNotify={setToast}
            />
          </div>

          <div className={styles.stack}>
            {gig.can_manage ? (
              <>
                <section className={styles.panel}>
                  <header className={styles.panelHead}>
                    <h2 className={styles.panelTitle}>{t('gigs.manage.title')}</h2>
                  </header>
                  <div className={styles.panelBody}>
                    {actionError ? <p className={styles.error} role="alert">{actionError}</p> : null}
                    <div className={styles.actions}>
                      <Link to={editHref} className={[styles.btn, styles.btnDark].join(' ')}>{t('gigs.common.edit')}</Link>
                      {gig.status !== 'closed' && gig.status !== 'cancelled' ? (
                        <button
                          type="button"
                          className={styles.btn}
                          disabled={isClosing || (gig.filled_quantity ?? 0) < gig.quantity}
                          onClick={handleClose}
                        >
                          {t('gigs.manage.close')}
                        </button>
                      ) : null}
                    </div>
                  </div>
                </section>

                <GigApplicationsPanel
                  gig={gig}
                  token={token}
                  t={t}
                  locale={locale}
                  onGigChange={mergeGig}
                  onNotify={setToast}
                />
              </>
            ) : (
              <GigApplyPanel gig={gig} token={token} t={t} onGigChange={mergeGig} onNotify={setToast} />
            )}
          </div>
        </div>
      </div>

      <AppToast message={toast} isVisible={Boolean(toast)} onClose={clearToast} />
    </MainLayout>
  )
}
