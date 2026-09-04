import styles from './PoemDetailPage.module.css'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { useEffect, useRef, useState } from 'react'
import { useLocation, useParams } from 'react-router-dom'
import shellStyles from '../shared/ContentPageShell.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import AllPoemsSection from '../../components/poems/AllPoemsSection.jsx'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import PoemCard from '../../components/poems/PoemCard.jsx'
import CommentsThread from '../../components/shared/CommentsThread.jsx'
import { createPoemReport } from '../../app/api/reports.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { scheduleScrollToComments } from '../../app/utils/scrollToComments.js'

export default function PoemDetailPage() {
  const { slug } = useParams()
  const location = useLocation()
  const { t } = useI18n()
  const { token } = useAuth()
  const commentsRef = useRef(null)
  const [poemDetail, setPoemDetail] = useState(null)

  useEffect(() => {
    if (location.hash !== '#comments' && !location.state?.focusComments) {
      return
    }
    if (!poemDetail?.id || !commentsRef.current) {
      return
    }

    const cancel = scheduleScrollToComments({ behavior: 'smooth' })

    return cancel
  }, [location.hash, location.state, poemDetail?.id])

  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    t,
    contentType: 'poem',
    knownItems: [],
    shareCopiedMessage: t('poems.actions.share_copied'),
    shareSentMessage: t('poems.actions.share_sent'),
    shareFailedMessage: t('poems.actions.share_failed'),
    reportSubmittedMessage: t('poems.actions.report_submitted'),
    reportFailedMessage: t('common.no_results'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id || item?.slug || poemDetail?.id || null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createPoemReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  return (
    <MainLayout themeTone="poems">
      <div className={[shellStyles.poemsPage, styles.poemDetailPage].join(' ')}>
        <div className={styles.poem}>
          <PoemCard
            slug={slug}
            onLoaded={setPoemDetail}
            onComments={() => commentsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
          />
        </div>
        <div id="comments" ref={commentsRef} style={{ scrollMarginTop: 104 }}>
          <CommentsThread contentType="poem" contentId={poemDetail?.id ?? null} title="Commenti poesia" />
        </div>

        <AllPoemsSection />
      </div>
      <ContentReportModal
        key={reportModalItem?.id ?? 'poem-report-closed'}
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? t('poems.all.no_title')}
        title={t('poems.actions.report_title')}
        description={t('poems.actions.report_description')}
        cancelLabel={t('poems.actions.report_cancel')}
        confirmLabel={t('poems.actions.report_confirm')}
        isSubmitting={isSubmittingReport}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
