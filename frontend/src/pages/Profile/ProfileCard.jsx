import { Link } from 'react-router-dom'
import SocialIcon from '../../components/icons/SocialIcon.jsx'
import styles from './ProfilePage.module.css'

export default function ProfileCard({ profile, t, onWrite = null, isWriting = false, follow = null }) {
  if (!profile) {
    return null
  }

  const facts = [
    follow ? { id: 'followers', label: t('profile.page.followers'), value: follow.countLabel } : null,
    profile.location ? { id: 'location', label: t('profile.page.location'), value: profile.location } : null,
    profile.website_url
      ? { id: 'website', label: t('profile.page.website'), value: profile.website_url, href: profile.website_url }
      : null,
    profile.email ? { id: 'email', label: t('profile.page.email'), value: profile.email, href: `mailto:${profile.email}` } : null,
    Array.isArray(profile.spoken_languages) && profile.spoken_languages.length
      ? {
          id: 'spoken-languages',
          label: t('profile.page.spoken_languages'),
          value: profile.spoken_languages.join(', '),
        }
      : null,
  ].filter(Boolean)
  const socialLinks = Array.isArray(profile.social_links) ? profile.social_links : []

  if (!profile.is_owner && !profile.bio && facts.length === 0 && socialLinks.length === 0 && !onWrite) {
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

      {socialLinks.length > 0 ? (
        <div className={styles.socialLinks} aria-label={t('profile.page.social_channels')}>
          {socialLinks.map((link, index) => (
            <a
              key={`${link.platform}-${link.url}-${index}`}
              className={styles.socialLink}
              href={link.url}
              rel="noreferrer noopener"
              target="_blank"
            >
              <SocialIcon className={styles.socialIcon} platform={link.platform} />
              <span>{link.label}</span>
            </a>
          ))}
        </div>
      ) : null}

      {profile.is_owner ? (
        <div className={styles.actions}>
          <Link className={styles.primaryLink} to="/profile/edit">
            {t('profile.page.edit')}
          </Link>
        </div>
      ) : onWrite || follow?.onToggle ? (
        <div className={styles.actions}>
          {follow?.onToggle ? (
            <button
              type="button"
              className={follow.isFollowing ? styles.unfollowButton : styles.primaryLink}
              onClick={follow.onToggle}
              disabled={follow.isBusy}
              aria-pressed={follow.isFollowing}
            >
              {follow.isFollowing ? t('profile.page.unfollow') : t('profile.page.follow')}
            </button>
          ) : null}
          {onWrite ? (
            <button type="button" className={styles.primaryLink} onClick={onWrite} disabled={isWriting}>
              {isWriting ? t('common.loading') : t('profile.page.write')}
            </button>
          ) : null}
        </div>
      ) : null}
    </article>
  )
}
