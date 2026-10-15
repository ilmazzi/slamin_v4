import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../../app/hooks/useAuth.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import NotificationBell from '../notifications/NotificationBell.jsx'
import HeaderLanguageSwitcher from './HeaderLanguageSwitcher.jsx'
import styles from './Header.module.css'

export default function HeaderAuthActions({
  user,
  locale = 'it',
  availableLocales = [],
  onLanguageChange,
  languageAriaLabel = 'Select language',
  onMainMenuToggle,
  onMainMenuClose,
  menuAriaLabel = 'Apri menu',
  fallbackUserName = 'Nome Cognome',
  dashboardLabel = 'Dashboard',
  showAdmin = false,
  adminHref = '/admin',
  adminLabel = 'Amministrazione',
  adminMenuLabel = 'Amministrazione',
  quickActionsTitle = 'Azioni rapide',
  writePoemLabel = 'Scrivi poesia',
  createEventLabel = 'Organizza evento',
  uploadVideoLabel = 'Carica video',
  publishArticleLabel = 'Pubblica articolo',
  myProfileLabel = 'Il mio profilo',
  editProfileLabel = 'Modifica profilo',
  chatLabel = 'Chat',
  logoutLabel = 'Logout',
}) {
  const { logout } = useAuth()
  const canPublish = user?.account_type === 'poet_organizer'
  const navigate = useNavigate()
  const [isQuickActionsOpen, setIsQuickActionsOpen] = useState(false)
  const [isProfileMenuOpen, setIsProfileMenuOpen] = useState(false)
  const [isNotificationsOpen, setIsNotificationsOpen] = useState(false)
  const [quickActionsPosition, setQuickActionsPosition] = useState({ top: 0, left: 0 })
  const shellRef = useRef(null)
  const mainMenuButtonRef = useRef(null)
  const quickActionsButtonRef = useRef(null)
  const quickActionsMenuRef = useRef(null)
  const userName = user?.name ?? fallbackUserName
  const userEmail = user?.email ?? ''
  const avatar = resolvePublicUrl(user?.profile_photo_url)

  useEffect(() => {
    if (!isQuickActionsOpen && !isProfileMenuOpen && !isNotificationsOpen) {
      return undefined
    }

    const handleClickOutside = (event) => {
      if (!shellRef.current?.contains(event.target)) {
        setIsQuickActionsOpen(false)
        setIsProfileMenuOpen(false)
        setIsNotificationsOpen(false)
      }
    }

    const handleEscape = (event) => {
      if (event.key === 'Escape') {
        setIsQuickActionsOpen(false)
        setIsProfileMenuOpen(false)
        setIsNotificationsOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    window.addEventListener('keydown', handleEscape)

    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
      window.removeEventListener('keydown', handleEscape)
    }
  }, [isQuickActionsOpen, isProfileMenuOpen, isNotificationsOpen])

  useEffect(() => {
    if (!isQuickActionsOpen) {
      return undefined
    }

    const updateQuickActionsPosition = () => {
      if (!shellRef.current || !quickActionsButtonRef.current) {
        return
      }

      const shellRect = shellRef.current.getBoundingClientRect()
      const buttonRect = quickActionsButtonRef.current.getBoundingClientRect()
      const menuWidth = quickActionsMenuRef.current?.offsetWidth ?? 260
      const preferredLeft = Math.max(0, buttonRect.left - shellRect.left)
      const maxLeft = Math.max(0, shellRect.width - menuWidth)
      const left = Math.min(preferredLeft, maxLeft)
      const top = (buttonRect.bottom - shellRect.top) + 10
      setQuickActionsPosition({ top, left })
    }

    updateQuickActionsPosition()
    window.addEventListener('resize', updateQuickActionsPosition)

    return () => {
      window.removeEventListener('resize', updateQuickActionsPosition)
    }
  }, [isQuickActionsOpen])

  const handleLogout = async () => {
    setIsQuickActionsOpen(false)
    setIsProfileMenuOpen(false)
    setIsNotificationsOpen(false)
    await logout()
    navigate('/login', { replace: true })
  }

  return (
    <div className={styles.authShell} ref={shellRef}>
      <div className={styles.authActions}>
        <Link to="/dashboard" className={styles.dashboardButton} aria-label={dashboardLabel}>
          <svg viewBox="0 0 24 24" fill="none" className={styles.dashboardButtonIcon} aria-hidden="true">
            <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
            <rect x="14" y="14" width="7" height="7" rx="1.5" stroke="currentColor" strokeWidth="1.8" />
          </svg>
        </Link>

        {showAdmin ? (
          <Link to={adminHref} className={styles.adminButton}>
            {adminLabel}
          </Link>
        ) : null}

        <button
          type="button"
          className={styles.menuButton}
          ref={mainMenuButtonRef}
          onClick={() => {
            onMainMenuToggle?.(mainMenuButtonRef.current)
            setIsQuickActionsOpen(false)
            setIsProfileMenuOpen(false)
            setIsNotificationsOpen(false)
          }}
          aria-label={menuAriaLabel}
        >
          <svg viewBox="0 0 36 24" fill="none" className={styles.desktopBurgerIcon} aria-hidden="true">
            <path d="M0 2H36" stroke="currentColor" strokeWidth="3" />
            <path d="M0 12H36" stroke="currentColor" strokeWidth="3" />
            <path d="M0 22H36" stroke="currentColor" strokeWidth="3" />
          </svg>
        </button>

        {canPublish ? (
          <button
            type="button"
            ref={quickActionsButtonRef}
            className={`${styles.menuButton} ${styles.quickActionsButton}`}
            onClick={() => {
              setIsQuickActionsOpen((current) => !current)
              setIsProfileMenuOpen(false)
              setIsNotificationsOpen(false)
              onMainMenuClose?.()
            }}
            aria-label={quickActionsTitle}
            aria-expanded={isQuickActionsOpen}
            aria-haspopup="menu"
          >
            <svg viewBox="0 0 24 24" fill="none" className={styles.menuIcon} aria-hidden="true">
              <path d="M13.5 2.5L4 14h6l-1 7.5L20 10h-6l-.5-7.5z" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" />
            </svg>
          </button>
        ) : null}

        <NotificationBell
          open={isNotificationsOpen}
          onToggle={() => {
            setIsNotificationsOpen((current) => !current)
            setIsQuickActionsOpen(false)
            setIsProfileMenuOpen(false)
            onMainMenuClose?.()
          }}
          onClose={() => setIsNotificationsOpen(false)}
          buttonClassName={styles.menuButton}
          iconClassName={styles.menuIcon}
        />

        <HeaderLanguageSwitcher
          locale={locale}
          availableLocales={availableLocales}
          onChange={onLanguageChange}
          ariaLabel={languageAriaLabel}
        />

        <button
          type="button"
          className={`${styles.userPill} ${styles.userPillButton}`}
          onClick={() => {
            setIsProfileMenuOpen((current) => !current)
            setIsQuickActionsOpen(false)
            setIsNotificationsOpen(false)
            onMainMenuClose?.()
          }}
          aria-label={menuAriaLabel}
          aria-expanded={isProfileMenuOpen}
          aria-haspopup="menu"
        >
          {avatar ? (
            <img src={avatar} alt="" className={styles.avatar} />
          ) : (
            <span className={styles.avatar} aria-hidden="true" />
          )}
          <span className={styles.userName}>{userName}</span>
        </button>
      </div>

      {isQuickActionsOpen ? (
        <div
          ref={quickActionsMenuRef}
          className={styles.authDropdown}
          role="menu"
          aria-label={quickActionsTitle}
          style={{
            top: `${quickActionsPosition.top}px`,
            left: `${quickActionsPosition.left}px`,
            right: 'auto',
          }}
        >
          <p className={styles.authDropdownTitle}>{quickActionsTitle}</p>
          {canPublish ? (
            <Link
              to="/poems/create"
              className={styles.authMenuLink}
              data-tone="poems"
              role="menuitem"
              onClick={() => setIsQuickActionsOpen(false)}
            >
              <span className={styles.authMenuArrow} aria-hidden="true">›</span>
              <span>{writePoemLabel}</span>
            </Link>
          ) : null}
          {canPublish ? (
            <Link
              to="/events/create"
              className={styles.authMenuLink}
              data-tone="event"
              role="menuitem"
              onClick={() => setIsQuickActionsOpen(false)}
            >
              <span className={styles.authMenuArrow} aria-hidden="true">›</span>
              <span>{createEventLabel}</span>
            </Link>
          ) : null}
          {canPublish ? (
            <Link
              to="/media/upload"
              className={styles.authMenuLink}
              data-tone="media"
              role="menuitem"
              onClick={() => setIsQuickActionsOpen(false)}
            >
              <span className={styles.authMenuArrow} aria-hidden="true">›</span>
              <span>{uploadVideoLabel}</span>
            </Link>
          ) : null}
          {canPublish ? (
            <Link
              to="/articles/create"
              className={styles.authMenuLink}
              data-tone="articles"
              role="menuitem"
              onClick={() => setIsQuickActionsOpen(false)}
            >
              <span className={styles.authMenuArrow} aria-hidden="true">›</span>
              <span>{publishArticleLabel}</span>
            </Link>
          ) : null}
        </div>
      ) : null}

      {isProfileMenuOpen ? (
        <div className={styles.profileDropdown} role="menu" aria-label={menuAriaLabel}>
          <div className={styles.profileDropdownHead}>
            <p className={styles.profileDropdownName}>{userName}</p>
            {userEmail ? <p className={styles.profileDropdownEmail}>{userEmail}</p> : null}
          </div>
          <Link
            to="/profile"
            className={styles.authMenuLink}
            data-tone="red"
            role="menuitem"
            onClick={() => setIsProfileMenuOpen(false)}
          >
            <span className={styles.authMenuArrow} aria-hidden="true">›</span>
            <span>{myProfileLabel}</span>
          </Link>
          <Link
            to="/profile/edit"
            className={styles.authMenuLink}
            data-tone="red"
            role="menuitem"
            onClick={() => setIsProfileMenuOpen(false)}
          >
            <span className={styles.authMenuArrow} aria-hidden="true">›</span>
            <span>{editProfileLabel}</span>
          </Link>
          <Link
            to="/chat"
            className={styles.authMenuLink}
            data-tone="red"
            role="menuitem"
            onClick={() => setIsProfileMenuOpen(false)}
          >
            <span className={styles.authMenuArrow} aria-hidden="true">›</span>
            <span>{chatLabel}</span>
          </Link>
          {showAdmin ? (
            <Link
              to={adminHref}
              className={styles.authMenuLink}
              data-tone="red"
              role="menuitem"
              onClick={() => setIsProfileMenuOpen(false)}
            >
              <span className={styles.authMenuArrow} aria-hidden="true">›</span>
              <span>{adminMenuLabel}</span>
            </Link>
          ) : null}
          <button
            type="button"
            className={`${styles.authMenuButton} ${styles.authMenuButtonDanger}`}
            data-tone="red"
            role="menuitem"
            onClick={handleLogout}
          >
            <span className={styles.authMenuArrow} aria-hidden="true">›</span>
            <span>{logoutLabel}</span>
          </button>
        </div>
      ) : null}
    </div>
  )
}
