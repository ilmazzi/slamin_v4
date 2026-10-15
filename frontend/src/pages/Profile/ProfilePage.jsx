import { Navigate } from 'react-router-dom'
import { ownerProfileFromUser } from '../../app/api/profile.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './ProfilePage.module.css'

export default function ProfilePage() {
  const { t } = useI18n()
  const { user } = useAuth()
  const nickname = ownerProfileFromUser(user)?.nickname

  if (nickname) {
    return <Navigate to={`/u/${encodeURIComponent(nickname)}`} replace />
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <p className={styles.status}>{t('profile.page.load_error')}</p>
      </section>
    </MainLayout>
  )
}
