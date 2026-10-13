import { ownerProfileFromUser } from '../../app/api/profile.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import ProfileCard from './ProfileCard.jsx'
import styles from './ProfilePage.module.css'

export default function ProfilePage() {
  const { t } = useI18n()
  const { user } = useAuth()
  const profile = ownerProfileFromUser(user)

  return (
    <MainLayout>
      <section className={styles.page}>
        {profile ? (
          <ProfileCard profile={profile} t={t} showOwnerActions />
        ) : (
          <p className={styles.status}>{t('profile.page.load_error')}</p>
        )}
      </section>
    </MainLayout>
  )
}
