import styles from './Header.module.css'

export default function HeaderMenuButton({ onClick, ariaLabel = 'Apri menu' }) {
  return (
    <button
      type="button"
      className={styles.menuButton}
      onClick={onClick}
      aria-label={ariaLabel}
    >
      <svg
        viewBox="0 0 36 24"
        fill="none"
        className={styles.menuIcon}
        aria-hidden="true"
      >
        <path d="M0 2H36" stroke="currentColor" strokeWidth="3" />
        <path d="M0 12H36" stroke="currentColor" strokeWidth="3" />
        <path d="M0 22H36" stroke="currentColor" strokeWidth="3" />
      </svg>
    </button>
  )
}
