import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { deleteAdminUser, listAdminUsers, updateAdminUser } from '../../app/api/admin.js'
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
  const [deletingId, setDeletingId] = useState(null)
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

  const remove = async (item) => {
    const name = item.profile?.display_name || item.profile?.nickname || item.email
    if (!window.confirm(`Eliminare ${name}? L’accesso verrà revocato e il profilo non sarà più visibile.`)) {
      return
    }

    setDeletingId(item.id)
    setError('')
    setMessage('')
    try {
      await deleteAdminUser(item.id, { token })
      setMessage(`Utente ${item.email} eliminato.`)
      const nextPage = users.length === 1 && meta.current_page > 1
        ? meta.current_page - 1
        : meta.current_page
      await load(nextPage)
    } catch (deleteError) {
      setError(deleteError?.message || 'Impossibile eliminare l’utente.')
    } finally {
      setDeletingId(null)
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
          <div className={styles.tableWrap}>
            <table className={styles.table}>
              <thead>
                <tr>
                  <th scope="col">Utente</th>
                  <th scope="col">Registrato</th>
                  <th scope="col">Stato</th>
                  <th scope="col">Tipo account</th>
                  <th scope="col" className={styles.centered}>Verificata</th>
                  <th scope="col">Ruoli</th>
                  <th scope="col"><span className={styles.srOnly}>Azioni</span></th>
                </tr>
              </thead>
              <tbody>
                {users.map((item) => {
                  const draft = editing[item.id] || editableUser(item)
                  const isSelf = String(sessionUser?.id) === String(item.id)
                    || (sessionUser?.public_id && sessionUser.public_id === item.public_id)
                  const displayName = item.profile?.display_name || item.profile?.nickname || item.email

                  return (
                    <tr key={item.id}>
                      <th scope="row" className={styles.identity}>
                        <strong>{displayName}</strong>
                        <span>{item.email}</span>
                        {isSelf ? <small>Il tuo account</small> : null}
                      </th>
                      <td className={styles.created}>
                        {item.created_at ? new Date(item.created_at).toLocaleDateString('it-IT') : '—'}
                      </td>
                      <td>
                        <label className={styles.srOnly} htmlFor={`status-${item.id}`}>Stato di {displayName}</label>
                        <select
                          id={`status-${item.id}`}
                          value={draft.status}
                          disabled={isSelf}
                          onChange={(event) => updateDraft(item.id, 'status', event.target.value)}
                        >
                          <option value="active">Attivo</option>
                          <option value="suspended">Sospeso</option>
                          <option value="banned">Bannato</option>
                        </select>
                      </td>
                      <td>
                        <label className={styles.srOnly} htmlFor={`account-type-${item.id}`}>Tipo account di {displayName}</label>
                        <select
                          id={`account-type-${item.id}`}
                          value={draft.account_type}
                          onChange={(event) => updateDraft(item.id, 'account_type', event.target.value)}
                        >
                          <option value="audience">Audience</option>
                          <option value="poet_organizer">Poeta/organizzatore</option>
                        </select>
                      </td>
                      <td className={styles.centered}>
                        <label className={styles.checkbox} title="Email verificata">
                          <input
                            type="checkbox"
                            checked={draft.email_verified}
                            disabled={isSelf}
                            onChange={(event) => updateDraft(item.id, 'email_verified', event.target.checked)}
                          />
                          <span className={styles.srOnly}>Email verificata per {displayName}</span>
                        </label>
                      </td>
                      <td>
                        <div className={styles.roles} aria-label={`Ruoli di ${displayName}`}>
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
                          {roles.length === 0 ? <span>—</span> : null}
                        </div>
                      </td>
                      <td className={styles.actions}>
                        <button
                          type="button"
                          disabled={savingId === item.id || deletingId === item.id}
                          onClick={() => save(item.id)}
                        >
                          {savingId === item.id ? 'Salvataggio...' : 'Salva'}
                        </button>
                        <button
                          type="button"
                          className={styles.deleteButton}
                          disabled={isSelf || deletingId === item.id || savingId === item.id}
                          title={isSelf ? 'Non puoi eliminare il tuo account' : `Elimina ${displayName}`}
                          onClick={() => remove(item)}
                        >
                          {deletingId === item.id ? 'Eliminazione...' : 'Elimina'}
                        </button>
                      </td>
                    </tr>
                  )
                })}
                {users.length === 0 ? (
                  <tr>
                    <td colSpan="7" className={styles.empty}>Nessun utente trovato.</td>
                  </tr>
                ) : null}
              </tbody>
            </table>
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
