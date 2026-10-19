import { Link } from 'react-router-dom'
import { resolvePublicUrl } from '../../app/api/url.js'
import styles from './EventGroupsHighlight.module.css'

const LEAD_KEYS = {
  event: 'group.ui.event_highlight_lead',
  poem: 'group.ui.poem_highlight_lead',
  article: 'group.ui.article_highlight_lead',
}

export default function EventGroupsHighlight({ groups, t, contentType = 'event', variant = 'banner' }) {
  const items = (groups || []).filter((group) => group?.name)

  if (items.length === 0) {
    return null
  }

  return (
    <section
      className={`${styles.highlight} ${variant === 'inset' ? styles.highlightInset : ''}`}
      aria-labelledby="content-groups-title"
    >
      <div className={styles.head}>
        <div>
          <h2 id="content-groups-title" className={styles.title}>
            {t(
              items.length === 1
                ? 'group.ui.event_highlight_title_one'
                : 'group.ui.event_highlight_title_other',
              { count: items.length },
            )}
          </h2>
          <p className={styles.lead}>{t(LEAD_KEYS[contentType] || LEAD_KEYS.event)}</p>
        </div>
      </div>
      <div className={styles.grid}>
        {items.map((group) => {
          const href = group.slug ? `/groups/${group.slug}` : null
          const imageUrl = group.image_url ? resolvePublicUrl(group.image_url) : ''
          return (
            <article key={group.id || group.slug} className={styles.card}>
              {imageUrl ? (
                href ? (
                  <Link to={href} className={styles.coverLink} tabIndex={-1}>
                    <img src={imageUrl} alt="" className={styles.cover} />
                  </Link>
                ) : (
                  <img src={imageUrl} alt="" className={styles.cover} />
                )
              ) : null}
              <div className={styles.body}>
                {group.category?.name ? (
                  <p className={styles.category}>{group.category.name}</p>
                ) : null}
                <h3 className={styles.name}>
                  {href ? <Link to={href}>{group.name}</Link> : group.name}
                </h3>
                {href ? (
                  <Link to={href} className={styles.open}>
                    {t('group.ui.event_highlight_open')}
                    <span aria-hidden="true">›</span>
                  </Link>
                ) : null}
              </div>
            </article>
          )
        })}
      </div>
    </section>
  )
}
