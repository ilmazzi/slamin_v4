import { lazy, Suspense, useEffect, useState } from 'react'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import shellStyles from '../shared/ContentPageShell.module.css'
import styles from './EventPage.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { createEventReport } from '../../app/api/reports.js'
import EventMap from '../../components/events/EventMap.jsx'
import PersonalEventsCalendar from '../../components/PersonalEventsCalendar.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import { listAllPublicEvents } from '../../app/api/events.js'
import { normalizeEventsForUi } from '../../app/normalizers/event.js'

const UpcomingEventSlider = lazy(() => import('../../components/events/UpcomingEventSlider.jsx'))

export default function EventPage() {
  const { t } = useI18n()
  const { token, user } = useAuth()
  const canCreateEvent = user?.account_type === 'poet_organizer'
  const [publicEvents, setPublicEvents] = useState([])
  const [eventsLoading, setEventsLoading] = useState(true)
  const [eventsError, setEventsError] = useState('')
  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    contentType: 'event',
    knownItems: [],
    shareCopiedMessage: t('events.ui.share_copied'),
    shareSentMessage: t('events.ui.share_sent'),
    shareFailedMessage: t('events.ui.share_failed'),
    reportSubmittedMessage: t('events.ui.report_submitted'),
    reportFailedMessage: t('common.no_results'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id ?? null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createEventReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  useEffect(() => {
    const controller = new AbortController()

    async function loadEvents() {
      setEventsLoading(true)
      setEventsError('')

      try {
        const rows = await listAllPublicEvents({ token, signal: controller.signal })
        setPublicEvents(normalizeEventsForUi(rows))
      } catch (error) {
        if (error?.name !== 'AbortError') {
          setEventsError(t('common.no_results'))
          setPublicEvents([])
        }
      } finally {
        if (!controller.signal.aborted) setEventsLoading(false)
      }
    }

    loadEvents()
    return () => controller.abort()
  }, [t, token])

  return (
    <MainLayout themeTone="event">
      <div className={[shellStyles.eventPage, styles.eventPage].join(' ')}>
        <PageHero
          title={t('events.title')}
          leadPrefix={t('events.lead_prefix')}
          leadEmphasis={t('events.lead_emphasis')}
          ctaLabel={t('events.create_event')}
          ctaHref="/events/create"
          hideCta={!canCreateEvent}
        />
        <EventMap events={publicEvents} isLoading={eventsLoading} error={eventsError} />
        <Suspense fallback={null}>
          <UpcomingEventSlider
            events={publicEvents.slice(0, 9)}
            isLoading={eventsLoading}
            error={eventsError}
          />
        </Suspense>
        {token ? <PersonalEventsCalendar tone="event" showVisibilityToggle={false} /> : null}

        <HomeDashboardPromoSection compactTop={true} />
      </div>

      <ContentReportModal
        key={reportModalItem?.id ?? 'event-report-closed'}
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? t('events.event_title')}
        title={t('events.ui.report_title')}
        description={t('events.ui.report_description')}
        cancelLabel={t('events.ui.report_cancel')}
        confirmLabel={t('events.ui.report_confirm')}
        isSubmitting={isSubmittingReport}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
