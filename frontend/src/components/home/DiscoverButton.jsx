import { Link } from 'react-router-dom'
import styles from './DiscoverButton.module.css'

const isInternalHref = (href) => typeof href === 'string' && href.startsWith('/') && !href.startsWith('//')

export default function DiscoverButton({
  href = '#',
  children,
  variant = 'dark',
  size = 'desktop',
}) {
  const className = [
    styles.button,
    variant === 'light' ? styles.light : styles.dark,
    size === 'mobile' ? styles.mobile : styles.desktop,
  ].join(' ')

  if (isInternalHref(href)) {
    return (
      <Link to={href} className={className}>
        <span className={styles.label}>{children}</span>
        <span className={styles.arrow} aria-hidden="true">
          &gt;
        </span>
      </Link>
    )
  }

  return (
    <a href={href} className={className}>
      <span className={styles.label}>{children}</span>
      <span className={styles.arrow} aria-hidden="true">
        &gt;
      </span>
    </a>
  )
}
