import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import GigConfirmedPeople from './GigConfirmedPeople.jsx'
import styles from './EventConfirmedPeople.module.css'
import { listEventConfirmedGigs } from '../../app/api/gigs.js'

export default function EventConfirmedPeople({ eventId, token, t }) {
  const [result, setResult] = useState({ eventId: null, gigs: [] })

  useEffect(() => {
    if (!eventId) {
      return undefined
    }
    const controller = new AbortController()
    listEventConfirmedGigs(eventId, { token, signal: controller.signal })
      .then((gigs) => setResult({ eventId, gigs }))
      .catch(() => {
        if (!controller.signal.aborted) {
          setResult({ eventId, gigs: [] })
        }
      })
    return () => controller.abort()
  }, [eventId, token])

  const gigs = result.eventId === eventId ? result.gigs : []

  if (gigs.length === 0) {
    return null
  }

  return (
    <section className={styles.block} aria-labelledby="event-confirmed-title">
      <div>
        <h2 id="event-confirmed-title" className={styles.title}>{t('gigs.confirmed.event_title')}</h2>
        <p className={styles.lead}>{t('gigs.confirmed.event_lead')}</p>
      </div>
      <div className={styles.groups}>
        {gigs.map((gig) => (
          <div key={gig.id} className={styles.group}>
            <div className={styles.groupHead}>
              <Link to={`/gigs/${gig.id}`} className={styles.groupTitle}>{t(`gigs.types.${gig.type}`)}</Link>
              <span className={styles.count}>
                {t('gigs.detail.filled', { filled: gig.filled_quantity ?? 0, quantity: gig.quantity })}
              </span>
            </div>
            <GigConfirmedPeople users={gig.confirmed_users ?? []} t={t} />
          </div>
        ))}
      </div>
    </section>
  )
}
