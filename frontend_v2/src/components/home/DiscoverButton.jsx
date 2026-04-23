import styles from './DiscoverButton.module.css'

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

  return (
    <a href={href} className={className}>
      <span className={styles.label}>{children}</span>
      <span className={styles.arrow} aria-hidden="true">
        &gt;
      </span>
    </a>
  )
}
