import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext.jsx'
import { resolvePublicUrl } from '../../app/api/url.js'
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
  quickActionsTitle = 'Azioni rapide',
  writePoemLabel = 'Scrivi poesia',
  createEventLabel = 'Organizza evento',
  uploadVideoLabel = 'Carica video',
  publishArticleLabel = 'Pubblica articolo',
  notificationsAriaLabel = 'Notifiche',
  myProfileLabel = 'Il mio profilo',
  editProfileLabel = 'Modifica profilo',
  logoutLabel = 'Logout',
}) {
  const { logout } = useAuth()
  const navigate = useNavigate()
  const [isQuickActionsOpen, setIsQuickActionsOpen] = useState(false)
  const [isProfileMenuOpen, setIsProfileMenuOpen] = useState(false)
  const [quickActionsPosition, setQuickActionsPosition] = useState({ top: 0, left: 0 })
  const shellRef = useRef(null)
  const mainMenuButtonRef = useRef(null)
  const quickActionsButtonRef = useRef(null)
  const quickActionsMenuRef = useRef(null)
  const userName = user?.name ?? fallbackUserName
  const userEmail = user?.email ?? ''
  const avatar = resolvePublicUrl(user?.profile_photo_url)

  useEffect(() => {
    if (!isQuickActionsOpen && !isProfileMenuOpen) {
      return undefined
    }

    const handleClickOutside = (event) => {
      if (!shellRef.current?.contains(event.target)) {
        setIsQuickActionsOpen(false)
        setIsProfileMenuOpen(false)
      }
    }

    const handleEscape = (event) => {
      if (event.key === 'Escape') {
        setIsQuickActionsOpen(false)
        setIsProfileMenuOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    window.addEventListener('keydown', handleEscape)

    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
      window.removeEventListener('keydown', handleEscape)
    }
  }, [isQuickActionsOpen, isProfileMenuOpen])

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

        <button
          type="button"
          className={styles.menuButton}
          ref={mainMenuButtonRef}
          onClick={() => {
            onMainMenuToggle?.(mainMenuButtonRef.current)
            setIsQuickActionsOpen(false)
            setIsProfileMenuOpen(false)
          }}
          aria-label={menuAriaLabel}
        >
          <svg viewBox="0 0 36 24" fill="none" className={styles.desktopBurgerIcon} aria-hidden="true">
            <path d="M0 2H36" stroke="currentColor" strokeWidth="3" />
            <path d="M0 12H36" stroke="currentColor" strokeWidth="3" />
            <path d="M0 22H36" stroke="currentColor" strokeWidth="3" />
          </svg>
        </button>

        <button
          type="button"
          ref={quickActionsButtonRef}
          className={`${styles.menuButton} ${styles.quickActionsButton}`}
          onClick={() => {
            setIsQuickActionsOpen((current) => !current)
            setIsProfileMenuOpen(false)
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

        <button type="button" className={styles.menuButton} aria-label={notificationsAriaLabel}>
          <svg viewBox="0 0 24 24" fill="none" className={styles.menuIcon} aria-hidden="true">
            <path d="M12 3a5 5 0 0 0-5 5v2.5c0 .7-.2 1.4-.6 2L5 15h14l-1.4-2.5a4.1 4.1 0 0 1-.6-2V8a5 5 0 0 0-5-5Z" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M10 18a2 2 0 0 0 4 0" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
          </svg>
        </button>

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
