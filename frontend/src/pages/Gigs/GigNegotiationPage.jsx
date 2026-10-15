import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import { formatGigDeadline, formatGigDateTime, formatGigMoney, gigTitle } from '../../components/gigs/gigFormat.js'
import styles from './GigWorkspace.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { addGigNegotiationEvent, agreeGigNegotiation, getGigNegotiation } from '../../app/api/gigs.js'

const CURRENCIES = ['EUR', 'USD', 'GBP', 'CHF']
const OFFER_TYPES = ['owner_offer', 'translator_offer', 'counter_offer']

const personName = (person, fallbackId) => person?.display_name || person?.nickname || `#${fallbackId}`

export default function GigNegotiationPage() {
  const { negotiationId } = useParams()
  const { t, locale } = useI18n()
  const { token, user } = useAuth()
  const [negotiation, setNegotiation] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [formError, setFormError] = useState('')
  const [isBusy, setIsBusy] = useState(false)
  const [toast, setToast] = useState('')
  const [form, setForm] = useState({ event_type: 'counter_offer', price: '', currency: 'EUR', deadline: '', note: '' })
  const clearToast = useCallback(() => setToast(''), [])

  const load = useCallback((signal) => getGigNegotiation(negotiationId, { token, signal })
    .then((data) => {
      setNegotiation(data)
      if (data?.currency) {
        setForm((prev) => ({ ...prev, currency: data.currency }))
      }
    }), [negotiationId, token])

  useEffect(() => {
    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    load(controller.signal)
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
  }, [load])

  if (isLoading || !negotiation) {
    return (
      <MainLayout themeTone="gigs">
        <div className={styles.page}>
          <Link to="/gigs/mine" className={styles.backLink}>{t('gigs.filters.mine')}</Link>
          <p className={styles.state} role={error ? 'alert' : undefined}>
            {isLoading ? t('gigs.common.loading') : (error || t('gigs.detail.not_found'))}
          </p>
        </div>
      </MainLayout>
    )
  }

  const viewerId = Number(user?.id)
  const isOwner = viewerId === Number(negotiation.owner_user_id)
  const isOpen = negotiation.status === 'open'
  const ownOfferType = isOwner ? 'owner_offer' : 'translator_offer'
  const allowedTypes = [ownOfferType, 'counter_offer', 'note']
  const events = negotiation.events ?? []
  const hasOffer = events.some((item) => OFFER_TYPES.includes(item.event_type))
  const isNote = form.event_type === 'note'
  const job = negotiation.translation_job

  const setField = (event) => {
    const { name, value } = event.target
    setForm((prev) => ({ ...prev, [name]: value }))
  }

  async function handleSend(event) {
    event.preventDefault()
    setIsBusy(true)
    setFormError('')
    try {
      const payload = {
        event_type: form.event_type,
        note: form.note.trim() || null,
        ...(isNote ? {} : {
          price: form.price === '' ? null : form.price,
          currency: form.currency,
          deadline: form.deadline || null,
        }),
      }
      const created = await addGigNegotiationEvent(negotiation.id, payload, { token })
      if (created) {
        setNegotiation((prev) => ({
          ...prev,
          events: [...(prev.events ?? []), created],
          events_count: (prev.events_count ?? 0) + 1,
        }))
      }
      setForm((prev) => ({ ...prev, price: '', deadline: '', note: '' }))
      setToast(t('gigs.negotiation.sent'))
    } catch (err) {
      setFormError(err.message)
    } finally {
      setIsBusy(false)
    }
  }

  async function handleAgree() {
    if (!window.confirm(t('gigs.negotiation.agree_confirm'))) {
      return
    }
    setIsBusy(true)
    setFormError('')
    try {
      await agreeGigNegotiation(negotiation.id, { token })
      await load()
      setToast(t('gigs.negotiation.agreed'))
    } catch (err) {
      setFormError(err.message)
    } finally {
      setIsBusy(false)
    }
  }

  return (
    <MainLayout themeTone="gigs">
      <div className={styles.page}>
        <Link to={`/gigs/${negotiation.gig_id}`} className={styles.backLink}>{t('gigs.negotiation.back')}</Link>

        <div className={styles.layout}>
          <section className={styles.panel}>
            <header className={styles.panelHead}>
              <h1 className={styles.panelTitle}>
                {t('gigs.negotiation.title')}: {negotiation.gig ? gigTitle(negotiation.gig, t) : `#${negotiation.gig_id}`}
              </h1>
              <span className={[styles.badge, styles.badgeAccent].join(' ')}>
                {t(`gigs.negotiation_statuses.${negotiation.status}`)}
              </span>
            </header>
            <div className={styles.panelBody}>
              <p className={styles.muted}>
                {t('gigs.negotiation.between', {
                  owner: personName(negotiation.owner, negotiation.owner_user_id),
                  translator: personName(negotiation.translator, negotiation.translator_user_id),
                })}
              </p>

              {negotiation.status === 'agreed' ? (
                <p className={styles.text}>
                  {t('gigs.negotiation.agreed_terms', {
                    price: formatGigMoney(negotiation.agreed_price, negotiation.currency, locale) || t('gigs.common.not_set'),
                    deadline: formatGigDeadline(negotiation.agreed_deadline, locale) || t('gigs.common.not_set'),
                  })}
                </p>
              ) : null}

              {job ? (
                <div className={styles.actions}>
                  <Link className={[styles.btn, styles.btnDark].join(' ')} to={`/translation-jobs/${job.id}`}>
                    {t('gigs.negotiation.open_job')}
                  </Link>
                </div>
              ) : null}

              <h2 className={styles.sectionTitle}>{t('gigs.negotiation.timeline')}</h2>
              {events.length === 0 ? <p className={styles.muted}>{t('gigs.negotiation.timeline_empty')}</p> : (
                <ol className={styles.list}>
                  {events.map((item) => {
                    const price = formatGigMoney(item.price, item.currency, locale)
                    const deadline = formatGigDeadline(item.deadline, locale)
                    return (
                      <li key={item.id} className={styles.listItem}>
                        <div className={styles.listItemHead}>
                          <strong>{t(`gigs.negotiation_events.${item.event_type}`)}</strong>
                          <span className={styles.muted}>
                            {personName(item.author, item.author_user_id)} · {formatGigDateTime(item.created_at, locale)}
                          </span>
                        </div>
                        {price || deadline ? (
                          <p className={styles.text}>
                            {[price, deadline ? `${t('gigs.negotiation.deadline')}: ${deadline}` : ''].filter(Boolean).join(' · ')}
                          </p>
                        ) : null}
                        {item.note ? <p className={styles.text}>{item.note}</p> : null}
                      </li>
                    )
                  })}
                </ol>
              )}
            </div>
          </section>

          {isOpen ? (
            <section className={styles.panel}>
              <header className={styles.panelHead}>
                <h2 className={styles.panelTitle}>{t('gigs.negotiation.new_event')}</h2>
              </header>
              <form className={[styles.panelBody, styles.form].join(' ')} onSubmit={handleSend}>
                {formError ? <p className={styles.error} role="alert">{formError}</p> : null}
                <label className={styles.field}>
                  {t('gigs.negotiation.event_type')}
                  <select className={styles.select} name="event_type" value={form.event_type} onChange={setField}>
                    {allowedTypes.map((value) => (
                      <option key={value} value={value}>{t(`gigs.negotiation_events.${value}`)}</option>
                    ))}
                  </select>
                </label>
                {!isNote ? (
                  <div className={styles.fieldGrid}>
                    <label className={styles.field}>
                      {t('gigs.negotiation.price')}
                      <input className={styles.input} type="number" min="0" step="0.01" name="price" value={form.price} onChange={setField} required />
                    </label>
                    <label className={styles.field}>
                      {t('gigs.negotiation.currency')}
                      <select className={styles.select} name="currency" value={form.currency} onChange={setField}>
                        {CURRENCIES.map((value) => <option key={value} value={value}>{value}</option>)}
                      </select>
                    </label>
                    <label className={styles.field}>
                      {t('gigs.negotiation.deadline')}
                      <input className={styles.input} type="date" name="deadline" value={form.deadline} onChange={setField} />
                    </label>
                  </div>
                ) : null}
                <label className={styles.field}>
                  {t('gigs.negotiation.note')}
                  <textarea className={styles.textarea} name="note" value={form.note} onChange={setField} required={isNote} maxLength={5000} />
                </label>
                <div className={styles.actions}>
                  <button type="submit" className={[styles.btn, styles.btnDark].join(' ')} disabled={isBusy}>
                    {t('gigs.negotiation.send')}
                  </button>
                  {hasOffer ? (
                    <button type="button" className={[styles.btn, styles.btnAccent].join(' ')} disabled={isBusy} onClick={handleAgree}>
                      {t('gigs.negotiation.agree')}
                    </button>
                  ) : null}
                </div>
              </form>
            </section>
          ) : null}
        </div>
      </div>

      <AppToast message={toast} isVisible={Boolean(toast)} onClose={clearToast} />
    </MainLayout>
  )
}
