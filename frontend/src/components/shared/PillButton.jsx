import { Link } from 'react-router-dom'
import styles from './PillButton.module.css'

const isInternalHref = (href) => typeof href === 'string' && href.startsWith('/') && !href.startsWith('//')

export default function PillButton({
  label,
  href,
  onClick,
  variant = 'dark',
  size = 'createDesktop',
  className = '',
  type = 'button',
  icon = 'plus',
  iconSrc = '',
}) {
  const classes = [
    styles.pill,
    styles[variant],
    styles[size],
    className,
  ]
    .filter(Boolean)
    .join(' ')

  const content = (
    <>
      <span className={styles.label}>{label}</span>
      <span className={styles.iconWrap} aria-hidden="true">
        {iconSrc ? (
          <img src={iconSrc} alt="" className={styles.iconImage} />
        ) : icon === 'arrow' ? (
          <>
            <span className={styles.arrowLine} />
            <span className={`${styles.arrowLine} ${styles.arrowLineTop}`} />
            <span className={`${styles.arrowLine} ${styles.arrowLineBottom}`} />
          </>
        ) : (
          <>
            <span className={styles.plusLine} />
            <span className={`${styles.plusLine} ${styles.plusLineVertical}`} />
          </>
        )}
      </span>
    </>
  )

  if (href) {
    if (isInternalHref(href)) {
      return (
        <Link to={href} className={classes}>
          {content}
        </Link>
      )
    }

    return (
      <a href={href} className={classes}>
        {content}
      </a>
    )
  }

  return (
    <button type={type} className={classes} onClick={onClick}>
      {content}
    </button>
  )
}