import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { createDirectChatThread } from '../../app/api/chat.js'
import { getPublicProfile } from '../../app/api/profile.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import ProfileCard from './ProfileCard.jsx'
import ProfileWorks from './ProfileWorks.jsx'
import styles from './ProfilePage.module.css'

export default function PublicProfilePage() {
  const { nickname } = useParams()
  const navigate = useNavigate()
  const { t } = useI18n()
  const { token, isAuthenticated, user } = useAuth()
  const [profile, setProfile] = useState(null)
  const [status, setStatus] = useState('loading')
  const [isWriting, setIsWriting] = useState(false)
  const [writeError, setWriteError] = useState('')

  useEffect(() => {
    const controller = new AbortController()
    let cancelled = false

    async function load() {
      setStatus('loading')
      setProfile(null)

      const result = await getPublicProfile(nickname, {
        token,
        signal: controller.signal,
      })

      if (cancelled) {
        return
      }

      if (!result.ok || !result.profile) {
        setStatus('missing')
        return
      }

      setProfile(result.profile)
      setStatus('ready')
    }

    load().catch(() => {
      if (!cancelled) {
        setStatus('missing')
      }
    })

    return () => {
      cancelled = true
      controller.abort()
    }
  }, [nickname, token])

  const displayName = profile?.display_name || profile?.nickname || ''
  const canManage = Boolean(profile?.is_owner && user?.account_type === 'poet_organizer')
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

  return (
    <MainLayout>
      <section className={styles.page}>
        {status === 'loading' ? <p className={styles.status}>{t('profile.page.loading')}</p> : null}
        {status === 'missing' ? <p className={styles.status}>{t('profile.page.not_found')}</p> : null}
        {status === 'ready' ? (
          <>
            <p className={styles.kicker}>{t('profile.page.public_title', { name: displayName })}</p>
            {writeError ? <p className={styles.status}>{writeError}</p> : null}
            <ProfileCard
              profile={profile}
              t={t}
              onWrite={canWrite ? handleWrite : null}
              isWriting={isWriting}
            />
            <ProfileWorks
              nickname={profile.nickname}
              canManage={canManage}
              token={canManage ? token : null}
            />
          </>
        ) : null}
      </section>
    </MainLayout>
  )
}
