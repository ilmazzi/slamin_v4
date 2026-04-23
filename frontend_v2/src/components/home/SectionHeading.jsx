import styles from './SectionHeading.module.css'

export default function SectionHeading({
  accent,
  title,
  subtitle,
  align = 'left',
  tone = 'dark',
}) {
  const className = [
    styles.heading,
    align === 'center' ? styles.center : styles.left,
    tone === 'light' ? styles.light : styles.dark,
  ].join(' ')

  return (
    <div className={className}>
      <h2 className={styles.accent}>{accent}</h2>
      {title ? <p className={styles.title}>{title}</p> : null}
      {subtitle ? <p className={styles.subtitle}>{subtitle}</p> : null}
    </div>
  )
}
