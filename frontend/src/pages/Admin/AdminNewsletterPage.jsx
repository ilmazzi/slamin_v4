import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { getNewsletterAdmin, sendNewsletterCampaign } from '../../app/api/newsletter.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import RichTextEditor from '../../components/shared/RichTextEditor.jsx'
import styles from './AdminNewsletterPage.module.css'

const EMPTY_DATA = {
  subscriberCount: 0,
  lastSentAt: null,
  campaigns: [],
}

export default function AdminNewsletterPage() {
  const { token } = useAuth()
  const { t, locale } = useI18n()
  const [data, setData] = useState(EMPTY_DATA)
  const [subject, setSubject] = useState('')
  const [content, setContent] = useState('')
  const [isLoading, setIsLoading] = useState(true)
  const [isSending, setIsSending] = useState(false)
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')

  const load = useCallback(async (signal) => {
    setIsLoading(true)
    setError('')
    try {
      setData(await getNewsletterAdmin({ token, signal }))
    } catch (loadError) {
      if (loadError?.name !== 'AbortError') {
        setError(loadError?.message || t('newsletter.admin.load_error'))
      }
    } finally {
      setIsLoading(false)
    }
  }, [t, token])

  useEffect(() => {
    const controller = new AbortController()
    load(controller.signal)
    return () => controller.abort()
  }, [load])

  const hasPendingCampaign = useMemo(
    () => data.campaigns.some((campaign) => ['queued', 'sending'].includes(campaign.status)),
    [data.campaigns],
  )

  const cooldownActive = data.lastSentAt
    ? Date.now() - new Date(data.lastSentAt).getTime() < 7 * 24 * 60 * 60 * 1000
    : false
  const canSend = data.subscriberCount > 0 && !hasPendingCampaign && !cooldownActive

  async function handleSubmit(event) {
    event.preventDefault()
    const confirmation = t('newsletter.admin.confirm_send')
      .replace(':count', String(data.subscriberCount))
    if (!window.confirm(confirmation)) {
      return
    }

    setIsSending(true)
    setError('')
    setMessage('')
    try {
      await sendNewsletterCampaign({ subject, content }, { token })
      setSubject('')
      setContent('')
      setMessage(t('newsletter.admin.queued'))
      await load()
    } catch (sendError) {
      setError(sendError?.message || t('newsletter.admin.send_error'))
    } finally {
      setIsSending(false)
    }
  }

  const formatDate = (value) => value
    ? new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
    : t('newsletter.admin.never_sent')

  return (
    <MainLayout>
      <main className={styles.page}>
        <header className={styles.header}>
          <Link to="/admin">← {t('admin.hub.back')}</Link>
          <h1>{t('newsletter.admin.title')}</h1>
          <p>{t('newsletter.admin.subtitle')}</p>
        </header>

        <section className={styles.metrics}>
          <article>
            <strong>{isLoading ? '…' : data.subscriberCount}</strong>
            <span>{t('newsletter.admin.subscribers')}</span>
          </article>
          <article>
            <strong>{isLoading ? '…' : formatDate(data.lastSentAt)}</strong>
            <span>{t('newsletter.admin.last_sent')}</span>
          </article>
        </section>

        {error ? <p className={styles.error} role="alert">{error}</p> : null}
        {message ? <p className={styles.success} role="status">{message}</p> : null}

        <form className={styles.composer} onSubmit={handleSubmit}>
          <label htmlFor="newsletter-subject">{t('newsletter.admin.subject')}</label>
          <input
            id="newsletter-subject"
            value={subject}
            required
            maxLength="180"
            onChange={(event) => setSubject(event.target.value)}
          />

          <span className={styles.label}>{t('newsletter.admin.content')}</span>
          <RichTextEditor
            value={content}
            onChange={setContent}
            ariaLabel={t('newsletter.admin.content')}
            minHeight="260px"
            compactParagraphs
          />

          {(cooldownActive || hasPendingCampaign) ? (
            <p className={styles.cooldown}>{t('newsletter.admin.cooldown')}</p>
          ) : null}

          <button type="submit" disabled={!canSend || isSending || !subject.trim() || !content.trim()}>
            {t(isSending ? 'newsletter.admin.sending' : 'newsletter.admin.send')}
          </button>
        </form>

        <section className={styles.history}>
          <h2>{t('newsletter.admin.history')}</h2>
          {data.campaigns.length ? (
            <div className={styles.tableWrap}>
              <table>
                <thead>
                  <tr>
                    <th>{t('newsletter.admin.subject')}</th>
                    <th>{t('newsletter.admin.status')}</th>
                    <th>{t('newsletter.admin.recipients_label')}</th>
                    <th>{t('newsletter.admin.date')}</th>
                  </tr>
                </thead>
                <tbody>
                  {data.campaigns.map((campaign) => (
                    <tr key={campaign.id}>
                      <th scope="row">{campaign.subject}</th>
                      <td>{t(`newsletter.admin.status_${campaign.status}`)}</td>
                      <td>{t('newsletter.admin.recipients').replace(':count', campaign.recipient_count)}</td>
                      <td>{formatDate(campaign.sent_at || campaign.created_at)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <p>{t('newsletter.admin.empty_campaigns')}</p>
          )}
        </section>
      </main>
    </MainLayout>
  )
}
