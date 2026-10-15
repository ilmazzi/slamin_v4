import { Link } from 'react-router-dom'
import styles from './GigCard.module.css'
import { formatGigDate, gigReferenceDate, gigTitle } from './gigFormat.js'

function Feature({ active, label, stateLabel }) {
  return (
    <li className={[styles.feature, active ? '' : styles.featureOff].join(' ')}>
      <span className={[styles.dot, active ? styles.dotActive : ''].join(' ')} aria-hidden="true" />
      <span>{label}</span>
      <span className={styles.srOnly}>{stateLabel}</span>
    </li>
  )
}

export default function GigCard({ gig, locale, t, children }) {
  const href = `/gigs/${gig.id}`
  const date = formatGigDate(gigReferenceDate(gig), locale)
  const applicationStatus = gig.my_application?.status

  return (
    <article className={styles.card}>
      <header className={styles.head}>
        <h3 className={styles.title}>
          <Link to={href}>{gigTitle(gig, t)}</Link>
        </h3>
        {date ? <time className={styles.date}>{date}</time> : null}
      </header>

      <div className={styles.body}>
        <div className={styles.main}>
          <p className={styles.position}>{t(`gigs.types.${gig.type}`)}</p>
          {applicationStatus ? (
            <span className={styles.badge}>{t(`gigs.application_statuses.${applicationStatus}`)}</span>
          ) : null}
          <p className={styles.description}>{gig.description || t('gigs.card.no_description')}</p>
          <div className={styles.footer}>
            <Link to={href} className={styles.more}>
              {t('gigs.card.discover')}
              <span aria-hidden="true">›</span>
            </Link>
            {gig.parent?.city ? <span className={styles.city}>{gig.parent.city}</span> : null}
          </div>
        </div>

        <ul className={styles.features}>
          {[
            ['cachet', gig.cachet_enabled],
            ['travel', gig.travel_expenses_enabled],
            ['accommodation', gig.accommodation_enabled],
          ].map(([key, active]) => (
            <Feature
              key={key}
              active={Boolean(active)}
              label={t(`gigs.card.${key}`)}
              stateLabel={t(active ? 'gigs.card.included' : 'gigs.card.not_included')}
            />
          ))}
          <li className={styles.feature}>
            <span className={styles.quantity}>{gig.quantity}</span>
            <span>{t('gigs.card.people')}</span>
          </li>
        </ul>
      </div>

      {children ? <footer className={styles.extra}>{children}</footer> : null}
    </article>
  )
}
