import { Link } from 'react-router-dom'
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
        ariaLabel={t('home.header.language_aria')}
      />

      <Link to={loginHref} className={`${styles.headerButton} ${styles.loginButton}`}>
        {t('common.login')}
      </Link>

      <Link
        to={registerHref}
        className={`${styles.headerButton} ${styles.registerButton}`}
      >
        {t('register.register')}
      </Link>
    </div>
  )
}
