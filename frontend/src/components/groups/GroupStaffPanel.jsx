import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  acceptGroupAffiliation,
  acceptGroupContentLink,
  approveGroupRequest,
  inviteGroupMember,
  listGroupAffiliations,
  listGroupContentLinks,
  listGroupMembers,
  listGroupRequests,
  rejectGroupAffiliation,
  rejectGroupContentLink,
  rejectGroupRequest,
  removeGroupContentLink,
  removeGroupMember,
  requestGroupAffiliation,
  searchAffiliableGroups,
  searchGroupInviteUsers,
  updateGroup,
  updateGroupChatThreads,
  updateGroupMemberRole,
} from '../../app/api/groups.js'
import styles from './GroupStaffPanel.module.css'

const ROLES = ['admin', 'editor', 'follower']
const CHAT_AUDIENCES = ['admin', 'editor', 'affiliates', 'public']

function personName(user) {
  return user?.display_name || user?.nickname || ''
}

function requestLabel(item, t) {
  if (item.requested_role) {
    return t('group.ui.staff_role_request', { role: t(`group.roles.${item.requested_role}`) })
  }
  return t('group.ui.staff_follow_request')
}

function affiliationPeer(item, groupId) {
  if (Number(item.parent_group_id) === Number(groupId)) {
    return item.child
  }
  return item.parent
}

function incomingAffiliationLabel(item, t) {
  const fromParent = Number(item.initiated_by_group_id) === Number(item.parent_group_id)
  const name = (fromParent ? item.parent?.name : item.child?.name) || ''
  return fromParent
    ? t('group.ui.staff_affiliation_incoming_parent', { name })
    : t('group.ui.staff_affiliation_incoming_child', { name })
}

function contentLinkPreview(item, t) {
  if (item.content_type === 'poem') {
    return {
      kind: t('group.ui.feed_poem'),
      title: item.poem?.title || t('group.ui.feed_poem'),
      href: item.poem?.slug ? `/poems/${item.poem.slug}` : null,
    }
  }
  if (item.content_type === 'article') {
    return {
      kind: t('group.ui.feed_article'),
      title: item.article?.title || t('group.ui.feed_article'),
      href: item.article?.slug ? `/articles/${item.article.slug}` : null,
    }
  }
  return {
    kind: t('group.ui.feed_event'),
    title: item.event?.title || t('group.ui.feed_event'),
    href: item.event?.slug ? `/events/${item.event.slug}` : null,
  }
}

function outgoingAffiliationLabel(item, t) {
  const asParent = Number(item.initiated_by_group_id) === Number(item.parent_group_id)
  const name = (asParent ? item.child?.name : item.parent?.name) || ''
  return asParent
    ? t('group.ui.staff_affiliation_outgoing_child', { name })
    : t('group.ui.staff_affiliation_outgoing_parent', { name })
}

function Count({ value, alert = false }) {
  if (!value) return null
  return <span className={`${styles.badge} ${alert ? styles.badgeAlert : ''}`}>{value}</span>
}

function Empty({ children }) {
  return <p className={styles.empty}>{children}</p>
}

export default function GroupStaffPanel({ group, token, t, onUpdated }) {
  const canInvite = Boolean(group?.permissions?.invite)
  const canManageLinks = Boolean(group?.permissions?.manage_content_links)
  const canUpdate = Boolean(group?.permissions?.update)
  const canViewAffiliations = Boolean(group?.permissions?.view_affiliations)
  const canManageAffiliations = Boolean(group?.permissions?.manage_affiliations)
  const canManageChats = Boolean(group?.permissions?.manage_chat_threads)
  const tabs = useMemo(() => {
    const next = []
    if (canManageLinks || canInvite || canManageAffiliations) next.push('inbox')
    if (canManageLinks) next.push('events')
    if (canInvite) next.push('people')
    if (canViewAffiliations) next.push('affiliations')
    if (canManageChats) next.push('chat')
    return next
  }, [canInvite, canManageAffiliations, canManageChats, canManageLinks, canViewAffiliations])

  const [tab, setTab] = useState(tabs[0] || 'inbox')
  const [hasPickedTab, setHasPickedTab] = useState(false)
  const [members, setMembers] = useState([])
  const [requests, setRequests] = useState([])
  const [pendingEvents, setPendingEvents] = useState([])
  const [acceptedEvents, setAcceptedEvents] = useState([])
  const [pendingPublications, setPendingPublications] = useState([])
  const [acceptedPublications, setAcceptedPublications] = useState([])
  const [affiliations, setAffiliations] = useState({
    parents: [],
    children: [],
    incoming: [],
    outgoing: [],
  })
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [busyKey, setBusyKey] = useState('')
  const [inviteQuery, setInviteQuery] = useState('')
  const [inviteResults, setInviteResults] = useState([])
  const [isSearching, setIsSearching] = useState(false)
  const [isSearchOpen, setIsSearchOpen] = useState(false)
  const [selectedUser, setSelectedUser] = useState(null)
  const [inviteRole, setInviteRole] = useState('follower')
  const [affQuery, setAffQuery] = useState('')
  const [affResults, setAffResults] = useState([])
  const [isAffSearching, setIsAffSearching] = useState(false)
  const [isAffSearchOpen, setIsAffSearchOpen] = useState(false)
  const [selectedAffGroup, setSelectedAffGroup] = useState(null)

  const activeTab = tabs.includes(tab) ? tab : (tabs[0] || 'inbox')

  const loadStaff = useCallback(async ({ signal } = {}) => {
    const [nextMembers, nextRequests, nextPending, nextAccepted, nextAffiliations] = await Promise.all([
      canInvite ? listGroupMembers(group.slug, { token, signal }) : Promise.resolve([]),
      canInvite ? listGroupRequests(group.slug, { token, signal }) : Promise.resolve([]),
      canManageLinks
        ? listGroupContentLinks(group.slug, { status: 'pending', token, signal })
        : Promise.resolve([]),
      canManageLinks
        ? listGroupContentLinks(group.slug, { status: 'accepted', token, signal })
        : Promise.resolve([]),
      canViewAffiliations
        ? listGroupAffiliations(group.slug, { token, signal })
        : Promise.resolve({ parents: [], children: [], incoming: [], outgoing: [] }),
    ])
    const isPublication = (item) => item.content_type === 'poem' || item.content_type === 'article'
    setMembers(nextMembers)
    setRequests(nextRequests)
    setPendingEvents(nextPending.filter((item) => !isPublication(item)))
    setAcceptedEvents(nextAccepted.filter((item) => !isPublication(item)))
    setPendingPublications(nextPending.filter(isPublication))
    setAcceptedPublications(nextAccepted.filter(isPublication))
    setAffiliations(nextAffiliations)
  }, [canInvite, canManageLinks, canViewAffiliations, group.slug, token])

  useEffect(() => {
    if (!group?.slug || !token) return undefined
    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    loadStaff({ signal: controller.signal })
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('group.ui.staff_load_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) setIsLoading(false)
      })
    return () => controller.abort()
  }, [group?.slug, loadStaff, t, token])

  const incomingAffiliations = affiliations.incoming
  const pendingCount = requests.length
    + pendingEvents.length
    + pendingPublications.length
    + (canManageAffiliations ? incomingAffiliations.length : 0)
  const adminsCount = useMemo(
    () => members.filter((member) => member.role === 'admin').length,
    [members],
  )

  useEffect(() => {
    if (isLoading || hasPickedTab) return
    if (pendingCount > 0 && tabs.includes('inbox')) {
      setTab('inbox')
      return
    }
    if (tabs.includes('events')) {
      setTab('events')
    }
  }, [hasPickedTab, isLoading, pendingCount, tabs])

  useEffect(() => {
    const query = inviteQuery.trim()
    if (!token || !group?.slug || selectedUser || query.length < 2) {
      setInviteResults([])
      setIsSearching(false)
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(async () => {
      setIsSearching(true)
      try {
        const users = await searchGroupInviteUsers(group.slug, query, {
          token,
          signal: controller.signal,
        })
        setInviteResults(users)
      } catch (searchError) {
        if (searchError?.name !== 'AbortError') setInviteResults([])
      } finally {
        if (!controller.signal.aborted) setIsSearching(false)
      }
    }, 300)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [group?.slug, inviteQuery, selectedUser, token])

  useEffect(() => {
    const query = affQuery.trim()
    if (!token || !group?.slug || !canManageAffiliations || selectedAffGroup || query.length < 2) {
      setAffResults([])
      setIsAffSearching(false)
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(async () => {
      setIsAffSearching(true)
      try {
        const groups = await searchAffiliableGroups(group.slug, query, {
          token,
          signal: controller.signal,
        })
        setAffResults(groups)
      } catch (searchError) {
        if (searchError?.name !== 'AbortError') setAffResults([])
      } finally {
        if (!controller.signal.aborted) setIsAffSearching(false)
      }
    }, 300)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [affQuery, canManageAffiliations, group?.slug, selectedAffGroup, token])

  const run = async (key, action, { confirmText } = {}) => {
    if (confirmText && !window.confirm(confirmText)) return
    setBusyKey(key)
    setError('')
    try {
      await action()
      await loadStaff()
      await onUpdated?.()
    } catch (actionError) {
      setError(actionError?.message || t('group.ui.staff_load_error'))
    } finally {
      setBusyKey('')
    }
  }

  const handleInvite = async (event) => {
    event.preventDefault()
    if (!selectedUser) return
    await run('invite', async () => {
      await inviteGroupMember(group.slug, {
        user_id: selectedUser.id,
        role: inviteRole,
      }, { token })
      setSelectedUser(null)
      setInviteQuery('')
      setInviteResults([])
      setInviteRole('follower')
    })
  }

  const handleAffiliate = async (direction) => {
    if (!selectedAffGroup) return
    await run(`affiliate-${direction}`, async () => {
      await requestGroupAffiliation(group.slug, direction === 'parent'
        ? { parent_group_id: selectedAffGroup.id }
        : { child_group_id: selectedAffGroup.id }, { token })
      setSelectedAffGroup(null)
      setAffQuery('')
      setAffResults([])
    })
  }

  const enabledChatAudiences = useMemo(
    () => (group.chat_threads || []).filter((thread) => thread.is_enabled).map((thread) => thread.audience),
    [group.chat_threads],
  )

  const toggleChatAudience = async (audience, checked) => {
    const next = checked
      ? [...new Set([...enabledChatAudiences, audience])]
      : enabledChatAudiences.filter((item) => item !== audience)
    await run(`chat-${audience}`, async () => {
      await updateGroupChatThreads(group.slug, next, { token })
    })
  }

  const toggleAutoAccept = async (checked) => {
    await run('auto-accept', async () => {
      await updateGroup(group.slug, {
        name: group.name,
        description: group.description,
        group_category_id: group.group_category_id,
        visibility: group.visibility,
        is_listed: group.is_listed,
        auto_accept_events: checked,
        website_url: group.website_url,
        facebook_url: group.facebook_url,
        instagram_url: group.instagram_url,
        youtube_url: group.youtube_url,
      }, { token })
    })
  }

  const selfId = group.current_user_membership?.id

  return (
    <section className={styles.panel}>
      <header className={styles.head}>
        <h2 className={styles.title}>{t('group.ui.staff')}</h2>
        {pendingCount > 0 ? (
          <span className={styles.meta}>{t('group.ui.staff_pending_count', { count: pendingCount })}</span>
        ) : null}
      </header>

      {tabs.length > 1 ? (
        <nav className={styles.tabs} aria-label={t('group.ui.staff')}>
          {tabs.includes('inbox') ? (
            <button
              type="button"
              className={`${styles.tab} ${activeTab === 'inbox' ? styles.tabActive : ''}`}
              onClick={() => {
                setHasPickedTab(true)
                setTab('inbox')
              }}
            >
              {t('group.ui.staff_tab_inbox')}
              <Count value={pendingCount} alert />
            </button>
          ) : null}
          {tabs.includes('events') ? (
            <button
              type="button"
              className={`${styles.tab} ${activeTab === 'events' ? styles.tabActive : ''}`}
              onClick={() => {
                setHasPickedTab(true)
                setTab('events')
              }}
            >
              {t('group.ui.staff_tab_events')}
              <Count value={acceptedEvents.length} />
            </button>
          ) : null}
          {tabs.includes('people') ? (
            <button
              type="button"
              className={`${styles.tab} ${activeTab === 'people' ? styles.tabActive : ''}`}
              onClick={() => {
                setHasPickedTab(true)
                setTab('people')
              }}
            >
              {t('group.ui.staff_tab_people')}
              <Count value={members.length} />
            </button>
          ) : null}
          {tabs.includes('affiliations') ? (
            <button
              type="button"
              className={`${styles.tab} ${activeTab === 'affiliations' ? styles.tabActive : ''}`}
              onClick={() => {
                setHasPickedTab(true)
                setTab('affiliations')
              }}
            >
              {t('group.ui.staff_tab_affiliations')}
              <Count
                value={affiliations.parents.length + affiliations.children.length + incomingAffiliations.length}
                alert={incomingAffiliations.length > 0}
              />
            </button>
          ) : null}
          {tabs.includes('chat') ? (
            <button
              type="button"
              className={`${styles.tab} ${activeTab === 'chat' ? styles.tabActive : ''}`}
              onClick={() => {
                setHasPickedTab(true)
                setTab('chat')
              }}
            >
              {t('group.ui.staff_tab_chat')}
            </button>
          ) : null}
        </nav>
      ) : null}

      <div className={styles.body}>
        {error ? <p className={styles.error} role="alert">{error}</p> : null}
        {isLoading ? <p className={styles.muted}>{t('group.ui.loading')}</p> : null}

        {activeTab === 'inbox' && !isLoading ? (
          pendingCount === 0 ? (
            <Empty>{t('group.ui.staff_empty_inbox')}</Empty>
          ) : (
            <div className={styles.blocks}>
              {canManageLinks && pendingEvents.length > 0 ? (
                <section className={styles.block}>
                  <header className={styles.blockHead}>
                    <h3 className={styles.blockTitle}>{t('group.ui.staff_inbox_events')}</h3>
                    <span className={styles.blockMeta}>{pendingEvents.length}</span>
                  </header>
                  <ul className={styles.stack}>
                    {pendingEvents.map((item) => (
                      <li key={item.id} className={styles.card}>
                        <div className={styles.cardBody}>
                          <span className={styles.kind}>{t('group.ui.feed_event')}</span>
                          {item.event?.slug ? (
                            <Link to={`/events/${item.event.slug}`} className={styles.cardTitle}>
                              {item.event.title}
                            </Link>
                          ) : (
                            <strong className={styles.cardTitle}>{item.event?.title || t('group.ui.feed_event')}</strong>
                          )}
                          {item.source === 'child_forward' && item.source_group?.name ? (
                            <span className={styles.cardMeta}>
                              {t('group.ui.staff_forwarded_by', { name: item.source_group.name })}
                            </span>
                          ) : personName(item.proposed_by) ? (
                            <span className={styles.cardMeta}>
                              {t('group.ui.staff_proposed_by', { name: personName(item.proposed_by) })}
                            </span>
                          ) : null}
                        </div>
                        <div className={styles.actions}>
                          <button
                            type="button"
                            className={`${styles.btn} ${styles.btnAccent}`}
                            disabled={Boolean(busyKey)}
                            onClick={() => run(`accept-${item.id}`, () => acceptGroupContentLink(group.slug, item.id, { token }))}
                          >
                            {t('group.ui.staff_approve')}
                          </button>
                          <button
                            type="button"
                            className={styles.btn}
                            disabled={Boolean(busyKey)}
                            onClick={() => run(`reject-${item.id}`, () => rejectGroupContentLink(group.slug, item.id, { token }))}
                          >
                            {t('group.ui.staff_reject')}
                          </button>
                        </div>
                      </li>
                    ))}
                  </ul>
                </section>
              ) : null}

              {canManageLinks && pendingPublications.length > 0 ? (
                <section className={styles.block}>
                  <header className={styles.blockHead}>
                    <h3 className={styles.blockTitle}>{t('group.ui.staff_inbox_publications')}</h3>
                    <span className={styles.blockMeta}>{pendingPublications.length}</span>
                  </header>
                  <ul className={styles.stack}>
                    {pendingPublications.map((item) => {
                      const preview = contentLinkPreview(item, t)
                      return (
                        <li key={item.id} className={styles.card}>
                          <div className={styles.cardBody}>
                            <span className={styles.kind}>{preview.kind}</span>
                            {preview.href ? (
                              <Link to={preview.href} className={styles.cardTitle}>{preview.title}</Link>
                            ) : (
                              <strong className={styles.cardTitle}>{preview.title}</strong>
                            )}
                            {personName(item.proposed_by) ? (
                              <span className={styles.cardMeta}>
                                {t('group.ui.staff_proposed_by', { name: personName(item.proposed_by) })}
                              </span>
                            ) : null}
                          </div>
                          <div className={styles.actions}>
                            <button
                              type="button"
                              className={`${styles.btn} ${styles.btnAccent}`}
                              disabled={Boolean(busyKey)}
                              onClick={() => run(`accept-${item.id}`, () => acceptGroupContentLink(group.slug, item.id, { token }))}
                            >
                              {t('group.ui.staff_approve')}
                            </button>
                            <button
                              type="button"
                              className={styles.btn}
                              disabled={Boolean(busyKey)}
                              onClick={() => run(`reject-${item.id}`, () => rejectGroupContentLink(group.slug, item.id, { token }))}
                            >
                              {t('group.ui.staff_reject')}
                            </button>
                          </div>
                        </li>
                      )
                    })}
                  </ul>
                </section>
              ) : null}

              {canManageAffiliations && incomingAffiliations.length > 0 ? (
                <section className={styles.block}>
                  <header className={styles.blockHead}>
                    <h3 className={styles.blockTitle}>{t('group.ui.staff_inbox_affiliations')}</h3>
                    <span className={styles.blockMeta}>{incomingAffiliations.length}</span>
                  </header>
                  <ul className={styles.stack}>
                    {incomingAffiliations.map((item) => {
                      const peer = affiliationPeer(item, group.id)
                      return (
                        <li key={item.id} className={styles.card}>
                          <div className={styles.cardBody}>
                            <span className={styles.kind}>{t('group.ui.staff_affiliation')}</span>
                            {peer?.slug ? (
                              <Link to={`/groups/${peer.slug}`} className={styles.cardTitle}>
                                {peer.name}
                              </Link>
                            ) : (
                              <strong className={styles.cardTitle}>{peer?.name || t('group.ui.staff_affiliation')}</strong>
                            )}
                            <span className={styles.cardMeta}>{incomingAffiliationLabel(item, t)}</span>
                          </div>
                          <div className={styles.actions}>
                            <button
                              type="button"
                              className={`${styles.btn} ${styles.btnAccent}`}
                              disabled={Boolean(busyKey)}
                              onClick={() => run(`aff-accept-${item.id}`, () => acceptGroupAffiliation(group.slug, item.id, { token }))}
                            >
                              {t('group.ui.staff_approve')}
                            </button>
                            <button
                              type="button"
                              className={styles.btn}
                              disabled={Boolean(busyKey)}
                              onClick={() => run(`aff-reject-${item.id}`, () => rejectGroupAffiliation(group.slug, item.id, { token }))}
                            >
                              {t('group.ui.staff_reject')}
                            </button>
                          </div>
                        </li>
                      )
                    })}
                  </ul>
                </section>
              ) : null}

              {canInvite && requests.length > 0 ? (
                <section className={styles.block}>
                  <header className={styles.blockHead}>
                    <h3 className={styles.blockTitle}>{t('group.ui.staff_inbox_people')}</h3>
                    <span className={styles.blockMeta}>{requests.length}</span>
                  </header>
                  <ul className={styles.stack}>
                    {requests.map((item) => (
                      <li key={item.id} className={styles.card}>
                        <div className={styles.cardBody}>
                          <span className={styles.kind}>
                            {item.requested_role ? t(`group.roles.${item.requested_role}`) : t('group.roles.follower')}
                          </span>
                          <strong className={styles.cardTitle}>{personName(item.user)}</strong>
                          <span className={styles.cardMeta}>{requestLabel(item, t)}</span>
                        </div>
                        <div className={styles.actions}>
                          <button
                            type="button"
                            className={`${styles.btn} ${styles.btnAccent}`}
                            disabled={Boolean(busyKey)}
                            onClick={() => run(`approve-${item.id}`, () => approveGroupRequest(group.slug, item.id, { token }))}
                          >
                            {t('group.ui.staff_approve')}
                          </button>
                          <button
                            type="button"
                            className={styles.btn}
                            disabled={Boolean(busyKey)}
                            onClick={() => run(`reject-${item.id}`, () => rejectGroupRequest(group.slug, item.id, { token }))}
                          >
                            {t('group.ui.staff_reject')}
                          </button>
                        </div>
                      </li>
                    ))}
                  </ul>
                </section>
              ) : null}
            </div>
          )
        ) : null}

        {activeTab === 'events' && !isLoading ? (
          <div className={styles.blocks}>
            {canUpdate ? (
              <label className={styles.setting}>
                <input
                  type="checkbox"
                  checked={Boolean(group.auto_accept_events)}
                  disabled={Boolean(busyKey)}
                  onChange={(event) => toggleAutoAccept(event.target.checked)}
                />
                <span>
                  <strong>{t('group.ui.staff_auto_accept')}</strong>
                  <small>{t('group.ui.staff_auto_accept_help')}</small>
                </span>
              </label>
            ) : null}

            <section className={styles.block}>
              <header className={styles.blockHead}>
                <h3 className={styles.blockTitle}>{t('group.ui.staff_accepted_publications')}</h3>
                <span className={styles.blockMeta}>{acceptedPublications.length}</span>
              </header>
              {acceptedPublications.length === 0 ? (
                <Empty>{t('group.ui.staff_empty_accepted_publications')}</Empty>
              ) : (
                <ul className={styles.stack}>
                  {acceptedPublications.map((item) => {
                    const preview = contentLinkPreview(item, t)
                    return (
                      <li key={item.id} className={styles.card}>
                        <div className={styles.cardBody}>
                          <span className={`${styles.kind} ${styles.kindQuiet}`}>{preview.kind}</span>
                          {preview.href ? (
                            <Link to={preview.href} className={styles.cardTitle}>{preview.title}</Link>
                          ) : (
                            <strong className={styles.cardTitle}>{preview.title}</strong>
                          )}
                        </div>
                        <button
                          type="button"
                          className={styles.btn}
                          disabled={Boolean(busyKey)}
                          onClick={() => run(`unlink-${item.id}`, () => removeGroupContentLink(group.slug, item.id, { token }), {
                            confirmText: t('group.ui.staff_event_remove_confirm', { title: preview.title }),
                          })}
                        >
                          {t('group.ui.staff_event_remove')}
                        </button>
                      </li>
                    )
                  })}
                </ul>
              )}
            </section>

            <section className={styles.block}>
              <header className={styles.blockHead}>
                <h3 className={styles.blockTitle}>{t('group.ui.staff_accepted_events')}</h3>
                <span className={styles.blockMeta}>{acceptedEvents.length}</span>
              </header>
              {acceptedEvents.length === 0 ? (
                <Empty>{t('group.ui.staff_empty_accepted_events')}</Empty>
              ) : (
                <ul className={styles.stack}>
                  {acceptedEvents.map((item) => (
                    <li key={item.id} className={styles.card}>
                      <div className={styles.cardBody}>
                        <span className={`${styles.kind} ${styles.kindQuiet}`}>{t('group.ui.staff_on_page')}</span>
                        {item.event?.slug ? (
                          <Link to={`/events/${item.event.slug}`} className={styles.cardTitle}>
                            {item.event.title}
                          </Link>
                        ) : (
                          <strong className={styles.cardTitle}>{item.event?.title || t('group.ui.feed_event')}</strong>
                        )}
                        {item.source === 'child_forward' && item.source_group?.name ? (
                          <span className={styles.cardMeta}>
                            {t('group.ui.staff_forwarded_by', { name: item.source_group.name })}
                          </span>
                        ) : personName(item.proposed_by) ? (
                          <span className={styles.cardMeta}>
                            {t('group.ui.staff_proposed_by', { name: personName(item.proposed_by) })}
                          </span>
                        ) : null}
                      </div>
                      <button
                        type="button"
                        className={styles.btn}
                        disabled={Boolean(busyKey)}
                        onClick={() => run(`unlink-${item.id}`, () => removeGroupContentLink(group.slug, item.id, { token }), {
                          confirmText: t('group.ui.staff_event_remove_confirm', {
                            title: item.event?.title || t('group.ui.feed_event'),
                          }),
                        })}
                      >
                        {t('group.ui.staff_event_remove')}
                      </button>
                    </li>
                  ))}
                </ul>
              )}
            </section>
          </div>
        ) : null}

        {activeTab === 'people' && !isLoading ? (
          <div className={styles.blocks}>
            <section className={styles.inviteBox}>
              <h3 className={styles.blockTitle}>{t('group.ui.staff_invite')}</h3>
              <p className={styles.inviteLead}>{t('group.ui.staff_invite_lead')}</p>
              <form className={styles.invite} onSubmit={handleInvite}>
                <label className={styles.field}>
                  {t('group.ui.staff_invite_search')}
                  <div className={styles.combobox}>
                    <input
                      className={styles.input}
                      value={selectedUser ? personName(selectedUser) : inviteQuery}
                      onChange={(event) => {
                        setSelectedUser(null)
                        setInviteQuery(event.target.value)
                        setIsSearchOpen(true)
                      }}
                      onFocus={() => setIsSearchOpen(true)}
                      onBlur={() => window.setTimeout(() => setIsSearchOpen(false), 120)}
                      placeholder={t('group.ui.staff_invite_placeholder')}
                      autoComplete="off"
                      disabled={Boolean(busyKey)}
                    />
                    {isSearchOpen && !selectedUser && inviteQuery.trim().length >= 2 ? (
                      <div className={styles.suggestions} role="listbox">
                        {isSearching ? (
                          <p className={styles.suggestionStatus}>{t('group.ui.staff_searching')}</p>
                        ) : inviteResults.length === 0 ? (
                          <p className={styles.suggestionStatus}>{t('group.ui.staff_no_users')}</p>
                        ) : (
                          inviteResults.map((user) => (
                            <button
                              key={user.id}
                              type="button"
                              className={styles.suggestion}
                              onMouseDown={(event) => event.preventDefault()}
                              onClick={() => {
                                setSelectedUser(user)
                                setInviteQuery('')
                                setInviteResults([])
                                setIsSearchOpen(false)
                              }}
                            >
                              <strong>{personName(user)}</strong>
                              {user.nickname && user.display_name ? <small>{user.nickname}</small> : null}
                            </button>
                          ))
                        )}
                      </div>
                    ) : null}
                  </div>
                </label>

                <label className={styles.field}>
                  {t('group.ui.staff_invite_role')}
                  <select
                    className={styles.select}
                    value={inviteRole}
                    onChange={(event) => setInviteRole(event.target.value)}
                    disabled={Boolean(busyKey)}
                  >
                    {ROLES.map((role) => (
                      <option key={role} value={role}>{t(`group.roles.${role}`)}</option>
                    ))}
                  </select>
                </label>

                <button
                  type="submit"
                  className={`${styles.btn} ${styles.btnAccent}`}
                  disabled={!selectedUser || Boolean(busyKey)}
                >
                  {busyKey === 'invite' ? t('group.ui.staff_inviting') : t('group.ui.staff_invite_submit')}
                </button>
              </form>
            </section>

            <section className={styles.block}>
              <header className={styles.blockHead}>
                <h3 className={styles.blockTitle}>{t('group.ui.staff_people')}</h3>
                <span className={styles.blockMeta}>
                  {t('group.ui.staff_members_count', { count: members.length })}
                </span>
              </header>
              {members.length === 0 ? (
                <Empty>{t('group.ui.staff_empty_people')}</Empty>
              ) : (
                <ul className={styles.stack}>
                  {members.map((member) => {
                    const name = personName(member.user)
                    const isSelf = member.id === selfId
                    const isLastAdmin = member.role === 'admin' && adminsCount <= 1
                    return (
                      <li key={member.id} className={styles.card}>
                        <div className={styles.cardBody}>
                          <span className={`${styles.kind} ${styles[`kind_${member.role}`] || ''}`}>
                            {t(`group.roles.${member.role}`)}
                          </span>
                          <strong className={styles.cardTitle}>
                            {name}
                            {isSelf ? ` ${t('group.ui.staff_you')}` : ''}
                          </strong>
                          {isLastAdmin ? (
                            <span className={styles.cardMeta}>{t('group.ui.staff_last_admin')}</span>
                          ) : null}
                        </div>
                        {isLastAdmin ? null : (
                          <div className={styles.actions}>
                            <label className={styles.srOnly} htmlFor={`role-${member.id}`}>
                              {t('group.ui.staff_invite_role')}
                            </label>
                            <select
                              id={`role-${member.id}`}
                              className={styles.select}
                              value={member.role}
                              disabled={Boolean(busyKey)}
                              onChange={(event) => {
                                const role = event.target.value
                                event.target.value = member.role
                                if (role === member.role) return
                                run(`role-${member.id}`, () => updateGroupMemberRole(group.slug, member.id, role, { token }), {
                                  confirmText: t('group.ui.staff_role_confirm', {
                                    name,
                                    role: t(`group.roles.${role}`),
                                  }),
                                })
                              }}
                            >
                              {ROLES.map((role) => (
                                <option key={role} value={role}>{t(`group.roles.${role}`)}</option>
                              ))}
                            </select>
                            <button
                              type="button"
                              className={styles.btn}
                              disabled={Boolean(busyKey)}
                              onClick={() => run(`remove-${member.id}`, () => removeGroupMember(group.slug, member.id, { token }), {
                                confirmText: t('group.ui.staff_remove_confirm', { name }),
                              })}
                            >
                              {t('group.ui.staff_remove')}
                            </button>
                          </div>
                        )}
                      </li>
                    )
                  })}
                </ul>
              )}
            </section>
          </div>
        ) : null}

        {activeTab === 'affiliations' && !isLoading ? (
          <div className={styles.blocks}>
            {incomingAffiliations.length > 0 ? (
              <section className={styles.block}>
                <header className={styles.blockHead}>
                  <h3 className={styles.blockTitle}>{t('group.ui.staff_inbox_affiliations')}</h3>
                  <span className={styles.blockMeta}>{incomingAffiliations.length}</span>
                </header>
                <ul className={styles.stack}>
                  {incomingAffiliations.map((item) => {
                    const peer = affiliationPeer(item, group.id)
                    return (
                      <li key={item.id} className={styles.card}>
                        <div className={styles.cardBody}>
                          <span className={styles.kind}>{t('group.ui.staff_affiliation')}</span>
                          {peer?.slug ? (
                            <Link to={`/groups/${peer.slug}`} className={styles.cardTitle}>{peer.name}</Link>
                          ) : (
                            <strong className={styles.cardTitle}>{peer?.name}</strong>
                          )}
                          <span className={styles.cardMeta}>{incomingAffiliationLabel(item, t)}</span>
                        </div>
                        {canManageAffiliations ? (
                          <div className={styles.actions}>
                            <button
                              type="button"
                              className={`${styles.btn} ${styles.btnAccent}`}
                              disabled={Boolean(busyKey)}
                              onClick={() => run(`aff-accept-${item.id}`, () => acceptGroupAffiliation(group.slug, item.id, { token }))}
                            >
                              {t('group.ui.staff_approve')}
                            </button>
                            <button
                              type="button"
                              className={styles.btn}
                              disabled={Boolean(busyKey)}
                              onClick={() => run(`aff-reject-${item.id}`, () => rejectGroupAffiliation(group.slug, item.id, { token }))}
                            >
                              {t('group.ui.staff_reject')}
                            </button>
                          </div>
                        ) : null}
                      </li>
                    )
                  })}
                </ul>
              </section>
            ) : null}

            {canManageAffiliations ? (
              <section className={styles.inviteBox}>
                <h3 className={styles.blockTitle}>{t('group.ui.staff_affiliation_connect')}</h3>
                <p className={styles.inviteLead}>{t('group.ui.staff_affiliation_lead')}</p>
                <div className={styles.field}>
                  {t('group.ui.staff_affiliation_search')}
                  <div className={styles.combobox}>
                    <input
                      className={styles.input}
                      value={selectedAffGroup ? selectedAffGroup.name : affQuery}
                      onChange={(event) => {
                        setSelectedAffGroup(null)
                        setAffQuery(event.target.value)
                        setIsAffSearchOpen(true)
                      }}
                      onFocus={() => setIsAffSearchOpen(true)}
                      onBlur={() => window.setTimeout(() => setIsAffSearchOpen(false), 120)}
                      placeholder={t('group.ui.staff_affiliation_placeholder')}
                      autoComplete="off"
                      disabled={Boolean(busyKey)}
                    />
                    {isAffSearchOpen && !selectedAffGroup && affQuery.trim().length >= 2 ? (
                      <div className={styles.suggestions} role="listbox">
                        {isAffSearching ? (
                          <p className={styles.suggestionStatus}>{t('group.ui.staff_searching')}</p>
                        ) : affResults.length === 0 ? (
                          <p className={styles.suggestionStatus}>{t('group.ui.propose_empty')}</p>
                        ) : (
                          affResults.map((item) => (
                            <button
                              key={item.id}
                              type="button"
                              className={styles.suggestion}
                              onMouseDown={(event) => event.preventDefault()}
                              onClick={() => {
                                setSelectedAffGroup(item)
                                setAffQuery('')
                                setAffResults([])
                                setIsAffSearchOpen(false)
                              }}
                            >
                              <strong>{item.name}</strong>
                              {item.category?.name ? <small>{item.category.name}</small> : null}
                            </button>
                          ))
                        )}
                      </div>
                    ) : null}
                  </div>
                </div>
                {selectedAffGroup ? (
                  <div className={styles.actions}>
                    <button
                      type="button"
                      className={`${styles.btn} ${styles.btnAccent}`}
                      disabled={Boolean(busyKey)}
                      onClick={() => handleAffiliate('parent')}
                    >
                      {t('group.ui.staff_affiliation_ask_parent')}
                    </button>
                    <button
                      type="button"
                      className={styles.btn}
                      disabled={Boolean(busyKey)}
                      onClick={() => handleAffiliate('child')}
                    >
                      {t('group.ui.staff_affiliation_invite_child')}
                    </button>
                  </div>
                ) : null}
              </section>
            ) : null}

            {affiliations.outgoing.length > 0 ? (
              <section className={styles.block}>
                <header className={styles.blockHead}>
                  <h3 className={styles.blockTitle}>{t('group.ui.staff_outgoing_affiliations')}</h3>
                  <span className={styles.blockMeta}>{affiliations.outgoing.length}</span>
                </header>
                <ul className={styles.stack}>
                  {affiliations.outgoing.map((item) => {
                    const peer = affiliationPeer(item, group.id)
                    return (
                      <li key={item.id} className={styles.card}>
                        <div className={styles.cardBody}>
                          <span className={`${styles.kind} ${styles.kindQuiet}`}>{t('group.ui.staff_affiliation_sent')}</span>
                          {peer?.slug ? (
                            <Link to={`/groups/${peer.slug}`} className={styles.cardTitle}>{peer.name}</Link>
                          ) : (
                            <strong className={styles.cardTitle}>{peer?.name}</strong>
                          )}
                          <span className={styles.cardMeta}>{outgoingAffiliationLabel(item, t)}</span>
                        </div>
                      </li>
                    )
                  })}
                </ul>
              </section>
            ) : null}

            <section className={styles.block}>
              <header className={styles.blockHead}>
                <h3 className={styles.blockTitle}>{t('group.ui.staff_parents')}</h3>
                <span className={styles.blockMeta}>{affiliations.parents.length}</span>
              </header>
              {affiliations.parents.length === 0 ? (
                <Empty>{t('group.ui.staff_empty_parents')}</Empty>
              ) : (
                <ul className={styles.stack}>
                  {affiliations.parents.map((item) => (
                    <li key={item.id} className={styles.card}>
                      <div className={styles.cardBody}>
                        <span className={styles.kind}>{t('group.ui.staff_parent')}</span>
                        {item.parent?.slug ? (
                          <Link to={`/groups/${item.parent.slug}`} className={styles.cardTitle}>{item.parent.name}</Link>
                        ) : (
                          <strong className={styles.cardTitle}>{item.parent?.name}</strong>
                        )}
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </section>

            <section className={styles.block}>
              <header className={styles.blockHead}>
                <h3 className={styles.blockTitle}>{t('group.ui.staff_children')}</h3>
                <span className={styles.blockMeta}>{affiliations.children.length}</span>
              </header>
              {affiliations.children.length === 0 ? (
                <Empty>{t('group.ui.staff_empty_children')}</Empty>
              ) : (
                <ul className={styles.stack}>
                  {affiliations.children.map((item) => (
                    <li key={item.id} className={styles.card}>
                      <div className={styles.cardBody}>
                        <span className={`${styles.kind} ${styles.kindQuiet}`}>{t('group.ui.staff_child')}</span>
                        {item.child?.slug ? (
                          <Link to={`/groups/${item.child.slug}`} className={styles.cardTitle}>{item.child.name}</Link>
                        ) : (
                          <strong className={styles.cardTitle}>{item.child?.name}</strong>
                        )}
                      </div>
                    </li>
                  ))}
                </ul>
              )}
            </section>
          </div>
        ) : null}

        {activeTab === 'chat' && !isLoading ? (
          <div className={styles.blocks}>
            <p className={styles.muted}>{t('group.ui.staff_chat_lead')}</p>
            {CHAT_AUDIENCES.map((audience) => (
              <label key={audience} className={styles.setting}>
                <input
                  type="checkbox"
                  checked={enabledChatAudiences.includes(audience)}
                  disabled={Boolean(busyKey)}
                  onChange={(event) => toggleChatAudience(audience, event.target.checked)}
                />
                <span>
                  <strong>{t(`group.ui.chat_audience_${audience}`)}</strong>
                  <small>{t(`group.ui.chat_audience_${audience}_help`)}</small>
                </span>
              </label>
            ))}
          </div>
        ) : null}
      </div>
    </section>
  )
}
