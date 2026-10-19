import { Link } from 'react-router-dom'
import { resolvePublicUrl } from '../../app/api/url.js'
import GroupMembershipActions from './GroupMembershipActions.jsx'
import styles from './GroupCard.module.css'

export default function GroupCard({ group, token, t, onUpdated }) {
  const href = `/groups/${group.slug}`
  const isActiveMember = group.current_user_membership?.status === 'active'
  const showDescription = group.visibility !== 'private' || isActiveMember
  const description = showDescription
    ? (group.description || t('group.ui.no_description'))
    : t('group.ui.private_teaser')
  const imageUrl = group.image_url ? resolvePublicUrl(group.image_url) : ''

  return (
    <article className={styles.card}>
      <header className={styles.head}>
        <h3 className={styles.title}>
          <Link to={href}>{group.name}</Link>
        </h3>
        {group.visibility ? (
          <span className={styles.meta}>{t(`group.visibility.${group.visibility}`)}</span>
        ) : null}
      </header>

      {imageUrl ? (
        <Link to={href} className={styles.coverLink} tabIndex={-1}>
          <img src={imageUrl} alt="" className={styles.cover} />
        </Link>
      ) : null}

      <div className={styles.body}>
        {group.category?.name ? <p className={styles.category}>{group.category.name}</p> : null}
        <p className={styles.description}>{description}</p>
        <div className={styles.footer}>
          <Link to={href} className={styles.more}>
            {t('group.ui.discover')}
            <span aria-hidden="true">›</span>
          </Link>
          <span className={styles.count}>
            {t('group.ui.followers', { count: group.active_members_count ?? 0 })}
          </span>
        </div>
      </div>

      {group.permissions?.follow || group.permissions?.update || group.current_user_membership ? (
        <footer className={styles.extra}>
          {group.permissions?.update ? (
            <Link to={`${href}/edit`} className={styles.edit}>
              {t('group.ui.edit')}
            </Link>
          ) : null}
          <GroupMembershipActions
            group={group}
            token={token}
            t={t}
            compact
            onUpdated={onUpdated}
          />
        </footer>
      ) : null}
    </article>
  )
}
