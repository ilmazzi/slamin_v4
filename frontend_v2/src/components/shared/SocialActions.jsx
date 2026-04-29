import { useEffect, useState } from 'react'
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
import { useAuth } from '../../app/hooks/useAuth.js'
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

function resolveMetricValue(value) {
  const numeric = Number(value)
  return Number.isFinite(numeric) ? numeric : 0
}

async function defaultShare(target, { contentType, publicBasePath }) {
  const shareUrl = resolveContentUrl(target, contentType, publicBasePath)
  const shareTitle = target?.title ?? ''

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
  /** Order of view / like / comment metrics, e.g. poem detail: ['likes', 'comments', 'views'] */
  metricsOrder = ['views', 'likes', 'comments'],
  onLike,
  onComments,
  onShare,
  onReport,
}) {
  const auth = useAuth()
  const target = explicitTarget ?? item ?? article ?? null
  const normalizedMetrics = metrics ?? target?.metrics ?? null
  const classes = [styles.actions, styles[layout] || '', className].filter(Boolean).join(' ')
  const shareIcon = variant === 'list' ? mediaShareListFigmaIcon : mediaShareFeaturedFigmaIcon
  const reportIcon = variant === 'list' ? mediaReportListFigmaIcon : mediaReportFeaturedFigmaIcon
  const resolvedApiBasePath = buildContentBasePath(contentType, apiBasePath)

  const initialIsLiked = Boolean(target?.is_liked)
  const [isLiked, setIsLiked] = useState(initialIsLiked)
  const [likesOverride, setLikesOverride] = useState(null)

  const likesValue = likesOverride !== null
    ? likesOverride
    : resolveMetricValue(normalizedMetrics?.likes)

  useEffect(() => {
    setIsLiked(Boolean(target?.is_liked))
    setLikesOverride(null)
  }, [target?.id, target?.is_liked])

  async function handleLike() {
    if (onLike) {
      await onLike(target)
      return
    }

    if (!target?.id) {
      return
    }

    if (!auth?.isAuthenticated || !auth?.token) {
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

      await defaultShare(target, { contentType, publicBasePath })
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
      aria-label="Like"
      onClick={handleLike}
      disabled={!onLike && (!auth?.isAuthenticated || !auth?.token)}
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
      aria-label="Commenti"
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
    </div>
  )
}
