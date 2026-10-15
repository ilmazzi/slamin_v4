import { Link } from 'react-router-dom'
import { resolvePublicUrl } from '../../app/api/url.js'
import styles from './GigConfirmedPeople.module.css'

function initials(name) {
  return String(name)
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
}

export default function GigConfirmedPeople({ users = [], t }) {
  if (users.length === 0) {
    return null
  }

  return (
    <ul className={styles.people}>
      {users.map((user) => {
        const name = user.display_name || user.nickname || t('gigs.confirmed.unknown')
        const avatar = resolvePublicUrl(user.avatar_url)
        const content = (
          <>
            {avatar ? (
              <img src={avatar} alt="" className={styles.avatar} loading="lazy" />
            ) : (
              <span className={styles.avatar} aria-hidden="true">{initials(name)}</span>
            )}
            <span className={styles.name}>{name}</span>
          </>
        )

        return (
          <li key={user.id}>
            {user.nickname ? (
              <Link
                to={`/u/${encodeURIComponent(user.nickname)}`}
                className={styles.person}
                aria-label={t('gigs.confirmed.view_profile', { name })}
              >
                {content}
              </Link>
            ) : (
              <span className={styles.person}>{content}</span>
            )}
          </li>
        )
      })}
    </ul>
  )
}
