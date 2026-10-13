import { Link } from 'react-router-dom'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './AdminHomePage.module.css'

export default function AdminHomePage() {
  const { t } = useI18n()
  const { hasAnyRole } = useAuth()
  const canManageTranslations = hasAnyRole(['admin', 'translator'])
  const canModerate = hasAnyRole(['admin', 'moderator'])
  const canManageSettings = hasAnyRole(['admin'])

  const cards = [
    canManageTranslations ? {
      id: 'translations',
      href: '/admin/translations',
      title: t('admin.hub.translations_title'),
      description: t('admin.hub.translations_description'),
      ready: true,
    } : null,
    canModerate ? {
      id: 'moderation',
      href: '/moderation/dashboard',
      title: t('admin.hub.moderation_title'),
      description: t('admin.hub.moderation_description'),
      ready: true,
    } : null,
    canManageSettings ? {
      id: 'settings',
      href: '/admin/settings',
      title: t('admin.hub.settings_title'),
      description: t('admin.hub.settings_description'),
      ready: true,
    } : null,
    canManageSettings ? {
      id: 'users',
      href: '/admin/users',
      title: t('admin.hub.users_title'),
      description: t('admin.hub.users_description'),
      ready: true,
    } : null,
    canManageSettings ? {
      id: 'help',
      href: '/admin/help',
      title: t('admin.hub.help_title'),
      description: t('admin.hub.help_description'),
      ready: true,
    } : null,
  ].filter(Boolean)

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <h1 className={styles.title}>{t('admin.hub.title')}</h1>
          <p className={styles.subtitle}>{t('admin.hub.subtitle')}</p>
        </header>

        <div className={styles.grid}>
          {cards.map((card) => (
            <Link key={card.id} to={card.href} className={styles.card}>
              <span className={card.ready ? styles.badgeReady : styles.badgeSoon}>
                {card.ready ? t('admin.hub.ready') : t('admin.hub.coming_soon')}
              </span>
              <h2>{card.title}</h2>
              <p>{card.description}</p>
              <span className={styles.open}>{t('admin.hub.open')}</span>
            </Link>
          ))}
        </div>
      </section>
    </MainLayout>
  )
}
