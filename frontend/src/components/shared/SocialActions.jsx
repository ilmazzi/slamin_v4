import { useCallback, useEffect, useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import {
  mediaCommentsDesktopIcon,
  mediaLikeDesktopIcon,
  mediaReportFeaturedFigmaIcon,
  mediaReportListFigmaIcon,
  mediaShareFeaturedFigmaIcon,
  mediaShareListFigmaIcon,
  mediaViewsDesktopIcon,
} from '../../assets/home/media/index.js'
import { authApiRequest } from '../../app/api/client.js'
import { getApiUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { scheduleScrollToComments } from '../../app/utils/scrollToComments.js'
import { CalendarIcon } from '../icons/ActionIcons.jsx'
import SharePanel from './SharePanel.jsx'
import styles from './SocialActions.module.css'

function pluralizeContentType(contentType) {
  const normalized = String(contentType ?? '').trim().toLowerCase()
  if (!normalized) {
    return 'articles'
  }

  if (normalized.endsWith('y')) {
    return `${normalized.slice(0, -1)}ies`
  }

  if (normalized.endsWith('s')) {
    return normalized
  }

  return `${normalized}s`
}

function buildContentBasePath(contentType, apiBasePath) {
  if (apiBasePath) {
    return String(apiBasePath).replace(/\/+$/, '')
  }

  return `/${pluralizeContentType(contentType)}`
}

function buildContentEventName(contentType, action) {
  const normalizedType = String(contentType ?? 'article').trim().toLowerCase() || 'article'
  return `${normalizedType}:${action}`
}

function resolveContentUrl(target, contentType = 'article', publicBasePath) {
  const slug = target?.slug ?? target?.id
  const basePath = publicBasePath
    ? String(publicBasePath).replace(/\/+$/, '')
    : `/${pluralizeContentType(contentType)}`

  if (!slug) {
    return `${window.location.origin}${basePath}`
  }

  return `${window.location.origin}${basePath}/${slug}`
}

function resolveContentPath(target, contentType = 'article', publicBasePath) {
  const slug = target?.slug ?? target?.id
  const basePath = publicBasePath
    ? String(publicBasePath).replace(/\/+$/, '')
    : `/${pluralizeContentType(contentType)}`

  if (!slug) {
    return null
  }

  return `${basePath}/${encodeURIComponent(slug)}`
}

function resolveMetricValue(value) {
  const numeric = Number(value)
  return Number.isFinite(numeric) ? numeric : 0
}

function canSharePayload(payload) {
  try {
    return typeof navigator.canShare === 'function' && navigator.canShare(payload)
  } catch {
    return false
  }
}

async function shareWithFallback(shareTitle, shareUrl, file = null) {
  if (file && navigator.share) {
    const withFileAndUrl = { title: shareTitle, text: shareTitle, url: shareUrl, files: [file] }
    if (canSharePayload(withFileAndUrl)) {
      await navigator.share(withFileAndUrl)
      return
    }

    if (canSharePayload({ files: [file] })) {
      await navigator.share({ title: shareTitle, files: [file] })
      return
    }
  }

  if (navigator.share) {
    await navigator.share({
      title: shareTitle,
      url: shareUrl,
    })
    return
  }

  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(shareUrl)
  }
}

const SHARE_PAGE_TYPES = {
  poem: { plural: 'poems', key: 'slug', hasCard: true },
  event: { plural: 'events', key: 'slug', hasCard: true },
  article: { plural: 'articles', key: 'slug', hasCard: false },
  media: { plural: 'media', key: 'id', hasCard: false },
}

function resolveSharePageType(contentType) {
  return SHARE_PAGE_TYPES[String(contentType ?? '').toLowerCase()] ?? null
}

function resolveShareUrl(target, contentType, publicBasePath) {
  const shareType = resolveSharePageType(contentType)
  const key = shareType ? target?.[shareType.key] : null

  if (key === null || key === undefined || key === '') {
    return resolveContentUrl(target, contentType, publicBasePath)
  }

  return `${window.location.origin}/share/${shareType.plural}/${encodeURIComponent(String(key))}`
}

async function fetchShareCard(target, contentType) {
  const shareType = resolveSharePageType(contentType)
  if (!shareType?.hasCard || !target?.slug) {
    return null
  }

  const slug = encodeURIComponent(String(target.slug))
  const response = await fetch(getApiUrl(`/api/v1/${shareType.plural}/${slug}/share-card`), {
    headers: { Accept: 'image/png' },
  })
  if (!response.ok) {
    return null
  }

  const blob = await response.blob()
  return blob?.size > 0 ? blob : null
}

function prefersNativeShare() {
  return typeof navigator.share === 'function' && window.matchMedia('(pointer: coarse)').matches
}

async function nativeShare(target, { contentType, publicBasePath }) {
  const shareUrl = resolveShareUrl(target, contentType, publicBasePath)
  const shareTitle = target?.title ?? ''

  let file = null
  try {
    const blob = await fetchShareCard(target, contentType)
    file = blob ? new File([blob], `${target.slug}-share.png`, { type: 'image/png' }) : null
  } catch {
    file = null
  }

  await shareWithFallback(shareTitle, shareUrl, file)
}

function buildReportDetail(target, contentType) {
  const normalizedType = String(contentType ?? 'article').trim().toLowerCase() || 'article'

  return {
    [normalizedType]: target ?? null,
    [`${normalizedType}Id`]: target?.id ?? null,
    [`${normalizedType}Slug`]: target?.slug ?? null,
    contentType: normalizedType,
    target: target ?? null,
  }
}

function defaultReport(target, { contentType }) {
  window.dispatchEvent(
    new CustomEvent(buildContentEventName(contentType, 'report'), {
      detail: buildReportDetail(target, contentType),
    }),
  )
}

export default function SocialActions({
  article,
  item,
  target: explicitTarget,
  contentType = 'article',
  apiBasePath,
  publicBasePath,
  metrics,
  showMetrics = true,
  showActions = true,
  metricsPlacement = 'start',
  layout = 'row',
  shareLabel,
  reportLabel,
  variant = 'featured',
  className = '',
  metricsClassName = '',
  metricClassName = '',
  viewMetricClassName = '',
  likeMetricClassName = '',
  commentMetricClassName = '',
  metricValueClassName = '',
  viewIconClassName = '',
  likeIconClassName = '',
  commentIconClassName = '',
  buttonsClassName = '',
  iconClassName = '',
  shareIconClassName = '',
  reportIconClassName = '',
  shareIconSrc,
  reportIconSrc,
  showBookmark = false,
  bookmarkLabel,
  onBookmark,
  /** Order of view / like / comment metrics, e.g. poem detail: ['likes', 'comments', 'views'] */
  metricsOrder = ['views', 'likes', 'comments'],
  onLike,
  onComments,
  onShare,
  onReport,
}) {
  const auth = useAuth()
  const { t } = useI18n()
  const navigate = useNavigate()
  const location = useLocation()
  const target = explicitTarget ?? item ?? article ?? null
  const normalizedMetrics = metrics ?? target?.metrics ?? null
  const classes = [styles.actions, styles[layout] || '', className].filter(Boolean).join(' ')
  const shareIcon = shareIconSrc || (variant === 'list' ? mediaShareListFigmaIcon : mediaShareFeaturedFigmaIcon)
  const reportIcon = reportIconSrc || (variant === 'list' ? mediaReportListFigmaIcon : mediaReportFeaturedFigmaIcon)
  const resolvedApiBasePath = buildContentBasePath(contentType, apiBasePath)

  const initialIsLiked = Boolean(target?.is_liked)
  const [isLiked, setIsLiked] = useState(initialIsLiked)
  const [likesOverride, setLikesOverride] = useState(null)
  const [isBookmarked, setIsBookmarked] = useState(Boolean(target?.is_bookmarked))
  const [isSharePanelOpen, setIsSharePanelOpen] = useState(false)
  const closeSharePanel = useCallback(() => setIsSharePanelOpen(false), [])
  const canDownloadShareCard = Boolean(resolveSharePageType(contentType)?.hasCard && target?.slug)

  const likesValue = likesOverride !== null
    ? likesOverride
    : resolveMetricValue(normalizedMetrics?.likes)

  useEffect(() => {
    setIsLiked(Boolean(target?.is_liked))
    setLikesOverride(null)
    setIsBookmarked(Boolean(target?.is_bookmarked))
  }, [target?.id, target?.is_liked, target?.is_bookmarked])

  async function handleLike() {
    if (onLike) {
      await onLike(target)
      return
    }

    if (!target?.id) {
      return
    }

    if (!auth?.isAuthenticated || !auth?.token) {
      navigate('/login', { state: { from: `${location.pathname}${location.search}` } })
      return
    }

    try {
      const method = isLiked ? 'DELETE' : 'POST'
      const result = await authApiRequest(
        `/api/v1${resolvedApiBasePath}/${encodeURIComponent(target.id)}/like`,
        {
          token: auth.token,
          method,
          fallbackMessage: 'Unable to toggle like',
        },
      )

      if (!result.ok) {
        throw new Error('Unable to toggle like')
      }

      const nextIsLiked = !isLiked
      setIsLiked(nextIsLiked)
      setLikesOverride(Math.max(0, likesValue + (nextIsLiked ? 1 : -1)))

      window.dispatchEvent(
        new CustomEvent(buildContentEventName(contentType, 'like-changed'), {
          detail: {
            target: target ?? null,
            contentType,
            isLiked: nextIsLiked,
          },
        }),
      )
    } catch {
      window.dispatchEvent(
        new CustomEvent(buildContentEventName(contentType, 'like-error'), {
          detail: {
            target: target ?? null,
            contentType,
          },
        }),
      )
    }
  }

  async function handleBookmark() {
    if (onBookmark) {
      await onBookmark(target)
      return
    }

    if (!target?.id) {
      return
    }

    if (!auth?.isAuthenticated || !auth?.token) {
      navigate('/login', { state: { from: `${location.pathname}${location.search}` } })
      return
    }

    try {
      const method = isBookmarked ? 'DELETE' : 'POST'
      const result = await authApiRequest(
        `/api/v1${resolvedApiBasePath}/${encodeURIComponent(target.id)}/bookmark`,
        {
          token: auth.token,
          method,
          fallbackMessage: 'Unable to toggle bookmark',
        },
      )

      if (!result.ok) {
        throw new Error('Unable to toggle bookmark')
      }

      const nextIsBookmarked = !isBookmarked
      setIsBookmarked(nextIsBookmarked)

      window.dispatchEvent(
        new CustomEvent(buildContentEventName(contentType, 'bookmark-changed'), {
          detail: {
            target: target ?? null,
            contentType,
            isBookmarked: nextIsBookmarked,
          },
        }),
      )
    } catch {
      window.dispatchEvent(
        new CustomEvent(buildContentEventName(contentType, 'bookmark-error'), {
          detail: {
            target: target ?? null,
            contentType,
          },
        }),
      )
    }
  }

  function handleComments() {
    if (onComments) {
      onComments(target)
      return
    }

    window.dispatchEvent(
      new CustomEvent(buildContentEventName(contentType, 'comments'), {
        detail: {
          target: target ?? null,
          contentType,
        },
      }),
    )

    const targetPath = resolveContentPath(target, contentType, publicBasePath)
    if (!targetPath) {
      return
    }

    if (location.pathname === targetPath) {
      scheduleScrollToComments({ behavior: 'smooth' })
      if (location.hash !== '#comments') {
        window.history.replaceState(null, '', `${location.pathname}${location.search}#comments`)
      }
      return
    }

    const nextUrl = `${targetPath}#comments`
    const currentUrl = `${location.pathname}${location.hash || ''}`
    if (nextUrl !== currentUrl) {
      navigate(nextUrl, { state: { focusComments: true } })
    }
  }

  async function handleShare() {
    try {
      if (onShare) {
        await onShare(target)
        window.dispatchEvent(
          new CustomEvent(buildContentEventName(contentType, 'share-success'), {
            detail: {
              target: target ?? null,
              contentType,
              mode: 'custom',
            },
          }),
        )
        return
      }

      if (!prefersNativeShare()) {
        setIsSharePanelOpen(true)
        return
      }

      await nativeShare(target, { contentType, publicBasePath })
      window.dispatchEvent(
        new CustomEvent(buildContentEventName(contentType, 'share-success'), {
          detail: {
            target: target ?? null,
            contentType,
            mode: navigator.share ? 'native' : 'clipboard',
          },
        }),
      )
    } catch {
      window.dispatchEvent(
        new CustomEvent(buildContentEventName(contentType, 'share-error'), {
          detail: {
            target: target ?? null,
            contentType,
          },
        }),
      )
    }
  }

  function handleReport() {
    if (onReport) {
      onReport(target)
      return
    }

    defaultReport(target, { contentType })
  }

  const allowedMetricKeys = ['views', 'likes', 'comments']
  const resolvedMetricsOrder = (Array.isArray(metricsOrder) ? metricsOrder : ['views', 'likes', 'comments']).filter(
    (key) => allowedMetricKeys.includes(key),
  )
  const metricOrderFinal = resolvedMetricsOrder.length ? resolvedMetricsOrder : ['views', 'likes', 'comments']

  const viewMetricEl = (
    <div
      key="views"
      className={[styles.metric, metricClassName, viewMetricClassName].filter(Boolean).join(' ')}
    >
      <span className={[styles.metricValue, metricValueClassName].filter(Boolean).join(' ')}>
        {resolveMetricValue(normalizedMetrics?.views)}
      </span>
      <img
        src={mediaViewsDesktopIcon}
        alt=""
        className={[styles.metricIcon, viewIconClassName].filter(Boolean).join(' ')}
        aria-hidden="true"
      />
    </div>
  )

  const likeMetricEl = (
    <button
      key="likes"
      type="button"
      className={[styles.metricButton, styles.metric, metricClassName, likeMetricClassName, isLiked ? styles.isLiked : '']
        .filter(Boolean)
        .join(' ')}
      aria-label={t('common.like')}
      onClick={handleLike}
    >
      <span className={[styles.metricValue, metricValueClassName].filter(Boolean).join(' ')}>{likesValue}</span>
      <img
        src={mediaLikeDesktopIcon}
        alt=""
        className={[styles.metricIcon, likeIconClassName].filter(Boolean).join(' ')}
        aria-hidden="true"
      />
    </button>
  )

  const commentMetricEl = (
    <button
      key="comments"
      type="button"
      className={[styles.metricButton, styles.metric, metricClassName, commentMetricClassName].filter(Boolean).join(' ')}
      aria-label={t('common.comments')}
      onClick={handleComments}
    >
      <span className={[styles.metricValue, metricValueClassName].filter(Boolean).join(' ')}>
        {resolveMetricValue(normalizedMetrics?.comments)}
      </span>
      <img
        src={mediaCommentsDesktopIcon}
        alt=""
        className={[styles.metricIcon, commentIconClassName].filter(Boolean).join(' ')}
        aria-hidden="true"
      />
    </button>
  )

  const metricByKey = {
    views: viewMetricEl,
    likes: likeMetricEl,
    comments: commentMetricEl,
  }

  const metricsBlock = showMetrics && normalizedMetrics ? (
    <div className={[styles.metrics, metricsClassName].filter(Boolean).join(' ')}>
      {metricOrderFinal.map((key) => metricByKey[key]).filter(Boolean)}
    </div>
  ) : null

  const buttonsBlock = showActions ? (
    <div className={[styles.buttons, buttonsClassName].filter(Boolean).join(' ')}>
      {showBookmark ? (
        <button
          type="button"
          aria-label={bookmarkLabel}
          aria-pressed={isBookmarked}
          onClick={handleBookmark}
          className={[styles.actionButton, isBookmarked ? styles.isBookmarked : ''].filter(Boolean).join(' ')}
        >
          <CalendarIcon className={[styles.icon, styles.bookmarkIcon].filter(Boolean).join(' ')} />
        </button>
      ) : null}
      <button type="button" aria-label={shareLabel} onClick={handleShare} className={styles.actionButton}>
        <img
          src={shareIcon}
          alt=""
          className={[styles.icon, iconClassName, shareIconClassName].filter(Boolean).join(' ')}
        />
      </button>
      <button type="button" aria-label={reportLabel} onClick={handleReport} className={styles.actionButton}>
        <img
          src={reportIcon}
          alt=""
          className={[styles.icon, iconClassName, reportIconClassName].filter(Boolean).join(' ')}
        />
      </button>
    </div>
  ) : null

  return (
    <div className={classes}>
      {metricsPlacement === 'end' ? buttonsBlock : metricsBlock}
      {metricsPlacement === 'end' ? metricsBlock : buttonsBlock}
      {isSharePanelOpen ? (
        <SharePanel
          isOpen
          title={target?.title ?? ''}
          url={resolveShareUrl(target, contentType, publicBasePath)}
          imageName={`${target?.slug ?? 'slamin'}-share.png`}
          loadImage={canDownloadShareCard ? () => fetchShareCard(target, contentType) : null}
          onClose={closeSharePanel}
        />
      ) : null}
    </div>
  )
}
