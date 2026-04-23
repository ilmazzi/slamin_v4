import styles from './SearchBar.module.css'

export default function SearchBar({ placeholder }) {
  return (
    <form className={styles.form} onSubmit={(event) => event.preventDefault()}>
      <label className={styles.label}>
        <span className={styles.srOnly}>{placeholder}</span>
        <input
          type="search"
          className={styles.input}
          placeholder={placeholder}
          aria-label={placeholder}
        />
      </label>
      <button type="submit" className={styles.button} aria-label={placeholder}>
        <span className={styles.icon} aria-hidden="true" />
      </button>
    </form>
  )
}
