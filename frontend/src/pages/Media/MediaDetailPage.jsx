import { useEffect, useRef, useState } from 'react'
import { useLocation, useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { getMediaById, syncMediaVideoStatus } from '../../app/api/media.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import SocialActions from '../../components/shared/SocialActions.jsx'
import CommentsThread from '../../components/shared/CommentsThread.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { createMediaReport } from '../../app/api/reports.js'
import { scheduleScrollToComments } from '../../app/utils/scrollToComments.js'
import pageStyles from '../shared/ContentPageShell.module.css'
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
          setError(nextError?.message || t('media.ui.load_error'))
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
  }, [mediaId, token, t])

  useEffect(() => {
    if (!mediaId || media?.type !== 'video' || media?.status !== 'processing') {
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setInterval(async () => {
      try {
        const next = await syncMediaVideoStatus(mediaId, { token, signal: controller.signal })
        if (next) {
          setMedia(next)
        }
      } catch (error) {
        if (error?.name !== 'AbortError') {
          window.clearInterval(timer)
        }
      }
    }, 5000)

    return () => {
      controller.abort()
      window.clearInterval(timer)
    }
  }, [mediaId, media?.type, media?.status, token])

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
    shareCopiedMessage: t('media.ui.share_copied'),
    shareSentMessage: t('media.ui.share_sent'),
    shareFailedMessage: t('media.ui.share_failed'),
    reportSubmittedMessage: t('media.ui.report_submitted'),
    reportFailedMessage: t('report.ui.submit_error'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id ?? media?.id ?? null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createMediaReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  return (
    <MainLayout themeTone="media" syncHeaderWithTheme={false}>
      <div className={pageStyles.mediaPage}>
      <div className={styles.page}>
        {isLoading ? <p>{t('common.loading')}</p> : null}
        {!isLoading && error ? <p className={styles.error}>{error}</p> : null}
        {!isLoading && !error && !media ? <p>{t('common.no_results')}</p> : null}

        {!isLoading && media ? (
          <>
            <article className={styles.card}>
              <h1 className={styles.title}>{media?.title || t('media.ui.untitled')}</h1>
              <p className={styles.author}>{media?.author?.display_name || media?.author?.nickname || t('common.unknown_user')}</p>
              {media?.description ? <p className={styles.description}>{media.description}</p> : null}

              <div className={styles.player}>
                {media?.type === 'video' && media?.peertube_embed_url ? (
                  <iframe src={media.peertube_embed_url} title={media?.title || t('media.ui.video_title')} allowFullScreen />
                ) : media?.type === 'video' && media?.file_url ? (
                  <video src={media.file_url} controls />
                ) : media?.file_url ? (
                  <img src={media.file_url} alt={media?.title || t('media.ui.untitled')} />
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
                shareLabel={t('media.ui.share')}
                reportLabel={t('media.ui.report')}
                onComments={() => commentsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
              />
            </article>

            <div ref={commentsRef} id="comments" style={{ scrollMarginTop: 104 }}>
              <CommentsThread contentType="media" contentId={media.id} />
            </div>
          </>
        ) : null}
      </div>
      </div>

      <ContentReportModal
        key={reportModalItem?.id ?? 'media-detail-report-closed'}
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? t('media.ui.untitled')}
        title={t('media.ui.report_title')}
        description={t('media.ui.report_description')}
        cancelLabel={t('media.ui.report_cancel')}
        confirmLabel={t('media.ui.report_confirm')}
        isSubmitting={isSubmittingReport}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
