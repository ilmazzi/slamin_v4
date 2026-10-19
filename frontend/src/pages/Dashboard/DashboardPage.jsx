import { useState } from 'react'
import { Link } from 'react-router-dom'
import { formatLocaleNumber } from '../../app/formatters/number.js'
import { ownerProfileFromUser } from '../../app/api/profile.js'
import { useDashboardWidgets } from '../../app/hooks/useDashboardWidgets.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './DashboardPage.module.css'
import DashboardHero from '../../components/dashboard/DashboardHero.jsx'
import RecentActivities from '../../components/dashboard/RecentActivities.jsx'
import DashboardPublications from './DashboardPublications.jsx'
import {
  CommunityFriendsIcon,
  CommunityGroupsIcon,
  CommunityInvitesIcon,
  CommunityMessagesIcon,
} from '../../components/icons/ActionIcons.jsx'
import PersonalEventsCalendar from '../../components/PersonalEventsCalendar.jsx'
export default function DashboardPage({ user }) {
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const profile = ownerProfileFromUser(user)
  const canManagePublications = user?.account_type === 'poet_organizer'
  const [activitiesPage, setActivitiesPage] = useState(1)
  const [activitiesFeed, setActivitiesFeed] = useState('following')
  const {
    widgets,
    communityCards,
    recentActivitiesData,
    followingCount,
    isLoading: isLoadingWidgets,
  } = useDashboardWidgets({
    token,
    locale,
    activitiesPage,
    activitiesFeed,
    enabled: Boolean(token),
  })
  const widgetsById = new Map(widgets.map((item) => [item.id, item]))
  const poems = widgetsById.get('poems')?.value ?? 0
  const events = widgetsById.get('events')?.value ?? 0
  const views = widgetsById.get('views')?.value ?? 0
  const likes = widgetsById.get('likes')?.value ?? 0
  const formatMetricValue = (value) => (isLoadingWidgets ? '...' : formatLocaleNumber(value, locale))

  const poemsValue = formatMetricValue(poems)
  const eventsValue = formatMetricValue(events)
  const viewsValue = formatMetricValue(views)
  const likesValue = formatMetricValue(likes)
  const withCount = (key, count) => t(key).replace(':count', String(count))
  const fallbackCommunityCards = [
    { id: 'friends', count: 0, title: t('home.dashboard_page.friends_title'), subtitle: withCount('home.dashboard_page.friends_subtitle', 0) },
    { id: 'groups', count: 0, title: t('home.dashboard_page.groups_title'), subtitle: withCount('home.dashboard_page.groups_subtitle', 0) },
    { id: 'invites', count: 0, title: t('home.dashboard_page.invites_title'), subtitle: withCount('home.dashboard_page.invites_subtitle', 0) },
    { id: 'messages', count: 0, title: t('home.dashboard_page.messages_title'), subtitle: withCount('home.dashboard_page.messages_subtitle', 0) },
  ]
  const resolvedCommunityCards = (communityCards.length ? communityCards : fallbackCommunityCards)
    .slice(0, 4)
    .map((card) => ({ ...card, count: isLoadingWidgets ? '...' : card.count }))
  const communityIconById = {
    friends: CommunityFriendsIcon,
    groups: CommunityGroupsIcon,
    invites: CommunityInvitesIcon,
    messages: CommunityMessagesIcon,
  }

  return (
    <MainLayout variant="auth" user={user} mainClassName={styles.main}>
        <DashboardHero
          displayName={user?.name}
          nickname={user?.nickname}
          avatarSrc={resolvePublicUrl(user?.profile_photo_url ?? user?.profile_photo)}
          backgroundSrc={resolvePublicUrl(user?.banner_image_url ?? user?.banner_image)}
          roles={user?.roles}
          createdAt={user?.created_at}
        />
        <section className={styles.statsRow}>
          <article className={styles.statCard}>
            <strong className={`${styles.statValue} textRed`}>{poemsValue}</strong>
            <span className={`${styles.statLabel} typeBody textBlack`}>{t('home.dashboard.poems').toLowerCase()}</span>
          </article>
          <article className={styles.statCard}>
            <strong className={`${styles.statValue} textRed`}>{eventsValue}</strong>
            <span className={`${styles.statLabel} typeBody textBlack`}>{t('home.dashboard.events').toLowerCase()}</span>
          </article>
          <article className={styles.statCard}>
            <strong className={`${styles.statValue} textRed`}>{viewsValue}</strong>
            <span className={`${styles.statLabel} typeBody textBlack`}>{t('home.dashboard.views').toLowerCase()}</span>
          </article>
          <article className={styles.statCard}>
            <strong className={`${styles.statValue} textRed`}>{likesValue}</strong>
            <span className={`${styles.statLabel} typeBody textBlack`}>{t('home.dashboard.likes').toLowerCase()}</span>
          </article>
        </section>
        <div className={styles.statisticsLinkWrap}>
          <Link to="/dashboard/statistics" className={styles.statisticsLink}>
            {t('statistics.view_all')} <span aria-hidden="true">→</span>
          </Link>
        </div>

        <PersonalEventsCalendar />
        <section className={styles.communityTitleWrap}>
          <h2 className={styles.communityTitle}>
            <span>{t('home.dashboard_page.connections')}</span>{' '}
            <span className={styles.communityTitleAccent}>{t('home.dashboard_page.community')}</span>
          </h2>
        </section>
        <section className={styles.communityBoxes}>
          {resolvedCommunityCards.map((card) => {
            const Icon = communityIconById[card.id] || CommunityFriendsIcon

            const cardBody = (
              <>
                <span className={styles.communityIconWrap} aria-hidden="true">
                  <Icon className={styles.communityIcon} />
                </span>
                <div className={styles.communityTextWrap}>
                  <strong className={styles.communityCount}>{card.count}</strong>
                  <span className={styles.communityCardTitle}>{card.title}</span>
                  <span className={styles.communityCardSubtitle}>{card.subtitle}</span>
                </div>
              </>
            )

            if (card.id === 'messages') {
              return (
                <Link key={card.id} to="/chat" className={styles.communityCard}>
                  {cardBody}
                </Link>
              )
            }

            if (card.id === 'groups') {
              return (
                <Link key={card.id} to="/groups?mine=1" className={styles.communityCard}>
                  {cardBody}
                </Link>
              )
            }

            return (
              <article key={card.id} className={styles.communityCard}>
                {cardBody}
              </article>
            )
          })}
        </section>
        {profile?.nickname ? (
          <>
            <section className={styles.recentTitleWrap}>
              <h2 className={styles.recentTitle}>
                <span>{t('home.dashboard_page.publications_lead')}</span>{' '}
                <span className={styles.recentTitleAccent}>{t('home.dashboard_page.publications_accent')}</span>
              </h2>
            </section>
            <DashboardPublications
              nickname={profile.nickname}
              canManage={canManagePublications}
              token={canManagePublications ? token : null}
            />
          </>
        ) : null}
        <RecentActivities
          data={recentActivitiesData}
          page={activitiesPage}
          onPageChange={setActivitiesPage}
          isLoading={isLoadingWidgets}
          tabs={[
            { id: 'following', label: t('home.dashboard_page.activity_feeds.following') },
            { id: 'mine', label: t('home.dashboard_page.activity_feeds.mine') },
          ]}
          activeTab={activitiesFeed}
          onTabChange={(feed) => {
            setActivitiesFeed(feed)
            setActivitiesPage(1)
          }}
          emptyText={
            activitiesFeed === 'following'
              ? t(followingCount > 0 ? 'home.dashboard_page.following_empty' : 'home.dashboard_page.following_none')
              : undefined
          }
        />
    </MainLayout>
  )
}
