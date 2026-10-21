import styles from './CreateFormHeader.module.css'

export default function CreateFormHeader({ title, lead }) {
  const hasLead = Boolean(String(lead ?? '').trim())

  return (
    <header className={styles.header}>
      <h1 className={styles.title}>{title}</h1>
      {hasLead ? <p className={styles.lead}>{lead}</p> : null}
    </header>
  )
}
