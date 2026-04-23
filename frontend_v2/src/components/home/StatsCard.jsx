import styles from './StatsCard.module.css'

const iconSizeById = {
  views: {
    desktop: { width: 151, height: 107 },
    mobile: { width: 62.093, height: 44 },
  },
  poets: {
    desktop: { width: 169, height: 85 },
    mobile: { width: 87, height: 44 },
  },
  events: {
    desktop: { width: 97, height: 107 },
    mobile: { width: 39, height: 44 },
  },
  videos: {
    desktop: { width: 118, height: 107 },
    mobile: { width: 48, height: 44 },
  },
}

export default function StatsCard({ id, value, label, desktopIconUrl, mobileIconUrl }) {
  const iconSize = iconSizeById[id] || iconSizeById.views

  return (
    <article className={styles.card}>
      <div className={styles.copy}>
        <p className={styles.value}>{value}</p>
        <p className={styles.label}>{label}</p>
      </div>
      <div className={styles.icon} aria-hidden="true">
        <img
          src={desktopIconUrl}
          alt=""
          className={`${styles.iconAsset} ${styles.iconAssetDesktop}`}
          style={{
            '--stats-icon-width-desktop': `${iconSize.desktop.width}px`,
            '--stats-icon-height-desktop': `${iconSize.desktop.height}px`,
          }}
        />
        <img
          src={mobileIconUrl || desktopIconUrl}
          alt=""
          className={`${styles.iconAsset} ${styles.iconAssetMobile}`}
          style={{
            '--stats-icon-width-mobile': `${iconSize.mobile.width}px`,
            '--stats-icon-height-mobile': `${iconSize.mobile.height}px`,
          }}
        />
      </div>
    </article>
  )
}
