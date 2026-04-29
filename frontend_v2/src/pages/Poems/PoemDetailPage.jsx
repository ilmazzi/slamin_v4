import styles from './PoemDetailPage.module.css'
import MainLayout from '../../components/layout/MainLayout.jsx'
import { useParams } from 'react-router-dom'
import shellStyles from '../shared/ContentPageShell.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import AllPoemsSection from '../../components/poems/AllPoemsSection.jsx'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import PoemCard from '../../components/poems/PoemCard.jsx'

export default function PoemDetailPage() {
  const { slug } = useParams()
  const { t } = useI18n()

  const {
    reportModalItem,
    toastMessage,
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
  })

  return (
    <MainLayout themeTone="poems">
      <div className={[shellStyles.poemsPage, styles.poemDetailPage].join(' ')}>
        <div className={styles.poem}>
          <PoemCard slug={slug} />
        </div>

        <AllPoemsSection />
      </div>
      <ContentReportModal
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? t('poems.all.no_title')}
        title={t('poems.actions.report_title')}
        description={t('poems.actions.report_description')}
        cancelLabel={t('poems.actions.report_cancel')}
        confirmLabel={t('poems.actions.report_confirm')}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
