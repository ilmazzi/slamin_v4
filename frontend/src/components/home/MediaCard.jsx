import { Link } from 'react-router-dom'
import { useMemo } from 'react'
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
import SocialActions from '../shared/SocialActions.jsx'
import styles from './MediaCard.module.css'

const isInternalHref = (href) => typeof href === 'string' && href.startsWith('/') && !href.startsWith('//')

function MediaTarget({ href, className, ariaLabel, children }) {
  if (isInternalHref(href)) {
    return (
      <Link to={href} className={className} aria-label={ariaLabel}>
        {children}
      </Link>
    )
  }

  return (
    <a href={href} target="_blank" rel="noreferrer" className={className} aria-label={ariaLabel}>
      {children}
    </a>
  )
}

function StatPill({ value, desktopIcon, mobileIcon, alt }) {
  return (
    <span className={styles.statPill}>
      <span className={styles.statValue}>{value}</span>
      <img src={desktopIcon} alt={alt} className={styles.desktopStatIcon} />
      <img src={mobileIcon} alt={alt} className={styles.mobileStatIcon} />
    </span>
  )
}

function MediaSocialFooter({
  item,
  views,
  likes,
  comments,
  shareLabel,
  reportLabel,
  shareIconSrc,
  reportIconSrc,
  className,
  metricsClassName,
  metricClassName,
  metricValueClassName,
  buttonsClassName,
  viewIconClassName,
  likeIconClassName,
  commentIconClassName,
}) {
  return (
    <SocialActions
      target={item}
      contentType="media"
      apiBasePath="/media"
      publicBasePath="/media"
      variant="list"
      metrics={{ views, likes, comments }}
      shareLabel={shareLabel}
      reportLabel={reportLabel}
      shareIconSrc={shareIconSrc}
      reportIconSrc={reportIconSrc}
      className={className}
      metricsClassName={metricsClassName}
      metricClassName={metricClassName}
      metricValueClassName={metricValueClassName}
      buttonsClassName={buttonsClassName}
      viewIconClassName={viewIconClassName}
      likeIconClassName={likeIconClassName}
      commentIconClassName={commentIconClassName}
    />
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
  shareLabel,
  reportLabel,
  item = null,
  fluid = false,
}) {
  const normalizedCoverUrl = useMemo(() => {
    if (!coverUrl) return null
    return resolvePublicUrl(coverUrl)
  }, [coverUrl])

  const normalizedMediaUrl = useMemo(() => {
    if (!mediaUrl) return null
    return resolvePublicUrl(mediaUrl)
  }, [mediaUrl])

  const openHref = href || normalizedMediaUrl || '#'
  const playHref = isInternalHref(href) ? href : normalizedMediaUrl
  const canPlay = type === 'video' && Boolean(playHref)
  const hasSocial = Boolean(item?.id)

  return (
    <article className={[styles.card, fluid ? styles.fluid : '', styles[tone] || ''].filter(Boolean).join(' ')} data-media-card>
      <div className={styles.preview}>
        <div className={styles.mobileHeader}>
          {canPlay ? (
            <MediaTarget href={playHref} className={styles.playButton} ariaLabel={playAria}>
              <img src={mediaPlayMobileIcon} alt="" className={styles.playIcon} aria-hidden="true" />
            </MediaTarget>
          ) : null}
          <div className={styles.copy}>
            <h3 className={styles.title}>
              {isInternalHref(openHref) ? <Link to={openHref}>{title}</Link> : title}
            </h3>
            <p className={styles.subtitle}>{subtitle}</p>
          </div>
        </div>

        <div className={styles.previewImage}>
          {isInternalHref(openHref) ? (
            <Link to={openHref} className={styles.coverLink} aria-label={openAria}>
              <img
                src={normalizedCoverUrl || mediaPreviewDesktop}
                alt=""
                className={styles.desktopPreviewAsset}
                onError={(event) => {
                  event.currentTarget.src = mediaPreviewDesktop
                }}
              />
              <img
                src={normalizedCoverUrl || mediaPreviewMobile}
                alt=""
                className={styles.mobilePreviewAsset}
                onError={(event) => {
                  event.currentTarget.src = mediaPreviewMobile
                }}
              />
            </Link>
          ) : (
            <>
              <img
                src={normalizedCoverUrl || mediaPreviewDesktop}
                alt=""
                className={styles.desktopPreviewAsset}
                onError={(event) => {
                  event.currentTarget.src = mediaPreviewDesktop
                }}
              />
              <img
                src={normalizedCoverUrl || mediaPreviewMobile}
                alt=""
                className={styles.mobilePreviewAsset}
                onError={(event) => {
                  event.currentTarget.src = mediaPreviewMobile
                }}
              />
            </>
          )}
          {canPlay ? (
            <MediaTarget href={playHref} className={styles.desktopPlayButton} ariaLabel={playAria}>
              <img src={mediaPlayDesktopIcon} alt="" className={styles.desktopPlayAsset} aria-hidden="true" />
            </MediaTarget>
          ) : null}
        </div>
      </div>

      <div className={styles.desktopCopy}>
        <h3 className={styles.title}>
          {isInternalHref(openHref) ? <Link to={openHref}>{title}</Link> : title}
        </h3>
        <p className={styles.subtitle}>{subtitle}</p>
      </div>

      <div className={styles.desktopFooter}>
        {hasSocial ? (
          <MediaSocialFooter
            item={item}
            views={views}
            likes={likes}
            comments={comments}
            shareLabel={shareLabel}
            reportLabel={reportLabel}
            shareIconSrc={mediaShareDesktopIcon}
            reportIconSrc={mediaReportDesktopIcon}
            className={styles.desktopSocial}
            metricsClassName={styles.desktopFooterIcons}
            metricClassName={styles.statPill}
            metricValueClassName={styles.statValue}
            buttonsClassName={styles.desktopFooterActions}
            viewIconClassName={styles.desktopStatIcon}
            likeIconClassName={styles.desktopStatIcon}
            commentIconClassName={styles.desktopStatIcon}
          />
        ) : (
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
              {isInternalHref(openHref) ? (
                <Link to={openHref} className={styles.iconButton} aria-label={openAria}>
                  <img
                    src={mediaShareDesktopIcon}
                    alt=""
                    className={styles.desktopActionIcon}
                    aria-hidden="true"
                  />
                </Link>
              ) : (
                <a href={openHref} className={styles.iconButton} aria-label={openAria}>
                  <img
                    src={mediaShareDesktopIcon}
                    alt=""
                    className={styles.desktopActionIcon}
                    aria-hidden="true"
                  />
                </a>
              )}
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
        )}

        <img
          src={mediaCornerDesktop}
          alt=""
          className={styles.desktopCorner}
          aria-hidden="true"
        />
      </div>

      <div className={styles.mobileFooter}>
        {hasSocial ? (
          <MediaSocialFooter
            item={item}
            views={views}
            likes={likes}
            comments={comments}
            shareLabel={shareLabel}
            reportLabel={reportLabel}
            shareIconSrc={mediaShareMobileIcon}
            reportIconSrc={mediaReportMobileIcon}
            className={styles.mobileSocial}
            metricsClassName={styles.mobileFooterIcons}
            metricClassName={styles.statPill}
            metricValueClassName={styles.statValue}
            buttonsClassName={styles.mobileFooterActions}
            viewIconClassName={styles.mobileStatIcon}
            likeIconClassName={styles.mobileStatIcon}
            commentIconClassName={styles.mobileStatIcon}
          />
        ) : (
          <>
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
              {isInternalHref(openHref) ? (
                <Link to={openHref} className={styles.iconButton} aria-label={openAria}>
                  <img
                    src={mediaShareMobileIcon}
                    alt=""
                    className={styles.mobileActionIcon}
                    aria-hidden="true"
                  />
                </Link>
              ) : (
                <a href={openHref} className={styles.iconButton} aria-label={openAria}>
                  <img
                    src={mediaShareMobileIcon}
                    alt=""
                    className={styles.mobileActionIcon}
                    aria-hidden="true"
                  />
                </a>
              )}
              <button type="button" className={styles.iconButton} aria-label={openAria} disabled>
                <img
                  src={mediaReportMobileIcon}
                  alt=""
                  className={styles.mobileActionIcon}
                  aria-hidden="true"
                />
              </button>
            </div>
          </>
        )}
      </div>
    </article>
  )
}
