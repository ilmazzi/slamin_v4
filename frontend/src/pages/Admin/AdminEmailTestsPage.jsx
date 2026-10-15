import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listAdminUsers, sendTestNotificationEmails } from '../../app/api/admin.js'
import { getNotificationEmailPreferences } from '../../app/api/notifications.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import shared from './AdminUsersPage.module.css'
import styles from './AdminEmailTestsPage.module.css'

const MAX_RECIPIENTS = 10
const DELIVERING_MAILERS = ['smtp', 'ses', 'postmark', 'resend', 'mailgun', 'sendmail', 'failover', 'roundrobin']

function userLabel(user) {
  return user.profile?.display_name || user.profile?.nickname || user.email
}

export default function AdminEmailTestsPage() {
  const { token } = useAuth()
  const { t } = useI18n()
  const [categories, setCategories] = useState([])
  const [category, setCategory] = useState('')
  const [search, setSearch] = useState('')
  const [matches, setMatches] = useState([])
  const [isSearching, setIsSearching] = useState(false)
  const [recipients, setRecipients] = useState([])
  const [isSending, setIsSending] = useState(false)
  const [report, setReport] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    const controller = new AbortController()

    getNotificationEmailPreferences({ token, signal: controller.signal })
      .then((preferences) => {
        const keys = Object.keys(preferences)
        setCategories(keys)
        setCategory((current) => current || keys[0] || '')
      })
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') setError(loadError?.message || 'Impossibile caricare i tipi di email.')
      })

    return () => controller.abort()
  }, [token])

  const findUsers = async (event) => {
    event.preventDefault()
    setIsSearching(true)
    setError('')
    try {
      const result = await listAdminUsers({ search: search.trim(), per_page: 10 }, { token })
      setMatches(result.users)
    } catch (searchError) {
      setError(searchError?.message || 'Impossibile cercare gli utenti.')
    } finally {
      setIsSearching(false)
    }
  }

  const addRecipient = (user) => {
    setRecipients((current) => (
      current.some((item) => item.public_id === user.public_id) || current.length >= MAX_RECIPIENTS
        ? current
        : [...current, user]
    ))
  }

  const removeRecipient = (publicId) => {
    setRecipients((current) => current.filter((item) => item.public_id !== publicId))
  }

  const send = async () => {
    setIsSending(true)
    setError('')
    setReport(null)
    try {
      setReport(await sendTestNotificationEmails(category, recipients.map((item) => item.public_id), { token }))
    } catch (sendError) {
      setError(sendError?.message || 'Impossibile inviare le email di prova.')
    } finally {
      setIsSending(false)
    }
  }

  const isFull = recipients.length >= MAX_RECIPIENTS

  return (
    <MainLayout>
      <section className={shared.page}>
        <header className={shared.header}>
          <p className={shared.back}><Link to="/admin">← Torna all’amministrazione</Link></p>
          <h1>Test email notifiche</h1>
          <p>
            Scegli il tipo di email e fino a {MAX_RECIPIENTS} destinatari. L’invio è immediato e ignora le preferenze
            dell’utente. Se l’utente ha già dati veri (notifiche, messaggi, mi piace) la mail usa quelli, altrimenti un esempio.
          </p>
        </header>

        {error ? <p className={shared.error}>{error}</p> : null}

        <div className={shared.list}>
          <div className={shared.card}>
            <label className={styles.field}>
              <span>Tipo di email</span>
              <select value={category} onChange={(event) => {
                  setCategory(event.target.value)
                  setReport(null)
                }} disabled={categories.length === 0}>
                {categories.map((key) => (
                  <option key={key} value={key}>{t(`notifications.preferences.categories.${key}.label`)}</option>
                ))}
              </select>
            </label>
            {category ? <p className={styles.hint}>{t(`notifications.preferences.categories.${category}.description`)}</p> : null}
          </div>

          <div className={shared.card}>
            <form className={styles.searchRow} onSubmit={findUsers}>
              <input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder="Cerca per email, nome o nickname"
                aria-label="Cerca destinatari"
              />
              <button type="submit" disabled={isSearching}>{isSearching ? 'Ricerca...' : 'Cerca'}</button>
            </form>

            {matches.length > 0 ? (
              <ul className={styles.matches}>
                {matches.map((user) => {
                  const isAdded = recipients.some((item) => item.public_id === user.public_id)
                  return (
                    <li key={user.public_id}>
                      <span>
                        <strong>{userLabel(user)}</strong>
                        <span className={styles.hint}>{user.email}{user.email_verified ? '' : ' · email non verificata'}</span>
                      </span>
                      <button type="button" disabled={isAdded || isFull} onClick={() => addRecipient(user)}>
                        {isAdded ? 'Aggiunto' : 'Aggiungi'}
                      </button>
                    </li>
                  )
                })}
              </ul>
            ) : null}

            <div>
              <p className={styles.label}>Destinatari ({recipients.length}/{MAX_RECIPIENTS})</p>
              {recipients.length === 0 ? <p className={styles.hint}>Nessun destinatario: cerca e aggiungi gli utenti.</p> : null}
              <ul className={styles.chips}>
                {recipients.map((user) => (
                  <li key={user.public_id}>
                    <span>{user.email}</span>
                    <button type="button" aria-label={`Rimuovi ${user.email}`} onClick={() => removeRecipient(user.public_id)}>×</button>
                  </li>
                ))}
              </ul>
            </div>

            <div className={shared.actions}>
              <button type="button" disabled={isSending || !category || recipients.length === 0} onClick={send}>
                {isSending ? 'Invio in corso...' : 'Invia email di prova'}
              </button>
            </div>
          </div>

          {report ? (
            <div className={shared.card}>
              <h2 className={styles.reportTitle}>Esito</h2>
              {!DELIVERING_MAILERS.includes(report.mailer) ? (
                <p className={shared.error}>
                  Il mailer attivo è “{report.mailer}”: le email non arrivano nelle caselle. Attiva l’SMTP nelle impostazioni.
                </p>
              ) : null}
              <ul className={styles.results}>
                {report.results.map((result) => (
                  <li key={result.public_id} className={result.sent ? styles.sent : styles.failed}>
                    <strong>{result.email}</strong>
                    <span>
                      {result.sent
                        ? `Inviata · ${result.uses_real_data ? 'con i suoi dati reali' : 'con dati di esempio'}`
                        : `Errore: ${result.error}`}
                      {result.opted_out ? ' · ha disattivato questa email nelle preferenze' : ''}
                    </span>
                  </li>
                ))}
              </ul>
            </div>
          ) : null}
        </div>
      </section>
    </MainLayout>
  )
}
