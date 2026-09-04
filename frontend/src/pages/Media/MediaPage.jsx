import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import styles from './MediaPage.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { listMedia } from '../../app/api/media.js'
import SocialActions from '../../components/shared/SocialActions.jsx'
import { useAuth } from '../../app/hooks/useAuth.js'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { createMediaReport } from '../../app/api/reports.js'

export default function MediaPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const { items, meta, isLoading, error } = usePaginatedApiList({
    fetcher: (params, { signal }) => listMedia(params, { token, signal }),
    params: { page, perPage: 12 },
    deps: [page, token],
  })

  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    contentType: 'media',
    knownItems: items,
    shareCopiedMessage: t('poems.actions.share_copied'),
    shareSentMessage: t('poems.actions.share_sent'),
    shareFailedMessage: t('poems.actions.share_failed'),
    reportSubmittedMessage: t('poems.actions.report_submitted'),
    reportFailedMessage: t('common.no_results'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id ?? null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createMediaReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  return (
    <MainLayout themeTone="media" syncHeaderWithTheme={false}>
      <div className={styles.mediaPage}>
        <PageHero
          title={t('media.title')}
          leadPrefix={t('media.lead_prefix')}
          leadEmphasis={t('media.lead_emphasis')}
          ctaLabel={t('media.create_media')}
          ctaHref="/media/upload"
        />

        <section className={styles.listSection}>
          {isLoading ? <p>{t('common.loading')}</p> : null}
          {!isLoading && error ? <p>{error}</p> : null}
          {!isLoading && !error && items.length === 0 ? <p>{t('common.no_results')}</p> : null}

          <div className={styles.grid}>
            {items.map((item) => (
              <article key={item.id} className={styles.card}>
                <Link to={`/media/${item.id}`} className={styles.coverWrap}>
                  {item?.peertube_thumbnail_url || item?.file_url ? (
                    <img src={item?.peertube_thumbnail_url || item?.file_url} alt="" className={styles.cover} />
                  ) : (
                    <div className={styles.coverPlaceholder} aria-hidden="true" />
                  )}
                </Link>
                <div className={styles.cardBody}>
                  <h3 className={styles.cardTitle}>{item?.title || 'Media'}</h3>
                  <p className={styles.cardMeta}>{item?.author?.display_name || item?.author?.nickname || 'User'}</p>
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
                    shareLabel="Share media"
                    reportLabel="Report media"
                    onComments={() => navigate(`/media/${item.id}#comments`)}
                  />
                </div>
              </article>
            ))}
          </div>

          <div className={styles.pager}>
            <button type="button" onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1 || isLoading}>
              Prev
            </button>
            <span>
              {meta.current_page} / {meta.last_page}
            </span>
            <button
              type="button"
              onClick={() => setPage((p) => Math.min(meta.last_page, p + 1))}
              disabled={page >= meta.last_page || isLoading}
            >
              Next
            </button>
          </div>
        </section>
      </div>

      <ContentReportModal
        key={reportModalItem?.id ?? 'media-report-closed'}
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
