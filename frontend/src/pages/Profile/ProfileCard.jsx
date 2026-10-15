import { Link } from 'react-router-dom'
import styles from './ProfilePage.module.css'

export default function ProfileCard({ profile, t, onWrite = null, isWriting = false }) {
  if (!profile) {
    return null
  }

  const facts = [
    profile.location ? { id: 'location', label: t('profile.page.location'), value: profile.location } : null,
    profile.website_url
      ? { id: 'website', label: t('profile.page.website'), value: profile.website_url, href: profile.website_url }
      : null,
    profile.email ? { id: 'email', label: t('profile.page.email'), value: profile.email, href: `mailto:${profile.email}` } : null,
  ].filter(Boolean)

  if (!profile.is_owner && !profile.bio && facts.length === 0 && !onWrite) {
    return null
  }

  return (
    <article className={styles.card}>
      {profile.is_owner && profile.is_public === false ? (
        <p className={styles.privateHint}>{t('profile.page.private')}</p>
      ) : null}

      {profile.bio || profile.is_owner ? (
        <p className={styles.bio}>{profile.bio || t('profile.page.empty_bio')}</p>
      ) : null}

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

      {profile.is_owner ? (
        <div className={styles.actions}>
          <Link className={styles.primaryLink} to="/profile/edit">
            {t('profile.page.edit')}
          </Link>
        </div>
      ) : onWrite ? (
        <div className={styles.actions}>
          <button type="button" className={styles.primaryLink} onClick={onWrite} disabled={isWriting}>
            {isWriting ? t('common.loading') : t('profile.page.write')}
          </button>
        </div>
      ) : null}
    </article>
  )
}
