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
import { useAuth } from '../../context/AuthContext.jsx'
import styles from './SocialActions.module.css'

function resolveArticleUrl(article) {
  const slug = article?.slug ?? article?.id
  if (!slug) {
    return `${window.location.origin}/articles`
  }

  return `${window.location.origin}/articles/${slug}`
}

function resolveMetricValue(value) {
  const numeric = Number(value)
  return Number.isFinite(numeric) ? numeric : 0
}

async function defaultShare(article) {
  const shareUrl = resolveArticleUrl(article)
  const shareTitle = article?.title ?? ''

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

function defaultReport(article) {
  window.dispatchEvent(
    new CustomEvent('article:report', {
      detail: {
        article: article ?? null,
        articleId: article?.id ?? null,
        articleSlug: article?.slug ?? null,
      },
    }),
  )
}

export default function SocialActions({
  article,
  item,
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
  onLike,
  onComments,
  onShare,
  onReport,
}) {
  const auth = useAuth()
  const target = item ?? article ?? null
  const normalizedMetrics = metrics ?? target?.metrics ?? null
  const classes = [styles.actions, styles[layout] || '', className].filter(Boolean).join(' ')
  const shareIcon = variant === 'list' ? mediaShareListFigmaIcon : mediaShareFeaturedFigmaIcon
  const reportIcon = variant === 'list' ? mediaReportListFigmaIcon : mediaReportFeaturedFigmaIcon

  const initialIsLiked = Boolean(target?.is_liked)
  const [isLiked, setIsLiked] = useState(initialIsLiked)
  const [likesOverride, setLikesOverride] = useState(null)

  const likesValue = likesOverride !== null
    ? likesOverride
    : resolveMetricValue(normalizedMetrics?.likes)

  // Keep local like state in sync when the item changes (navigation / refetch).
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
      const result = await authApiRequest(`/api/v1/articles/${encodeURIComponent(target.id)}/like`, {
        token: auth.token,
        method,
        fallbackMessage: 'Unable to toggle like',
      })

      if (!result.ok) {
        throw new Error('Unable to toggle like')
      }

      const nextIsLiked = !isLiked
      setIsLiked(nextIsLiked)
      setLikesOverride(Math.max(0, likesValue + (nextIsLiked ? 1 : -1)))

      window.dispatchEvent(
        new CustomEvent('article:like-changed', {
          detail: {
            article: target ?? null,
            isLiked: nextIsLiked,
          },
        }),
      )
    } catch {
      window.dispatchEvent(
        new CustomEvent('article:like-error', {
          detail: {
            article: target ?? null,
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
      new CustomEvent('article:comments', {
        detail: {
          article: target ?? null,
        },
      }),
    )
  }

  async function handleShare() {
    try {
      if (onShare) {
        await onShare(target)
        window.dispatchEvent(
          new CustomEvent('article:share-success', {
            detail: {
              article: target ?? null,
              mode: 'custom',
            },
          }),
        )
        return
      }

      await defaultShare(target)
      window.dispatchEvent(
        new CustomEvent('article:share-success', {
          detail: {
            article: target ?? null,
            mode: navigator.share ? 'native' : 'clipboard',
          },
        }),
      )
    } catch {
      window.dispatchEvent(
        new CustomEvent('article:share-error', {
          detail: {
            article: target ?? null,
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

    defaultReport(target)
  }

  const metricsBlock = showMetrics && normalizedMetrics ? (
    <div className={[styles.metrics, metricsClassName].filter(Boolean).join(' ')}>
      <div className={[styles.metric, metricClassName, viewMetricClassName].filter(Boolean).join(' ')}>
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
      <button
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
      <button
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
