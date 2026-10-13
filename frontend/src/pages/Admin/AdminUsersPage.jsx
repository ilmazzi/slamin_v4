import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listAdminUsers, updateAdminUser } from '../../app/api/admin.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './AdminUsersPage.module.css'

const initialFilters = {
  search: '',
  status: '',
  account_type: '',
  verified: '',
  role: '',
}

function editableUser(user) {
  return {
    status: user.status || 'active',
    account_type: user.account_type || 'audience',
    email_verified: Boolean(user.email_verified),
    roles: Array.isArray(user.roles) ? user.roles : [],
  }
}

export default function AdminUsersPage() {
  const { token, user: sessionUser } = useAuth()
  const [filters, setFilters] = useState(initialFilters)
  const [appliedFilters, setAppliedFilters] = useState(initialFilters)
  const [users, setUsers] = useState([])
  const [roles, setRoles] = useState([])
  const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 })
  const [editing, setEditing] = useState({})
  const [isLoading, setIsLoading] = useState(true)
  const [savingId, setSavingId] = useState(null)
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')

  const load = useCallback(async (page = 1, signal) => {
    setIsLoading(true)
    setError('')
    try {
      const result = await listAdminUsers({ ...appliedFilters, page, per_page: 20 }, { token, signal })
      setUsers(result.users)
      setRoles(result.roles)
      setMeta(result.meta)
      setEditing(Object.fromEntries(result.users.map((item) => [item.id, editableUser(item)])))
    } catch (loadError) {
      if (loadError?.name !== 'AbortError') setError(loadError?.message || 'Impossibile caricare gli utenti.')
    } finally {
      setIsLoading(false)
    }
  }, [appliedFilters, token])

  useEffect(() => {
    const controller = new AbortController()
    load(1, controller.signal)
    return () => controller.abort()
  }, [load])

  const updateDraft = (userId, key, value) => {
    setEditing((current) => ({
      ...current,
      [userId]: { ...current[userId], [key]: value },
    }))
  }

  const toggleRole = (userId, role) => {
    const current = editing[userId]?.roles || []
    updateDraft(userId, 'roles', current.includes(role) ? current.filter((item) => item !== role) : [...current, role])
  }

  const save = async (userId) => {
    setSavingId(userId)
    setError('')
    setMessage('')
    try {
      const updated = await updateAdminUser(userId, editing[userId], { token })
      setUsers((current) => current.map((item) => (item.id === userId ? updated : item)))
      setEditing((current) => ({ ...current, [userId]: editableUser(updated) }))
      setMessage(`Utente ${updated.email} aggiornato.`)
    } catch (saveError) {
      setError(saveError?.message || 'Impossibile aggiornare l’utente.')
    } finally {
      setSavingId(null)
    }
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <p className={styles.back}><Link to="/admin">← Torna all’amministrazione</Link></p>
          <h1>Gestione utenti</h1>
          <p>Cerca utenti e gestisci verifica email, stato, tipo account e ruoli.</p>
        </header>

        <form
          className={styles.filters}
          onSubmit={(event) => {
            event.preventDefault()
            setAppliedFilters(filters)
          }}
        >
          <input
            className={styles.search}
            value={filters.search}
            onChange={(event) => setFilters({ ...filters, search: event.target.value })}
            placeholder="Email, nome o nickname"
          />
          <select value={filters.status} onChange={(event) => setFilters({ ...filters, status: event.target.value })}>
            <option value="">Tutti gli stati</option>
            <option value="active">Attivo</option>
            <option value="suspended">Sospeso</option>
            <option value="banned">Bannato</option>
          </select>
          <select value={filters.account_type} onChange={(event) => setFilters({ ...filters, account_type: event.target.value })}>
            <option value="">Tutti i tipi</option>
            <option value="audience">Audience</option>
            <option value="poet_organizer">Poeta/organizzatore</option>
          </select>
          <select value={filters.verified} onChange={(event) => setFilters({ ...filters, verified: event.target.value })}>
            <option value="">Qualsiasi verifica</option>
            <option value="1">Email verificata</option>
            <option value="0">Email non verificata</option>
          </select>
          <select value={filters.role} onChange={(event) => setFilters({ ...filters, role: event.target.value })}>
            <option value="">Tutti i ruoli</option>
            {roles.map((role) => <option key={role.name} value={role.name}>{role.label || role.name}</option>)}
          </select>
          <button type="submit">Cerca</button>
        </form>

        <div className={styles.summary}>{meta.total || 0} utenti</div>
        {error ? <p className={styles.error}>{error}</p> : null}
        {message ? <p className={styles.success}>{message}</p> : null}
        {isLoading ? <p>Caricamento...</p> : null}

        {!isLoading ? (
          <div className={styles.list}>
            {users.map((item) => {
              const draft = editing[item.id] || editableUser(item)
              const isSelf = String(sessionUser?.id) === String(item.id)
                || (sessionUser?.public_id && sessionUser.public_id === item.public_id)
              return (
                <article className={styles.card} key={item.id}>
                  <div className={styles.identity}>
                    <div>
                      <h2>{item.profile?.display_name || item.profile?.nickname || item.email}</h2>
                      <p>{item.email}{isSelf ? ' · il tuo account' : ''}</p>
                    </div>
                    <span className={styles.created}>
                      Registrato {item.created_at ? new Date(item.created_at).toLocaleDateString('it-IT') : '—'}
                    </span>
                  </div>

                  <div className={styles.controls}>
                    <label>
                      <span>Stato</span>
                      <select
                        value={draft.status}
                        disabled={isSelf}
                        onChange={(event) => updateDraft(item.id, 'status', event.target.value)}
                      >
                        <option value="active">Attivo</option>
                        <option value="suspended">Sospeso</option>
                        <option value="banned">Bannato</option>
                      </select>
                    </label>
                    <label>
                      <span>Tipo account</span>
                      <select value={draft.account_type} onChange={(event) => updateDraft(item.id, 'account_type', event.target.value)}>
                        <option value="audience">Audience</option>
                        <option value="poet_organizer">Poeta/organizzatore</option>
                      </select>
                    </label>
                    <label className={styles.checkbox}>
                      <input
                        type="checkbox"
                        checked={draft.email_verified}
                        disabled={isSelf}
                        onChange={(event) => updateDraft(item.id, 'email_verified', event.target.checked)}
                      />
                      <span>Email verificata</span>
                    </label>
                  </div>

                  <fieldset className={styles.roles}>
                    <legend>Ruoli</legend>
                    {roles.map((role) => (
                      <label key={role.name}>
                        <input
                          type="checkbox"
                          checked={draft.roles.includes(role.name)}
                          disabled={isSelf && role.name === 'admin'}
                          onChange={() => toggleRole(item.id, role.name)}
                        />
                        <span>{role.label || role.name}</span>
                      </label>
                    ))}
                    {roles.length === 0 ? <span>Nessun ruolo configurato.</span> : null}
                  </fieldset>

                  <div className={styles.actions}>
                    <button type="button" disabled={savingId === item.id} onClick={() => save(item.id)}>
                      {savingId === item.id ? 'Salvataggio...' : 'Salva'}
                    </button>
                  </div>
                </article>
              )
            })}
            {users.length === 0 ? <p>Nessun utente trovato.</p> : null}
          </div>
        ) : null}

        <nav className={styles.pagination} aria-label="Paginazione utenti">
          <button type="button" disabled={meta.current_page <= 1 || isLoading} onClick={() => load(meta.current_page - 1)}>
            ← Precedente
          </button>
          <span>Pagina {meta.current_page || 1} di {meta.last_page || 1}</span>
          <button type="button" disabled={meta.current_page >= meta.last_page || isLoading} onClick={() => load(meta.current_page + 1)}>
            Successiva →
          </button>
        </nav>
      </section>
    </MainLayout>
  )
}
