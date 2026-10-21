import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { createDirectChatThread } from '../../app/api/chat.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import styles from './EventOrganizer.module.css'

function initialsOf(name) {
  return String(name ?? '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('')
}

function PersonChip({ person, profileLabel }) {
  const nickname = person?.nickname || ''
  const name = person?.display_name || nickname
  if (!name) {
    return null
  }

  const avatarUrl = person?.avatar_url ? resolvePublicUrl(person.avatar_url) : ''
  const identity = (
    <>
      {avatarUrl ? (
        <img src={avatarUrl} alt="" className={styles.avatar} />
      ) : (
        <span className={styles.avatar} aria-hidden="true">{initialsOf(name)}</span>
      )}
      <span className={styles.name}>{name}</span>
    </>
  )

  if (!nickname) {
    return <span className={styles.person}>{identity}</span>
  }

  return (
    <Link
      to={`/u/${encodeURIComponent(nickname)}`}
      className={styles.person}
      aria-label={profileLabel}
    >
      {identity}
    </Link>
  )
}

export default function EventOrganizer({ author, collaborators = [], isOwner, token, t }) {
  const navigate = useNavigate()
  const [isWriting, setIsWriting] = useState(false)
  const [writeError, setWriteError] = useState('')

  const nickname = author?.nickname || ''
  const name = author?.display_name || nickname
  const visibleCollaborators = Array.isArray(collaborators)
    ? collaborators.filter((person) => person?.display_name || person?.nickname)
    : []

  if (!name && visibleCollaborators.length === 0) {
    return null
  }

  async function handleWrite() {
    if (isWriting || !nickname) {
      return
    }
    setIsWriting(true)
    setWriteError('')
    try {
      const thread = await createDirectChatThread({ nickname }, { token })
      if (thread?.id) {
        navigate(`/chat/${thread.id}`)
      }
    } catch (error) {
      setWriteError(error?.message || t('events.detail_page.organizer.write_error'))
    } finally {
      setIsWriting(false)
    }
  }

  return (
    <div className={styles.organizer}>
      {name ? (
        <>
          <span className={styles.label}>{t('events.detail_page.organizer.label')}</span>
          <div className={styles.row}>
            <PersonChip
              person={author}
              profileLabel={t('events.detail_page.organizer.view_profile', { name })}
            />

            {!isOwner && nickname ? (
              token ? (
                <button type="button" className={styles.writeButton} onClick={handleWrite} disabled={isWriting}>
                  {t('events.detail_page.organizer.write')}
                </button>
              ) : (
                <Link to="/login" className={styles.writeButton}>
                  {t('events.detail_page.organizer.login_to_write')}
                </Link>
              )
            ) : null}
          </div>
        </>
      ) : null}

      {visibleCollaborators.length > 0 ? (
        <>
          <span className={styles.label}>{t('events.detail_page.organizer.collaborators_label')}</span>
          <div className={styles.row}>
            {visibleCollaborators.map((person) => (
              <PersonChip
                key={person.id || person.public_id || person.nickname}
                person={person}
                profileLabel={t('events.detail_page.organizer.view_profile', {
                  name: person.display_name || person.nickname,
                })}
              />
            ))}
          </div>
        </>
      ) : null}
      {writeError ? <p className={styles.error} role="alert">{writeError}</p> : null}
    </div>
  )
}
