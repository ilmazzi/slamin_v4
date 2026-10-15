import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import GigCard from './GigCard.jsx'
import styles from './EventGigsHighlight.module.css'
import { listOpenGigs } from '../../app/api/gigs.js'

const MAX_GIGS = 6

export default function EventGigsHighlight({ eventId, token, t, locale }) {
  const [result, setResult] = useState({ eventId: null, items: [], total: 0 })

  useEffect(() => {
    if (!eventId || !token) {
      return undefined
    }
    const controller = new AbortController()
    listOpenGigs({ eventId, perPage: MAX_GIGS }, { token, signal: controller.signal })
      .then(({ items, meta }) => setResult({ eventId, items, total: Number(meta?.total ?? items.length) }))
      .catch(() => {
        if (!controller.signal.aborted) {
          setResult({ eventId, items: [], total: 0 })
        }
      })
    return () => controller.abort()
  }, [eventId, token])

  const gigs = result.eventId === eventId ? result.items : []

  if (!token || gigs.length === 0) {
    return null
  }

  return (
    <section className={styles.highlight} aria-labelledby="event-gigs-title">
      <div className={styles.head}>
        <div>
          <h2 id="event-gigs-title" className={styles.title}>
            {t(result.total === 1 ? 'gigs.event_highlight.title_one' : 'gigs.event_highlight.title_other', { count: result.total })}
          </h2>
          <p className={styles.lead}>{t('gigs.event_highlight.lead')}</p>
        </div>
        {result.total > gigs.length ? (
          <Link to="/gigs" className={styles.allLink}>{t('gigs.event_highlight.all')}</Link>
        ) : null}
      </div>
      <div className={styles.grid}>
        {gigs.map((gig) => (
          <GigCard key={gig.id} gig={gig} locale={locale} t={t} />
        ))}
      </div>
    </section>
  )
}
