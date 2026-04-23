import { useEffect, useMemo, useState } from 'react'
import { useI18n } from '../app/hooks/useI18n.js'
import { useAuth } from '../context/AuthContext.jsx'
import { authFetch } from '../app/api/authFetch.js'
import { resolvePublicUrl } from '../app/api/url.js'
import MainLayout from '../components/layout/MainLayout.jsx'
import styles from './DashboardPage.module.css'
import DashboardHero from '../components/dashboard/DashboardHero.jsx'
import {
  ArticleIcon,
  ActivityChevronLeftIcon,
  ActivityChevronRightIcon,
  ActivityCommentIcon,
  ActivityDeleteIcon,
  ActivityLikeIcon,
  ActivityPoemIcon,
  ActivityViewIcon,
  CalendarIcon,
  CommunityFriendsIcon,
  CommunityGroupsIcon,
  CommunityInvitesIcon,
  CommunityMessagesIcon,
  PenIcon,
  PlusIcon,
  VideoIcon,
} from '../components/icons/ActionIcons.jsx'
import PersonalEventsCalendar from '../components/PersonalEventsCalendar.jsx'

function resolveQuickActions(t, { hasAnyRole, canAny }) {
  const actions = []

  if (hasAnyRole(['poet']) || canAny(['poems.create', 'content.publish.own'])) {
    actions.push({ id: 'write-poem', label: t('home.quick_actions.write_poem'), href: '/poems/create', Icon: PenIcon })
  }
  if (hasAnyRole(['organizer']) || canAny(['events.create', 'events.create.public', 'events.create.private'])) {
    actions.push({
      id: 'create-event',
      label: t('home.quick_actions.create_event'),
      href: '/events/create',
      Icon: CalendarIcon,
    })
  }
  if (
    hasAnyRole(['poet', 'organizer', 'media'])
    || canAny(['videos.create', 'media.upload', 'content.publish.own'])
  ) {
    actions.push({
      id: 'upload-video',
      label: t('home.quick_actions.upload_video'),
      href: '/media/upload',
      Icon: VideoIcon,
    })
  }
  if (hasAnyRole(['editor', 'journalist']) || canAny(['articles.create', 'content.publish.own'])) {
    actions.push({
      id: 'publish-article',
      label: t('home.quick_actions.publish_article'),
      href: '/articles/create',
      Icon: ArticleIcon,
    })
  }

  if (actions.length === 0) {
    actions.push({ id: 'go-dashboard', label: t('home.quick_actions.go_dashboard'), href: '/dashboard', Icon: PenIcon })
  }

  return actions
}

export default function DashboardPage({ user }) {
  const { t, locale } = useI18n()
  const { token, hasAnyRole, canAny } = useAuth()
  const [widgets, setWidgets] = useState([])
  const [communityCards, setCommunityCards] = useState([])
  const [recentActivitiesData, setRecentActivitiesData] = useState({
    total: 0,
    per_page: 3,
    current_page: 1,
    last_page: 1,
    items: [],
  })
  const [activitiesPage, setActivitiesPage] = useState(1)
  const [isLoadingWidgets, setIsLoadingWidgets] = useState(true)
  const widgetsById = new Map(widgets.map((item) => [item.id, item]))
  const poems = widgetsById.get('poems')?.value ?? 0
  const events = widgetsById.get('events')?.value ?? 0
  const views = widgetsById.get('views')?.value ?? 0
  const likes = widgetsById.get('likes')?.value ?? 0

  useEffect(() => {
    const abortController = new AbortController()

    if (!token) {
      setWidgets([])
      setCommunityCards([])
      setRecentActivitiesData({
        total: 0,
        per_page: 3,
        current_page: 1,
        last_page: 1,
        items: [],
      })
      setIsLoadingWidgets(false)
      return
    }

    setIsLoadingWidgets(true)
    async function loadDashboardWidgets() {
      try {
        const response = await authFetch(`/api/v1/me/dashboard-widgets?locale=${locale}&dashboard_activities_page=${activitiesPage}`, {
          token,
          signal: abortController.signal,
        })

        if (!response.ok) {
          throw new Error(`Failed to fetch dashboard widgets: ${response.status}`)
        }

        const payload = await response.json()
        setWidgets(payload?.data?.widgets ?? [])
        setCommunityCards(payload?.data?.community_cards ?? [])
        setRecentActivitiesData(payload?.data?.recent_activities ?? {
          total: 0,
          per_page: 3,
          current_page: 1,
          last_page: 1,
          items: [],
        })
      } catch (error) {
        if (error?.name === 'AbortError') {
          return
        }
        console.error('Unable to load dashboard widgets:', error)
        setWidgets([])
        setCommunityCards([])
        setRecentActivitiesData({
          total: 0,
          per_page: 3,
          current_page: 1,
          last_page: 1,
          items: [],
        })
      } finally {
        setIsLoadingWidgets(false)
      }
    }

    loadDashboardWidgets()

    return () => {
      abortController.abort()
    }
  }, [token, locale, activitiesPage])

  const poemsValue = isLoadingWidgets ? '...' : poems
  const eventsValue = isLoadingWidgets ? '...' : events
  const viewsValue = isLoadingWidgets ? '...' : views
  const likesValue = isLoadingWidgets ? '...' : likes
  const quickActions = resolveQuickActions(t, { hasAnyRole, canAny })
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
  const recentActivities = recentActivitiesData?.items ?? []
  const recentTotal = Number(recentActivitiesData?.total ?? recentActivities.length)
  const recentPerPage = Number(recentActivitiesData?.per_page ?? 3)
  const recentCurrentPage = Number(recentActivitiesData?.current_page ?? activitiesPage)
  const recentLastPage = Number(recentActivitiesData?.last_page ?? 1)
  const pageStart = recentTotal === 0 ? 0 : ((recentCurrentPage - 1) * recentPerPage) + 1
  const pageEnd = recentTotal === 0 ? 0 : (pageStart + Math.max(0, recentActivities.length - 1))
  const maxVisiblePages = 5
  const pageNumbers = useMemo(() => {
    const rawStartPage = Math.max(1, recentCurrentPage - Math.floor(maxVisiblePages / 2))
    const rawEndPage = Math.min(recentLastPage, rawStartPage + maxVisiblePages - 1)
    const startPage = Math.max(1, rawEndPage - maxVisiblePages + 1)
    const endPage = rawEndPage

    return Array.from({ length: Math.max(0, endPage - startPage + 1) }, (_, index) => startPage + index)
  }, [recentCurrentPage, recentLastPage])
  const paginationTokens = useMemo(() => {
    if (recentLastPage <= 1) {
      return [1]
    }

    if (recentLastPage <= maxVisiblePages) {
      return Array.from({ length: recentLastPage }, (_, index) => index + 1)
    }

    const firstVisible = pageNumbers[0]
    const lastVisible = pageNumbers[pageNumbers.length - 1]
    const tokens = []

    if (firstVisible > 1) {
      tokens.push(1)
      if (firstVisible > 2) {
        tokens.push('left-ellipsis')
      }
    }

    tokens.push(...pageNumbers)

    if (lastVisible < recentLastPage) {
      if (lastVisible < recentLastPage - 1) {
        tokens.push('right-ellipsis')
      }
      tokens.push(recentLastPage)
    }

    return tokens
  }, [pageNumbers, recentLastPage])
  const recentResultsTemplate = t('home.dashboard_page.activity_results')
  const recentResultsText = (recentResultsTemplate === 'home.dashboard_page.activity_results'
    ? 'Mostra :from-:to di :total risultati'
    : recentResultsTemplate)
    .replace(':from', String(pageStart))
    .replace(':to', String(pageEnd))
    .replace(':total', String(recentTotal))

  const activityIconByType = {
    poem: ActivityPoemIcon,
    event: CalendarIcon,
    article: ArticleIcon,
    video: VideoIcon,
    user: CommunityFriendsIcon,
    group: CommunityGroupsIcon,
    photo: ActivityViewIcon,
    gig: CommunityGroupsIcon,
  }
  const activityIconByAction = {
    viewed: ActivityViewIcon,
    liked: ActivityLikeIcon,
    commented: ActivityCommentIcon,
    deleted: ActivityDeleteIcon,
    updated: PenIcon,
    followed: CommunityFriendsIcon,
    unfollowed: CommunityFriendsIcon,
    invited: CommunityInvitesIcon,
    accepted: CalendarIcon,
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
        <section className={styles.actionsRow}>
          {quickActions.map((action) => {
            const ActionIcon = action.Icon

            return (
              <a key={action.id} className={styles.actionButton} href={action.href}>
                <ActionIcon className={styles.actionIcon} />
                <span className={`${styles.actionButtonText} typeUi textWhite`}>{action.label}</span>
                <PlusIcon className={styles.actionPlusIcon} />
              </a>
            )
          })}
        </section>
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

            return (
              <article key={card.id} className={styles.communityCard}>
                <span className={styles.communityIconWrap} aria-hidden="true">
                  <Icon className={styles.communityIcon} />
                </span>
                <div className={styles.communityTextWrap}>
                  <strong className={styles.communityCount}>{card.count}</strong>
                  <span className={styles.communityCardTitle}>{card.title}</span>
                  <span className={styles.communityCardSubtitle}>{card.subtitle}</span>
                </div>
              </article>
            )
          })}
        </section>
        <section className={styles.recentTitleWrap}>
          <h2 className={styles.recentTitle}>
            <span>{t('home.dashboard_page.recent')}</span>{' '}
            <span className={styles.recentTitleAccent}>{t('home.dashboard_page.activities')}</span>
          </h2>
        </section>
        <section className={styles.recentList}>
          {recentActivities.map((item) => {
            const Icon = activityIconByAction[item.action] || activityIconByType[item.type] || CalendarIcon

            return (
              <article key={item.id} className={styles.recentItem}>
                <div className={styles.recentMainInfo}>
                  <span className={styles.recentIconWrap} aria-hidden="true">
                    <Icon className={styles.recentIcon} />
                  </span>
                  <p className={styles.recentLabelAndTitle}>
                    <strong className={styles.recentLabel}>{item.label}</strong>
                    <span className={styles.recentItemTitle}>{item.title}</span>
                  </p>
                </div>
                <time className={styles.recentTime}>{item.time_ago}</time>
              </article>
            )
          })}
          {!isLoadingWidgets && recentActivities.length === 0 ? (
            <p className={styles.recentEmpty}>{t('common.no_results')}</p>
          ) : null}
        </section>
        <p className={styles.recentResultsText}>
          {recentResultsText}
        </p>
        <section className={styles.recentPaginationWrap}>
          <div className={styles.recentPagination}>
            <button
              type="button"
              className={styles.recentPageArrow}
              aria-label={t('events.prev_month')}
              onClick={() => setActivitiesPage((current) => Math.max(1, current - 1))}
              disabled={recentCurrentPage <= 1}
            >
              <ActivityChevronLeftIcon className={styles.recentPageArrowIcon} />
            </button>
            {paginationTokens.map((token, index) => (
              typeof token === 'number' ? (
                <button
                  key={token}
                  type="button"
                  className={`${styles.recentPageButton} ${token === recentCurrentPage ? styles.recentPageButtonActive : ''}`}
                  onClick={() => setActivitiesPage(token)}
                >
                  {token}
                </button>
              ) : (
                <span key={`${token}-${index}`} className={styles.recentPageEllipsis} aria-hidden="true">...</span>
              )
            ))}
            <button
              type="button"
              className={styles.recentPageArrow}
              aria-label={t('events.next_month')}
              onClick={() => setActivitiesPage((current) => Math.min(recentLastPage, current + 1))}
              disabled={recentCurrentPage >= recentLastPage}
            >
              <ActivityChevronRightIcon className={styles.recentPageArrowIcon} />
            </button>
          </div>
        </section>
    </MainLayout>
  )
}


























