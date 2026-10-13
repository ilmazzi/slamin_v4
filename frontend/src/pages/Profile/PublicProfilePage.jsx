import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { getPublicProfile } from '../../app/api/profile.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import ProfileCard from './ProfileCard.jsx'
import styles from './ProfilePage.module.css'

export default function PublicProfilePage() {
  const { nickname } = useParams()
  const { t } = useI18n()
  const { token } = useAuth()
  const [profile, setProfile] = useState(null)
  const [status, setStatus] = useState('loading')

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

  return (
    <MainLayout>
      <section className={styles.page}>
        {status === 'loading' ? <p className={styles.status}>{t('profile.page.loading')}</p> : null}
        {status === 'missing' ? <p className={styles.status}>{t('profile.page.not_found')}</p> : null}
        {status === 'ready' ? (
          <>
            <p className={styles.kicker}>{t('profile.page.public_title', { name: displayName })}</p>
            <ProfileCard profile={profile} t={t} />
          </>
        ) : null}
      </section>
    </MainLayout>
  )
}
