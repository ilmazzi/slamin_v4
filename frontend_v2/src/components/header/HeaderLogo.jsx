import logoWhite from '../../assets/slamin-logo-white.svg'
import logoBlack from '../../assets/slamin-logo-black.svg'
import logoPageWhite from '../../assets/slamin-logo-page-white.svg'
import styles from './Header.module.css'

const DEFAULT_LOGO_LABEL = 'Slam In'
export default function HeaderLogo({
  href = '/',
  label = DEFAULT_LOGO_LABEL,
  className,
  logoTone = 'light',
}) {
  const resolvedLogoSrc = logoTone === 'dark' ? logoPageWhite : logoWhite
  const linkClassName = className
    ? `${styles.logoLink} ${className}`
    : styles.logoLink

  return (
    <a href={href} className={linkClassName} aria-label={label}>
      <img src={resolvedLogoSrc} alt="" className={styles.logoImage} aria-hidden="true" />
    </a>
  )
}
