import { Link } from 'react-router-dom'
import logoWhite from '../../assets/slamin-logo-white.svg'
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
    <Link to={href} className={linkClassName} aria-label={label}>
      <img src={resolvedLogoSrc} alt="" className={styles.logoImage} aria-hidden="true" />
    </Link>
  )
}
