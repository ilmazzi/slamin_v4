import { Link } from 'react-router-dom'
import { resolvePublicUrl } from '../../app/api/url.js'
import styles from './ProfilePage.module.css'

function initialsFromProfile(profile) {
  const source = String(profile?.display_name || profile?.nickname || '').trim()
  if (!source) {
    return '?'
  }

  const parts = source.split(/\s+/).filter(Boolean)
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase()
  }

  return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase()
}

export default function ProfileCard({ profile, t, showOwnerActions = false }) {
  if (!profile) {
    return null
  }

  const displayName = profile.display_name || profile.nickname || ''
  const avatarUrl = resolvePublicUrl(profile.avatar_url)
  const facts = [
    profile.location ? { id: 'location', label: t('profile.page.location'), value: profile.location } : null,
    profile.website_url
      ? { id: 'website', label: t('profile.page.website'), value: profile.website_url, href: profile.website_url }
      : null,
    profile.email ? { id: 'email', label: t('profile.page.email'), value: profile.email, href: `mailto:${profile.email}` } : null,
  ].filter(Boolean)

  return (
    <article className={styles.card}>
      <div className={styles.identity}>
        {avatarUrl ? (
          <img className={styles.avatar} src={avatarUrl} alt="" />
        ) : (
          <span className={styles.avatarFallback} aria-hidden="true">
            {initialsFromProfile(profile)}
          </span>
        )}
        <div className={styles.identityText}>
          <h1 className={styles.name}>{displayName}</h1>
          {profile.nickname ? <p className={styles.nickname}>@{profile.nickname}</p> : null}
          {showOwnerActions && profile.is_public === false ? (
            <p className={styles.privateHint}>{t('profile.page.private')}</p>
          ) : null}
        </div>
      </div>

      <p className={styles.bio}>{profile.bio || t('profile.page.empty_bio')}</p>

      {facts.length > 0 ? (
        <dl className={styles.facts}>
          {facts.map((fact) => (
            <div key={fact.id} className={styles.fact}>
              <dt>{fact.label}</dt>
              <dd>
                {fact.href ? (
                  <a href={fact.href} rel="noreferrer noopener" target={fact.id === 'website' ? '_blank' : undefined}>
                    {fact.value}
                  </a>
                ) : (
                  fact.value
                )}
              </dd>
            </div>
          ))}
        </dl>
      ) : null}

      {showOwnerActions ? (
        <div className={styles.actions}>
          <Link className={styles.primaryLink} to="/profile/edit">
            {t('profile.page.edit')}
          </Link>
          {profile.nickname && profile.is_public !== false ? (
            <Link className={styles.secondaryLink} to={`/u/${encodeURIComponent(profile.nickname)}`}>
              {t('profile.page.view_public')}
            </Link>
          ) : null}
        </div>
      ) : profile.is_owner ? (
        <div className={styles.actions}>
          <Link className={styles.primaryLink} to="/profile/edit">
            {t('profile.page.edit')}
          </Link>
        </div>
      ) : null}
    </article>
  )
}
