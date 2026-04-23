import {
  mediaReportFeaturedFigmaIcon,
  mediaReportListFigmaIcon,
  mediaShareFeaturedFigmaIcon,
  mediaShareListFigmaIcon,
} from '../../assets/home/media/index.js'
import styles from './ArticleSocialActions.module.css'

function resolveArticleUrl(article) {
  const slug = article?.slug ?? article?.id
  if (!slug) {
    return `${window.location.origin}/articles`
  }

  return `${window.location.origin}/articles/${slug}`
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

export default function ArticleSocialActions({
  article,
  shareLabel,
  reportLabel,
  variant = 'featured',
  className = '',
  iconClassName = '',
  shareIconClassName = '',
  reportIconClassName = '',
  onShare,
  onReport,
}) {
  const classes = [styles.actions, className].filter(Boolean).join(' ')
  const shareIcon = variant === 'list' ? mediaShareListFigmaIcon : mediaShareFeaturedFigmaIcon
  const reportIcon = variant === 'list' ? mediaReportListFigmaIcon : mediaReportFeaturedFigmaIcon

  async function handleShare() {
    try {
      if (onShare) {
        await onShare(article)
        window.dispatchEvent(
          new CustomEvent('article:share-success', {
            detail: {
              article: article ?? null,
              mode: 'custom',
            },
          }),
        )
        return
      }

      await defaultShare(article)
      window.dispatchEvent(
        new CustomEvent('article:share-success', {
          detail: {
            article: article ?? null,
            mode: navigator.share ? 'native' : 'clipboard',
          },
        }),
      )
    } catch {
      window.dispatchEvent(
        new CustomEvent('article:share-error', {
          detail: {
            article: article ?? null,
          },
        }),
      )
    }
  }

  function handleReport() {
    if (onReport) {
      onReport(article)
      return
    }

    defaultReport(article)
  }

  return (
    <div className={classes}>
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
  )
}
