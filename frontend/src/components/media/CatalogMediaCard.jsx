import { Link } from 'react-router-dom'
import { mediaPlayDesktopIcon } from '../../assets/home/media/index.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import SocialActions from '../shared/SocialActions.jsx'
import styles from './CatalogMediaCard.module.css'

function coverFor(item) {
  return item?.peertube_thumbnail_url || item?.file_url || ''
}

export default function CatalogMediaCard({ item, t }) {
  if (!item?.id) {
    return null
  }

  const title = item.title || t('media.ui.untitled')
  const author = item?.author?.display_name || item?.author?.nickname || t('common.unknown_user')
  const href = `/media/${item.id}`
  const coverUrl = resolvePublicUrl(coverFor(item))
  const isVideo = item.type === 'video'

  return (
    <article className={styles.card}>
      <Link to={href} className={styles.coverWrap} aria-label={isVideo ? t('media.ui.play') : t('media.ui.open')}>
        {coverUrl ? (
          <img src={coverUrl} alt="" className={styles.cover} />
        ) : (
          <span className={styles.coverPlaceholder} aria-hidden="true" />
        )}
        {isVideo ? (
          <span className={styles.playButton} aria-hidden="true">
            <img src={mediaPlayDesktopIcon} alt="" className={styles.playIcon} />
          </span>
        ) : null}
      </Link>

      <div className={styles.body}>
        <h3 className={styles.title}>
          <Link to={href}>{title}</Link>
        </h3>
        <p className={styles.author}>{author}</p>
        <SocialActions
          target={item}
          contentType="media"
          apiBasePath="/media"
          publicBasePath="/media"
          variant="list"
          metrics={{
            views: item?.views_count ?? 0,
            likes: item?.likes_count ?? 0,
            comments: item?.comments_count ?? 0,
          }}
          shareLabel={t('media.ui.share')}
          reportLabel={t('media.ui.report')}
          className={styles.social}
        />
      </div>
    </article>
  )
}
