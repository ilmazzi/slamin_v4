import { useEffect, useMemo, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import HeaderActions from './HeaderActions.jsx'
import HeaderLogo from './HeaderLogo.jsx'
import styles from './Header.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
const normalizeLocaleOption = (entry) => {
  if (typeof entry === 'string') {
    return { code: entry.toLowerCase(), name: entry }
  }

  return {
    code: String(entry?.code || '').toLowerCase(),
    name: String(entry?.name || entry?.code || ''),
  }
}

export default function Header({
  variant = 'guest',
  user,
  homeHref = '/',
  loginHref = '/login',
  registerHref = '/register',
  onMenuClick,
  logoTone = 'light',
}) {
    const [isCompact, setIsCompact] = useState(false)
  const { t, locale, setLocale, availableLocales } = useI18n()
  const { isAuthenticated, hasAnyRole } = useAuth()
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)
  const [mainMenuLeft, setMainMenuLeft] = useState(null)
  const [mainMenuTop, setMainMenuTop] = useState(null)
  const headerRef = useRef(null)
  const canSeeAdminMenu = hasAnyRole(['admin', 'moderator'])
  const canSeeTranslations = hasAnyRole(['admin', 'translator'])

  const discoverItems = useMemo(() => {
    const items = [
      { id: 'events', label: t('common.events'), href: '/events', tone: 'event' },
      { id: 'poems', label: t('common.poems'), href: '/poems', tone: 'poems' },
      { id: 'articles', label: t('common.articles'), href: '/articles', tone: 'articles' },
      { id: 'media', label: t('common.media'), href: '/media', tone: 'media' },
    ]

    if (isAuthenticated) {
      items.splice(2, 0, { id: 'gigs', label: t('common.gigs'), href: '/gigs', tone: 'gigs', emphasized: true })
    }

    return items
  }, [isAuthenticated, t])

  const communityItems = useMemo(
    () => [
      { id: 'faq', label: t('header.menu.community.faq'), href: '/faq' },
      { id: 'help', label: t('header.menu.community.help'), href: '/help' },
      { id: 'support', label: t('header.menu.community.support'), href: '/support', emphasized: true },
      { id: 'contacts', label: t('header.menu.community.contacts'), href: '/contacts' },
      { id: 'tutorial', label: t('header.menu.community.tutorial'), href: '/tutorial' },
    ],
    [t],
  )

  const infoItems = useMemo(
    () => [
      { id: 'about', label: t('header.menu.info.about'), href: '/about' },
      { id: 'terms', label: t('header.menu.info.terms'), href: '/terms' },
      { id: 'privacy', label: t('header.menu.info.privacy'), href: '/privacy', emphasized: true },
    ],
    [t],
  )

  const localeOptions = useMemo(() => {
    const entries = (availableLocales.length ? availableLocales : [locale]).map(normalizeLocaleOption)
    return entries.filter((entry) => entry.code)
  }, [availableLocales, locale])

  useEffect(() => {
    if (!isMobileMenuOpen) {
      return undefined
    }

    const onEscape = (event) => {
      if (event.key === 'Escape') {
        setIsMobileMenuOpen(false)
      }
    }

    const onClickOutside = (event) => {
      if (!headerRef.current?.contains(event.target)) {
        setIsMobileMenuOpen(false)
      }
    }

    window.addEventListener('keydown', onEscape)
    document.addEventListener('mousedown', onClickOutside)

    return () => {
      window.removeEventListener('keydown', onEscape)
      document.removeEventListener('mousedown', onClickOutside)
    }
  }, [isMobileMenuOpen])

  useEffect(() => {
    // Deve essere maggiore del "salto" di layout (~78px), altrimenti flappa.
    const compactEnterThreshold = 96
    const compactExitThreshold = 4

    let ticking = false

    const updateCompactState = () => {
      const y = window.scrollY || window.pageYOffset || 0

      setIsCompact((prev) => {
        if (prev) {
          return y > compactExitThreshold
        }

        return y > compactEnterThreshold
      })

      ticking = false
    }

    const handleScroll = () => {
      if (ticking) {
        return
      }

      ticking = true
      window.requestAnimationFrame(updateCompactState)
    }

    updateCompactState()
    window.addEventListener('scroll', handleScroll, { passive: true })

    return () => {
      window.removeEventListener('scroll', handleScroll)
    }
  }, [])



  const resolveAnchorElement = (anchorSource) => {
    if (!anchorSource) {
      return null
    }

    if (typeof anchorSource.getBoundingClientRect === 'function') {
      return anchorSource
    }

    if (anchorSource.currentTarget && typeof anchorSource.currentTarget.getBoundingClientRect === 'function') {
      return anchorSource.currentTarget
    }

    return null
  }

  const handleToggleMobileMenu = (anchorSource = null) => {
    const anchorElement = resolveAnchorElement(anchorSource)

    if (anchorElement && headerRef.current) {
      const headerRect = headerRef.current.getBoundingClientRect()
      const anchorRect = anchorElement.getBoundingClientRect()
      const viewportWidth = typeof window !== 'undefined' ? window.innerWidth : 360
      const panelWidth = Math.min(360, Math.max(280, viewportWidth - 20))
      const padding = 8
      const rawLeft = anchorRect.left - headerRect.left
      const clampedLeft = Math.max(padding, Math.min(rawLeft, headerRect.width - panelWidth - padding))
      const computedTop = Math.max(8, (anchorRect.bottom - headerRect.top) + 8)
      setMainMenuLeft(clampedLeft)
      setMainMenuTop(computedTop)
    }

    setIsMobileMenuOpen((current) => !current)
    onMenuClick?.()
  }

  const handleCloseMobileMenu = () => {
    setIsMobileMenuOpen(false)
  }

  return (
    <header
    className={`${styles.header} ${isCompact ? styles.headerCompact : ''}`}
    ref={headerRef}
  >
      <div className={styles.inner}>
        <HeaderLogo
          href={homeHref}
          className={styles.logoLink}
          label={t('header.logo_alt')}
          logoTone={logoTone}
        />

        <div className={styles.authActionsHost}>
          <HeaderActions
            variant={variant}
            locale={locale}
            availableLocales={availableLocales}
            user={user}
            loginHref={loginHref}
            registerHref={registerHref}
            onLanguageChange={setLocale}
            onMainMenuToggle={handleToggleMobileMenu}
            onMainMenuClose={handleCloseMobileMenu}
            onMenuClick={handleToggleMobileMenu}
            t={t}
            showAdmin={canSeeTranslations || canSeeAdminMenu}
            adminHref="/admin"
            adminLabel={t('header.admin')}
          />
        </div>
      </div>

      <nav
        className={`${styles.mobileMenuPanel} ${isMobileMenuOpen ? styles.mobileMenuPanelOpen : ''}`}
        aria-hidden={!isMobileMenuOpen}
        style={
          mainMenuLeft !== null || mainMenuTop !== null
            ? {
              '--mobile-menu-left': `${mainMenuLeft ?? 24}px`,
              '--mobile-menu-top': `${mainMenuTop ?? 72}px`,
            }
            : undefined
        }
      >
        <div className={styles.menuSection}>
          <h3 className={styles.menuSectionTitle}>{t('header.menu.discover_title')}</h3>
          <div className={styles.menuSectionList}>
            {discoverItems.map((item) => (
              <Link
                key={item.id}
                to={item.href}
                className={`${styles.menuArrowLink} ${styles.menuArrowLinkDiscover}`}
                data-tone={item.tone}
                onClick={handleCloseMobileMenu}
              >
                <span className={styles.menuArrow}>›</span>
                <span>{item.label}</span>
              </Link>
            ))}
          </div>
        </div>

        <div className={styles.menuSection}>
          <h3 className={styles.menuSectionTitle}>{t('header.menu.community_title')}</h3>
          <div className={styles.menuSectionList}>
            {communityItems.map((item) => (
              <Link
                key={item.id}
                to={item.href}
                className={`${styles.menuArrowLink} ${styles.menuArrowLinkRed}`}
                onClick={handleCloseMobileMenu}
              >
                <span className={styles.menuArrow}>›</span>
                <span>{item.label}</span>
              </Link>
            ))}
          </div>
        </div>

        <div className={styles.menuSection}>
          <h3 className={styles.menuSectionTitle}>{t('header.menu.info_title')}</h3>
          <div className={styles.menuSectionList}>
            {infoItems.map((item) => (
              <Link
                key={item.id}
                to={item.href}
                className={`${styles.menuArrowLink} ${styles.menuArrowLinkRed}`}
                onClick={handleCloseMobileMenu}
              >
                <span className={styles.menuArrow}>›</span>
                <span>{item.label}</span>
              </Link>
            ))}
          </div>
        </div>

        {canSeeTranslations || canSeeAdminMenu ? (
          <div className={styles.menuSection}>
            <h3 className={styles.menuSectionTitle}>{t('header.menu.admin_title')}</h3>
            <div className={styles.menuSectionList}>
              <Link to="/admin" className={`${styles.menuArrowLink} ${styles.menuArrowLinkRed}`} onClick={handleCloseMobileMenu}>
                <span className={styles.menuArrow}>›</span>
                <span>{t('header.admin')}</span>
              </Link>
              {canSeeTranslations ? (
                <Link to="/admin/translations" className={`${styles.menuArrowLink} ${styles.menuArrowLinkRed}`} onClick={handleCloseMobileMenu}>
                  <span className={styles.menuArrow}>›</span>
                  <span>{t('i18n.panel.title')}</span>
                </Link>
              ) : null}
              {canSeeAdminMenu ? (
                <>
                  <Link to="/moderation/dashboard" className={`${styles.menuArrowLink} ${styles.menuArrowLinkRed}`} onClick={handleCloseMobileMenu}>
                    <span className={styles.menuArrow}>›</span>
                    <span>{t('common.moderation_dashboard')}</span>
                  </Link>
                  <Link to="/admin/settings" className={`${styles.menuArrowLink} ${styles.menuArrowLinkRed}`} onClick={handleCloseMobileMenu}>
                    <span className={styles.menuArrow}>›</span>
                    <span>{t('common.admin_settings')}</span>
                  </Link>
                </>
              ) : null}
            </div>
          </div>
        ) : null}

        <div className={styles.menuBottomRow}>
          <div className={styles.menuLocalePill}>
            <span>{(locale || 'it').toLowerCase() === 'it' ? 'Italiano' : 'English'}</span>
            <span className={styles.menuLocaleChevron} aria-hidden="true">
              <svg width="16" height="10" viewBox="0 0 16 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 1.5L8 8.5L15 1.5" stroke="white" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </span>
            <select
              className={styles.menuLocaleSelect}
              value={(locale || 'it').toLowerCase()}
              onChange={(event) => setLocale(event.target.value)}
              aria-label={t('header.language_aria')}
            >
              {localeOptions.map((entry) => (
                <option key={entry.code} value={entry.code}>{entry.name}</option>
              ))}
            </select>
          </div>
          <svg className={styles.menuRedMark} width="27" height="39" viewBox="0 0 27 39" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M0 0V29.3142H12.2315L27 39V29.3142V9.68174V0H0Z" fill="#E30613" />
          </svg>
        </div>
      </nav>
    </header>
  )
}
