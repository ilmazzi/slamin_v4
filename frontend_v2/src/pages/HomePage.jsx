import { useEffect, useState } from 'react'
import { getApiUrl } from '../app/api/url.js'
import MainLayout from '../components/layout/MainLayout.jsx'
import {
  categoryCards,
  forumPromo,
  mediaItems,
} from '../components/home/homeData.js'
import HomeCategories from '../components/home/HomeCategories.jsx'
import HomeDashboardPromoSection from '../components/home/HomeDashboardPromoSection.jsx'
import HomeForumPromo from '../components/home/HomeForumPromo.jsx'
import HomeHero from '../components/home/HomeHero.jsx'
import HomeIntro from '../components/home/HomeIntro.jsx'
import HomeMedia from '../components/home/HomeMedia.jsx'
import HomePublishPromo from '../components/home/HomePublishPromo.jsx'
import HomeStats from '../components/home/HomeStats.jsx'
import { useI18n } from '../app/hooks/useI18n.js'
import styles from './HomePage.module.css'

export function HomePage({ variant = 'guest', user = null }) {
  const { t, locale } = useI18n()
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
            ? new Intl.NumberFormat(locale).format(card.value)
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
        const response = await fetch(
          getApiUrl(`/api/v1/home/media?locale=${encodeURIComponent(locale)}`),
          {
            signal: controller.signal,
            headers: {
              Accept: 'application/json',
            },
            cache: 'no-store',
          },
        )

        if (!response.ok) {
          return
        }

        const payload = await response.json()
        const nextItems = payload?.data?.community_media?.items

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
  }, [locale])

  useEffect(() => {
    const controller = new AbortController()

    async function loadHomeStats() {
      try {
        const response = await fetch(
          getApiUrl(`/api/v1/home/stats?locale=${encodeURIComponent(locale)}`),
          {
            signal: controller.signal,
            headers: {
              Accept: 'application/json',
            },
            cache: 'no-store',
          },
        )

        if (!response.ok) {
          return
        }

        const payload = await response.json()
        const nextSection = payload?.data?.community_stats

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

        <HomeMedia
          headingAccent={t('home.community_media.title')}
          headingSubtitle={t('home.community_media.subtitle')}
          items={communityMediaItems}
          title={t('home.community_media.placeholder_title')}
          subtitle={t('home.community_media.placeholder_subtitle')}
          playAria={t('home.community_media.play_aria')}
          openAria={t('home.community_media.open_aria')}
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

        <HomePublishPromo
          title={t('home.publish_cta.title')}
          subtitle={t('home.publish_cta.subtitle')}
          ctaLabel={t('home.publish_cta.button_label')}
        />
    </MainLayout>
  )
}
