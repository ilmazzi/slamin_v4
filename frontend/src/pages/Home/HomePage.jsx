import { lazy, Suspense, useEffect, useState } from 'react'
import { formatLocaleNumber } from '../../app/formatters/number.js'
import { getHomeMedia, getHomeStats } from '../../app/api/home.js'
import { createMediaReport } from '../../app/api/reports.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import {
  categoryCards,
  forumPromo,
  mediaItems,
} from '../../components/home/homeData.js'
import HomeCategories from '../../components/home/HomeCategories.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import HomeForumPromo from '../../components/home/HomeForumPromo.jsx'
import HomeHero from '../../components/home/HomeHero.jsx'
import HomeIntro from '../../components/home/HomeIntro.jsx'
import HomeMedia from '../../components/home/HomeMedia.jsx'
// import HomePublishPromo from '../../components/home/HomePublishPromo.jsx'
import HomeStats from '../../components/home/HomeStats.jsx'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './HomePage.module.css'

const HomeUpcomingEvents = lazy(() => import('../../components/home/HomeUpcomingEvents.jsx'))

export function HomePage({ variant = 'guest', user = null }) {
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const [communityMediaItems, setCommunityMediaItems] = useState(mediaItems)
  const [communityStatsSection, setCommunityStatsSection] = useState(null)

  const cards = categoryCards.map((card) => ({
    ...card,
    title: t(`home.content_cards.${card.key}`),
  }))

  const stats = Array.isArray(communityStatsSection?.cards) && communityStatsSection.cards.length
    ? communityStatsSection.cards.map((card) => ({
        id: card.id,
        value:
          typeof card.value === 'number'
            ? formatLocaleNumber(card.value, locale)
            : String(card.value ?? 0),
        label: card.label,
      }))
    : [
        { id: 'views', value: '0', label: t('home.stats.views') },
        { id: 'poets', value: '0', label: t('home.stats.poets') },
        { id: 'events', value: '0', label: t('home.stats.events') },
        { id: 'videos', value: '0', label: t('home.stats.videos') },
      ]

  useEffect(() => {
    const controller = new AbortController()

    async function loadHomeMedia() {
      try {
        const nextItems = await getHomeMedia(locale, { signal: controller.signal, token })
        if (Array.isArray(nextItems) && nextItems.length > 0) {
          setCommunityMediaItems(nextItems)
        }
      } catch (error) {
        if (error?.name === 'AbortError') {
          return
        }

        console.error('Unable to load home media:', error)
      }
    }

    loadHomeMedia()

    return () => controller.abort()
  }, [locale, token])

  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    contentType: 'media',
    knownItems: communityMediaItems,
    shareCopiedMessage: t('media.ui.share_copied'),
    shareSentMessage: t('media.ui.share_sent'),
    shareFailedMessage: t('media.ui.share_failed'),
    reportSubmittedMessage: t('media.ui.report_submitted'),
    reportFailedMessage: t('report.ui.submit_error'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id ?? null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createMediaReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  useEffect(() => {
    const controller = new AbortController()

    async function loadHomeStats() {
      try {
        const nextSection = await getHomeStats(locale, { signal: controller.signal })
        if (Array.isArray(nextSection?.cards) && nextSection.cards.length > 0) {
          setCommunityStatsSection(nextSection)
        }
      } catch (error) {
        if (error?.name === 'AbortError') {
          return
        }

        console.error('Unable to load home stats:', error)
      }
    }

    loadHomeStats()

    return () => controller.abort()
  }, [locale])

  return (
    <MainLayout variant={variant} user={user} pageClassName={styles.page} mainClassName={styles.main}>
        <HomeHero
          welcomePrefix={t('home.hero.welcome_prefix')}
          brandWordmark={t('home.hero.brand_wordmark')}
          tagline={t('home.hero.tagline')}
          subcopy={t('home.hero.subcopy')}
          searchPlaceholder={t('home.hero.search_placeholder')}
        />

        <HomeIntro
          accent={t('home.intro.title')}
          descriptionPrefix={t('home.intro.description_prefix')}
          descriptionEmphasis={t('home.intro.description_emphasis')}
          descriptionMiddle={t('home.intro.description_middle')}
          descriptionTail={t('home.intro.description_tail')}
          ctaLabel={t('home.intro.button_label')}
        />

        <HomeCategories
          cards={cards}
          ctaLabel={t('home.content_cards.discover_more')}
        />

        <Suspense fallback={null}>
          <HomeUpcomingEvents />
        </Suspense>

        <HomeMedia
          headingAccent={t('home.community_media.title')}
          headingSubtitle={t('home.community_media.subtitle')}
          items={communityMediaItems}
          title={t('home.community_media.placeholder_title')}
          subtitle={t('home.community_media.placeholder_subtitle')}
          playAria={t('home.community_media.play_aria')}
          openAria={t('home.community_media.open_aria')}
          shareLabel={t('media.ui.share')}
          reportLabel={t('media.ui.report')}
          prevAria={t('home.community_media.prev_aria')}
          nextAria={t('home.community_media.next_aria')}
        />

        <HomeForumPromo
          title={t('home.forum.title')}
          description={t('home.forum.description')}
          ctaLabel={t('home.forum.cta')}
          imageDesktopSrc={forumPromo.imageDesktopSrc}
          imageMobileSrc={forumPromo.imageMobileSrc}
          imageDesktopSrcSet={forumPromo.imageDesktopSrcSet}
          imageMobileSrcSet={forumPromo.imageMobileSrcSet}
        />

        <HomeStats
          accent={communityStatsSection?.title || t('home.stats.title')}
          subtitle={communityStatsSection?.subtitle || t('home.stats.subtitle')}
          stats={stats}
        />

        {variant === 'auth' && user ? <HomeDashboardPromoSection /> : null}

        {/* <HomePublishPromo
          title={t('home.publish_cta.title')}
          subtitle={t('home.publish_cta.subtitle')}
          ctaLabel={t('home.publish_cta.button_label')}
        /> */}

        <ContentReportModal
          key={reportModalItem?.id ?? 'home-media-report-closed'}
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
