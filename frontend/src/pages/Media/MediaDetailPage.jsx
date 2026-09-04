import { useEffect, useRef, useState } from 'react'
import { useLocation, useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { getMediaById } from '../../app/api/media.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import SocialActions from '../../components/shared/SocialActions.jsx'
import CommentsThread from '../../components/shared/CommentsThread.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { createMediaReport } from '../../app/api/reports.js'
import { scheduleScrollToComments } from '../../app/utils/scrollToComments.js'
import styles from './MediaDetailPage.module.css'

export default function MediaDetailPage() {
  const { mediaId } = useParams()
  const location = useLocation()
  const { token } = useAuth()
  const { t } = useI18n()
  const [media, setMedia] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const commentsRef = useRef(null)

  useEffect(() => {
    if (location.hash !== '#comments' && !location.state?.focusComments) {
      return
    }
    if (!media?.id || !commentsRef.current) {
      return
    }

    const cancel = scheduleScrollToComments({ behavior: 'smooth' })

    return cancel
  }, [location.hash, location.state, media?.id])

  useEffect(() => {
    let cancelled = false
    const controller = new AbortController()

    async function load() {
      if (!mediaId) {
        setMedia(null)
        setIsLoading(false)
        return
      }

      setIsLoading(true)
      setError('')
      try {
        const next = await getMediaById(mediaId, { token, signal: controller.signal })
        if (!cancelled) {
          setMedia(next)
        }
      } catch (nextError) {
        if (!cancelled && nextError?.name !== 'AbortError') {
          setMedia(null)
          setError(nextError?.message || 'Unable to load media item')
        }
      } finally {
        if (!cancelled) {
          setIsLoading(false)
        }
      }
    }

    load()
    return () => {
      cancelled = true
      controller.abort()
    }
  }, [mediaId, token])

  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    contentType: 'media',
    knownItems: media ? [media] : [],
    shareCopiedMessage: t('poems.actions.share_copied'),
    shareSentMessage: t('poems.actions.share_sent'),
    shareFailedMessage: t('poems.actions.share_failed'),
    reportSubmittedMessage: t('poems.actions.report_submitted'),
    reportFailedMessage: t('common.no_results'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id ?? media?.id ?? null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createMediaReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  return (
    <MainLayout themeTone="media">
      <div className={styles.page}>
        {isLoading ? <p>{t('common.loading')}</p> : null}
        {!isLoading && error ? <p>{error}</p> : null}
        {!isLoading && !error && !media ? <p>{t('common.no_results')}</p> : null}

        {!isLoading && media ? (
          <>
            <article className={styles.card}>
              <h1 className={styles.title}>{media?.title || 'Media'}</h1>
              <p className={styles.author}>{media?.author?.display_name || media?.author?.nickname || 'User'}</p>
              {media?.description ? <p className={styles.description}>{media.description}</p> : null}

              <div className={styles.player}>
                {media?.type === 'video' && media?.peertube_embed_url ? (
                  <iframe src={media.peertube_embed_url} title={media?.title || 'Media video'} allowFullScreen />
                ) : media?.type === 'video' && media?.file_url ? (
                  <video src={media.file_url} controls />
                ) : media?.file_url ? (
                  <img src={media.file_url} alt={media?.title || 'Media'} />
                ) : (
                  <div className={styles.placeholder} aria-hidden="true" />
                )}
              </div>

              <SocialActions
                target={media}
                contentType="media"
                apiBasePath="/media"
                publicBasePath="/media"
                variant="list"
                metrics={{
                  views: media?.views_count ?? 0,
                  likes: media?.likes_count ?? 0,
                  comments: media?.comments_count ?? 0,
                }}
                shareLabel="Share media"
                reportLabel="Report media"
                onComments={() => commentsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
              />
            </article>

            <div ref={commentsRef} id="comments" style={{ scrollMarginTop: 104 }}>
              <CommentsThread contentType="media" contentId={media.id} title="Commenti media" />
            </div>
          </>
        ) : null}
      </div>

      <ContentReportModal
        key={reportModalItem?.id ?? 'media-detail-report-closed'}
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? 'Media'}
        title="Report media"
        description="Do you want to report this media?"
        cancelLabel="Cancel"
        confirmLabel="Send report"
        isSubmitting={isSubmittingReport}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
