import HeaderMenuButton from './HeaderMenuButton.jsx'
import HeaderLanguageSwitcher from './HeaderLanguageSwitcher.jsx'
import styles from './Header.module.css'

export default function HeaderGuestActions({
  locale = 'it',
  availableLocales = [],
  loginHref = '/login',
  registerHref = '/register',
  onLanguageChange,
  onMenuClick,
  menuAriaLabel = 'Menu',
  t,
}) {
  return (
    <div className={styles.guestActions}>
      <HeaderMenuButton onClick={onMenuClick} ariaLabel={menuAriaLabel} />

      <HeaderLanguageSwitcher
        locale={locale}
        availableLocales={availableLocales}
        onChange={onLanguageChange}
        ariaLabel={t ? t('home.header.language_aria') : 'Select language'}
      />

      <a href={loginHref} className={`${styles.headerButton} ${styles.loginButton}`}>
        {t ? t('common.login') : 'Accedi'}
      </a>

      <a
        href={registerHref}
        className={`${styles.headerButton} ${styles.registerButton}`}
      >
        {t ? t('register.register') : 'Registrati'}
      </a>
    </div>
  )
}
