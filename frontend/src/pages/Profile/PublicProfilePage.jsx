import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { createDirectChatThread } from '../../app/api/chat.js'
import { getPublicProfile, getPublicProfileActivities, setProfileFollow } from '../../app/api/profile.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { formatLocaleNumber } from '../../app/formatters/number.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import DashboardHero from '../../components/dashboard/DashboardHero.jsx'
import RecentActivities from '../../components/dashboard/RecentActivities.jsx'
import MainLayout from '../../components/layout/MainLayout.jsx'
import dashboardStyles from '../Dashboard/DashboardPage.module.css'
import ProfileCard from './ProfileCard.jsx'
import ProfileContentSection from './ProfileContentSection.jsx'
import styles from './ProfilePage.module.css'

const ROLES_BY_ACCOUNT_TYPE = {
  poet_organizer: ['poet', 'organizer'],
  audience: ['audience'],
}

export default function PublicProfilePage() {
  const { nickname } = useParams()
  const navigate = useNavigate()
  const { t, locale } = useI18n()
  const { token, isAuthenticated, user } = useAuth()
  const [profile, setProfile] = useState(null)
  const [stats, setStats] = useState(null)
  const [status, setStatus] = useState('loading')
  const [activities, setActivities] = useState(null)
  const [activitiesPage, setActivitiesPage] = useState(1)
  const [activitiesKey, setActivitiesKey] = useState('')
  const [isWriting, setIsWriting] = useState(false)
  const [writeError, setWriteError] = useState('')
  const [followState, setFollowState] = useState(null)
  const [isFollowBusy, setIsFollowBusy] = useState(false)

  useEffect(() => {
    const controller = new AbortController()

    async function load() {
      setStatus('loading')
      setProfile(null)
      setStats(null)
      setFollowState(null)
      setActivities(null)
      setActivitiesPage(1)

      const result = await getPublicProfile(nickname, {
        token,
        signal: controller.signal,
      })

      if (controller.signal.aborted) {
        return
      }

      if (!result.ok || !result.profile) {
        setStatus('missing')
        return
      }

      setProfile(result.profile)
      setStats(result.stats)
      setFollowState(result.follow)
      setStatus('ready')
    }

    load().catch(() => {
      if (!controller.signal.aborted) {
        setStatus('missing')
      }
    })

    return () => controller.abort()
  }, [nickname, token])

  const requestKey = `${nickname}:${activitiesPage}:${locale}`
  const isLoadingActivities = activitiesKey !== requestKey

  useEffect(() => {
    if (status !== 'ready') {
      return undefined
    }

    const controller = new AbortController()
    const currentKey = `${nickname}:${activitiesPage}:${locale}`

    getPublicProfileActivities(nickname, { page: activitiesPage, token, signal: controller.signal })
      .then((data) => {
        if (!controller.signal.aborted) {
          setActivities(data)
          setActivitiesKey(currentKey)
        }
      })
      .catch(() => {
        if (!controller.signal.aborted) {
          setActivities(null)
          setActivitiesKey(currentKey)
        }
      })

    return () => controller.abort()
  }, [status, nickname, activitiesPage, locale, token])

  const canWrite = Boolean(
    isAuthenticated && token && profile?.nickname && profile?.nickname !== user?.profile?.nickname && !profile?.is_owner,
  )

  async function handleWrite() {
    if (!canWrite || isWriting) {
      return
    }
    setIsWriting(true)
    setWriteError('')
    try {
      const thread = await createDirectChatThread({ nickname: profile.nickname }, { token })
      if (thread?.id) {
        navigate(`/chat/${thread.id}`)
      }
    } catch (error) {
      setWriteError(error?.message || t('profile.page.write_error'))
    } finally {
      setIsWriting(false)
    }
  }

  async function handleToggleFollow() {
    if (!canWrite || isFollowBusy || !followState) {
      return
    }
    setIsFollowBusy(true)
    setWriteError('')
    try {
      const next = await setProfileFollow(profile.nickname, !followState.is_following, { token })
      if (next) {
        setFollowState(next)
      }
    } catch (error) {
      setWriteError(error?.message || t('profile.page.follow_error'))
    } finally {
      setIsFollowBusy(false)
    }
  }

  if (status !== 'ready') {
    return (
      <MainLayout>
        <section className={styles.page}>
          <p className={styles.status}>
            {status === 'loading' ? t('profile.page.loading') : t('profile.page.not_found')}
          </p>
        </section>
      </MainLayout>
    )
  }

  return (
    <MainLayout mainClassName={dashboardStyles.main}>
      <DashboardHero
        displayName={profile.display_name || profile.nickname}
        nickname={`@${profile.nickname}`}
        avatarSrc={resolvePublicUrl(profile.avatar_url)}
        backgroundSrc={resolvePublicUrl(profile.banner_url)}
        roles={ROLES_BY_ACCOUNT_TYPE[profile.account_type] ?? []}
        createdAt={profile.member_since}
      />

      <div className={styles.about}>
        {writeError ? <p className={styles.error}>{writeError}</p> : null}
        <ProfileCard
          profile={profile}
          t={t}
          onWrite={canWrite ? handleWrite : null}
          isWriting={isWriting}
          follow={
            followState
              ? {
                  isFollowing: followState.is_following,
                  countLabel: formatLocaleNumber(followState.followers_count ?? 0, locale),
                  onToggle: canWrite ? handleToggleFollow : null,
                  isBusy: isFollowBusy,
                }
              : null
          }
        />
      </div>

      {stats && profile.sections?.length ? (
        <section className={dashboardStyles.statsRow}>
          {(profile.sections ?? []).map((key) => (
            <article key={key} className={dashboardStyles.statCard}>
              <strong className={`${dashboardStyles.statValue} textRed`}>
                {formatLocaleNumber(stats[key] ?? 0, locale)}
              </strong>
              <span className={`${dashboardStyles.statLabel} typeBody textBlack`}>
                {t(`profile.works.${key}`).toLowerCase()}
              </span>
            </article>
          ))}
        </section>
      ) : null}

      {(profile.sections ?? []).map((kind) => (
        <ProfileContentSection key={kind} kind={kind} nickname={profile.nickname} token={token} />
      ))}

      <RecentActivities
        data={activities}
        page={activitiesPage}
        onPageChange={setActivitiesPage}
        isLoading={isLoadingActivities}
        emptyText={t('profile.page.activities_empty')}
      />
    </MainLayout>
  )
}
