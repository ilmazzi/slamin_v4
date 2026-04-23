import { useEffect, useMemo, useState } from 'react'
import {
  mediaCommentsDesktopIcon,
  mediaCommentsMobileIcon,
  mediaCornerDesktop,
  mediaLikeDesktopIcon,
  mediaLikeMobileIcon,
  mediaPlayDesktopIcon,
  mediaPlayMobileIcon,
  mediaPreviewDesktop,
  mediaPreviewMobile,
  mediaReportDesktopIcon,
  mediaReportMobileIcon,
  mediaShareDesktopIcon,
  mediaShareMobileIcon,
  mediaViewsDesktopIcon,
  mediaViewsMobileIcon,
} from '../../assets/home/media/index.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import styles from './MediaCard.module.css'

function StatPill({ value, desktopIcon, mobileIcon, alt }) {
  return (
    <span className={styles.statPill}>
      <span className={styles.statValue}>{value}</span>
      <img src={desktopIcon} alt={alt} className={styles.desktopStatIcon} />
      <img src={mobileIcon} alt={alt} className={styles.mobileStatIcon} />
    </span>
  )
}

export default function MediaCard({
  title,
  subtitle,
  tone,
  type,
  href,
  mediaUrl,
  coverUrl,
  views,
  likes,
  comments,
  playAria,
  openAria,
}) {
  const normalizedCoverUrl = useMemo(() => {
    if (!coverUrl) return null
    return resolvePublicUrl(coverUrl)
  }, [coverUrl])

  const normalizedMediaUrl = useMemo(() => {
    if (!mediaUrl) return null
    return resolvePublicUrl(mediaUrl)
  }, [mediaUrl])

  const [desktopPreviewSrc, setDesktopPreviewSrc] = useState(normalizedCoverUrl || mediaPreviewDesktop)
  const [mobilePreviewSrc, setMobilePreviewSrc] = useState(normalizedCoverUrl || mediaPreviewMobile)

  useEffect(() => {
    setDesktopPreviewSrc(normalizedCoverUrl || mediaPreviewDesktop)
    setMobilePreviewSrc(normalizedCoverUrl || mediaPreviewMobile)
  }, [normalizedCoverUrl, title, subtitle])

  const canPlay = type === 'video' && Boolean(normalizedMediaUrl)

  return (
    <article className={[styles.card, styles[tone] || ''].join(' ')} data-media-card>
      <div className={styles.preview}>
        <div className={styles.mobileHeader}>
          {canPlay ? (
            <a
              href={normalizedMediaUrl}
              target="_blank"
              rel="noreferrer"
              className={styles.playButton}
              aria-label={playAria}
            >
              <img src={mediaPlayMobileIcon} alt="" className={styles.playIcon} aria-hidden="true" />
            </a>
          ) : null}
          <div className={styles.copy}>
            <h3 className={styles.title}>{title}</h3>
            <p className={styles.subtitle}>{subtitle}</p>
          </div>
        </div>

        <div className={styles.previewImage} aria-hidden="true">
          <img
            src={desktopPreviewSrc}
            alt=""
            className={styles.desktopPreviewAsset}
            onError={() => {
              if (desktopPreviewSrc !== mediaPreviewDesktop) {
                setDesktopPreviewSrc(mediaPreviewDesktop)
              }
            }}
          />
          <img
            src={mobilePreviewSrc}
            alt=""
            className={styles.mobilePreviewAsset}
            onError={() => {
              if (mobilePreviewSrc !== mediaPreviewMobile) {
                setMobilePreviewSrc(mediaPreviewMobile)
              }
            }}
          />
          {canPlay ? (
            <a
              href={normalizedMediaUrl}
              target="_blank"
              rel="noreferrer"
              className={styles.desktopPlayButton}
              aria-label={playAria}
            >
              <img src={mediaPlayDesktopIcon} alt="" className={styles.desktopPlayAsset} aria-hidden="true" />
            </a>
          ) : null}
        </div>
      </div>

      <div className={styles.desktopCopy}>
        <h3 className={styles.title}>{title}</h3>
        <p className={styles.subtitle}>{subtitle}</p>
      </div>

      <div className={styles.desktopFooter}>
        <div className={styles.desktopInteractionStrip}>
          <div className={styles.desktopFooterIcons}>
            <StatPill
              value={views}
              desktopIcon={mediaViewsDesktopIcon}
              mobileIcon={mediaViewsMobileIcon}
              alt=""
            />
            <StatPill
              value={likes}
              desktopIcon={mediaLikeDesktopIcon}
              mobileIcon={mediaLikeMobileIcon}
              alt=""
            />
            <StatPill
              value={comments}
              desktopIcon={mediaCommentsDesktopIcon}
              mobileIcon={mediaCommentsMobileIcon}
              alt=""
            />
          </div>

          <div className={styles.desktopFooterActions}>
            <a href={href || normalizedMediaUrl || '#'} className={styles.iconButton} aria-label={openAria}>
              <img
                src={mediaShareDesktopIcon}
                alt=""
                className={styles.desktopActionIcon}
                aria-hidden="true"
              />
            </a>
            <button type="button" className={styles.iconButton} aria-label={openAria} disabled>
              <img
                src={mediaReportDesktopIcon}
                alt=""
                className={styles.desktopActionIcon}
                aria-hidden="true"
              />
            </button>
          </div>
        </div>

        <img
          src={mediaCornerDesktop}
          alt=""
          className={styles.desktopCorner}
          aria-hidden="true"
        />
      </div>

      <div className={styles.mobileFooter}>
        <div className={styles.mobileFooterIcons}>
          <StatPill
            value={views}
            desktopIcon={mediaViewsDesktopIcon}
            mobileIcon={mediaViewsMobileIcon}
            alt=""
          />
          <StatPill
            value={likes}
            desktopIcon={mediaLikeDesktopIcon}
            mobileIcon={mediaLikeMobileIcon}
            alt=""
          />
          <StatPill
            value={comments}
            desktopIcon={mediaCommentsDesktopIcon}
            mobileIcon={mediaCommentsMobileIcon}
            alt=""
          />
        </div>

        <div className={styles.mobileFooterActions}>
          <a href={href || normalizedMediaUrl || '#'} className={styles.iconButton} aria-label={openAria}>
            <img
              src={mediaShareMobileIcon}
              alt=""
              className={styles.mobileActionIcon}
              aria-hidden="true"
            />
          </a>
          <button type="button" className={styles.iconButton} aria-label={openAria} disabled>
            <img
              src={mediaReportMobileIcon}
              alt=""
              className={styles.mobileActionIcon}
              aria-hidden="true"
            />
          </button>
        </div>
      </div>
    </article>
  )
}
