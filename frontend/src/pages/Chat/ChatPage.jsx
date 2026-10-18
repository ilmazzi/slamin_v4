import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  addChatThreadMember,
  createDirectChatThread,
  createGroupChatThread,
  listChatMessages,
  listChatThreads,
  markChatThreadRead,
  removeChatThreadMember,
  searchChatUsers,
  sendChatMessage,
} from '../../app/api/chat.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useRealtimeConfig } from '../../app/hooks/useRealtimeConfig.js'
import { acquireEcho, isReverbConfigured, releaseEcho } from '../../app/realtime/echo.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './ChatPage.module.css'

function threadTitle(thread, t) {
  if (thread?.type === 'direct') {
    return thread.other_user?.display_name || thread.other_user?.nickname || t('chat.ui.you')
  }
  return thread?.title || thread?.group?.name || t('chat.ui.group_fallback')
}

function memberLabel(member) {
  return member?.display_name || member?.nickname || ''
}

function isFreeGroupThread(thread) {
  return thread?.type === 'group' && !thread?.is_group_attached
}

function threadAvatarUrl(thread, currentUserId) {
  if (thread?.type === 'direct') {
    return resolvePublicUrl(thread.other_user?.avatar_url)
  }
  const other = (thread?.members || []).find((member) => Number(member.id) !== Number(currentUserId))
  return resolvePublicUrl(other?.avatar_url)
}

function authorLabel(message, currentUserId, t) {
  if (Number(message?.author?.id) === Number(currentUserId)) {
    return t('chat.ui.you')
  }
  return message?.author?.display_name || message?.author?.nickname || ''
}

function upsertMessage(messages, incoming) {
  if (!incoming?.id) {
    return messages
  }
  const index = messages.findIndex((item) => item.id === incoming.id)
  if (index === -1) {
    return [...messages, incoming]
  }
  return messages.map((item, itemIndex) => (itemIndex === index ? incoming : item))
}

function UserResultList({ results, isSearching, onSelect, disabled, excludeIds = [], t }) {
  const visible = results.filter((result) => !excludeIds.includes(Number(result.id)))

  return (
    <div className={styles.userSuggestions} role="listbox">
      {isSearching ? (
        <div className={styles.userSuggestionStatus}>{t('chat.ui.searching_users')}</div>
      ) : visible.length ? (
        visible.map((result) => {
          const avatar = resolvePublicUrl(result.avatar_url)
          return (
            <button
              type="button"
              key={result.id}
              className={styles.userSuggestion}
              role="option"
              onMouseDown={(event) => event.preventDefault()}
              onClick={() => onSelect(result)}
              disabled={disabled}
            >
              {avatar ? (
                <img src={avatar} alt="" className={styles.suggestionAvatar} />
              ) : (
                <span className={styles.suggestionAvatar} aria-hidden="true" />
              )}
              <span className={styles.suggestionMeta}>
                <span>{result.display_name || result.nickname}</span>
                {result.nickname ? <small>@{result.nickname}</small> : null}
              </span>
            </button>
          )
        })
      ) : (
        <div className={styles.userSuggestionStatus}>{t('chat.ui.no_users_found')}</div>
      )}
    </div>
  )
}

export default function ChatPage() {
  const { threadId } = useParams()
  const navigate = useNavigate()
  const { t } = useI18n()
  const { token, user } = useAuth()
  const [threads, setThreads] = useState([])
  const [messages, setMessages] = useState([])
  const [draft, setDraft] = useState('')
  const [composerMode, setComposerMode] = useState('direct')
  const [groupTitle, setGroupTitle] = useState('')
  const [groupMembers, setGroupMembers] = useState([])
  const [userQuery, setUserQuery] = useState('')
  const [userResults, setUserResults] = useState([])
  const [isSearchingUsers, setIsSearchingUsers] = useState(false)
  const [isSearchOpen, setIsSearchOpen] = useState(false)
  const [isMembersOpen, setIsMembersOpen] = useState(false)
  const [memberQuery, setMemberQuery] = useState('')
  const [memberResults, setMemberResults] = useState([])
  const [isSearchingMembers, setIsSearchingMembers] = useState(false)
  const [isMemberSearchOpen, setIsMemberSearchOpen] = useState(false)
  const [isManagingMember, setIsManagingMember] = useState(false)
  const [errorMessage, setErrorMessage] = useState('')
  const [isLoadingThreads, setIsLoadingThreads] = useState(true)
  const [isLoadingMessages, setIsLoadingMessages] = useState(false)
  const [isSending, setIsSending] = useState(false)
  const [isStarting, setIsStarting] = useState(false)
  const [isThreadListOpen, setIsThreadListOpen] = useState(!threadId)
  const [echoClient, setEchoClient] = useState(null)
  const selectedIdRef = useRef(null)
  const messagesPaneRef = useRef(null)
  const realtime = useRealtimeConfig(token)
  const selectedId = threadId ? Number(threadId) : null
  selectedIdRef.current = selectedId

  const loadThreads = useCallback(async ({ signal } = {}) => {
    if (!token) {
      return
    }
    const nextThreads = await listChatThreads({ token, signal })
    setThreads(nextThreads)
  }, [token])

  const replaceThread = useCallback((nextThread) => {
    if (!nextThread?.id) {
      return
    }
    setThreads((current) => {
      const exists = current.some((thread) => Number(thread.id) === Number(nextThread.id))
      if (!exists) {
        return [nextThread, ...current]
      }
      return current.map((thread) => (
        Number(thread.id) === Number(nextThread.id) ? { ...thread, ...nextThread } : thread
      ))
    })
  }, [])

  useEffect(() => {
    const controller = new AbortController()
    setIsLoadingThreads(true)
    setErrorMessage('')
    loadThreads({ signal: controller.signal })
      .catch((error) => {
        if (error?.name !== 'AbortError') {
          setErrorMessage(error?.message || t('chat.ui.load_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoadingThreads(false)
        }
      })
    return () => controller.abort()
  }, [loadThreads, t])

  useEffect(() => {
    if (!token || !selectedId) {
      setMessages([])
      return undefined
    }

    const controller = new AbortController()
    setIsLoadingMessages(true)
    setErrorMessage('')
    setIsMembersOpen(false)
    setMemberQuery('')
    setMemberResults([])

    Promise.all([
      listChatMessages(selectedId, { token, signal: controller.signal }),
      markChatThreadRead(selectedId, { token, signal: controller.signal }).catch(() => null),
    ])
      .then(([nextMessages]) => {
        setMessages(nextMessages)
        setThreads((current) => current.map((thread) => (
          Number(thread.id) === selectedId ? { ...thread, unread_count: 0 } : thread
        )))
      })
      .catch((error) => {
        if (error?.name !== 'AbortError') {
          setErrorMessage(error?.message || t('chat.ui.load_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoadingMessages(false)
        }
      })

    return () => controller.abort()
  }, [selectedId, t, token])

  useEffect(() => {
    const pane = messagesPaneRef.current
    if (!pane) {
      return
    }
    pane.scrollTop = pane.scrollHeight
  }, [messages])

  useEffect(() => {
    const query = userQuery.trim()
    if (!token || query.length < 2) {
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(() => {
      setIsSearchingUsers(true)
      searchChatUsers(query, { token, signal: controller.signal })
        .then(setUserResults)
        .catch((error) => {
          if (error?.name !== 'AbortError') {
            setUserResults([])
          }
        })
        .finally(() => {
          if (!controller.signal.aborted) {
            setIsSearchingUsers(false)
          }
        })
    }, 300)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [token, userQuery])

  useEffect(() => {
    const query = memberQuery.trim()
    if (!token || query.length < 2) {
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(() => {
      setIsSearchingMembers(true)
      searchChatUsers(query, { token, signal: controller.signal })
        .then(setMemberResults)
        .catch((error) => {
          if (error?.name !== 'AbortError') {
            setMemberResults([])
          }
        })
        .finally(() => {
          if (!controller.signal.aborted) {
            setIsSearchingMembers(false)
          }
        })
    }, 300)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [memberQuery, token])

  useEffect(() => {
    if (!token || !user?.id || !isReverbConfigured(realtime)) {
      return undefined
    }

    const echo = acquireEcho(token, realtime)
    setEchoClient(echo)
    if (!echo) {
      return undefined
    }

    const userChannel = echo.private(`chat.user.${user.id}`)
    const handleUserMessage = (payload) => {
      const incoming = payload?.message
      const incomingThreadId = Number(payload?.thread_id ?? incoming?.thread_id)
      if (incoming && incomingThreadId === selectedIdRef.current) {
        setMessages((current) => upsertMessage(current, incoming))
      }
      loadThreads().catch(() => null)
    }
    userChannel.listen('.message.sent', handleUserMessage)

    return () => {
      userChannel.stopListening('.message.sent', handleUserMessage)
      releaseEcho(echo)
      setEchoClient(null)
    }
  }, [loadThreads, realtime, token, user?.id])

  useEffect(() => {
    const echo = echoClient
    if (!echo || !selectedId) {
      return undefined
    }

    const channelName = `chat.${selectedId}`
    const channel = echo.private(channelName)
    const handleIncoming = (payload) => {
      const incoming = payload?.message
      if (incoming) {
        setMessages((current) => upsertMessage(current, incoming))
      }
    }
    channel.listen('.message.sent', handleIncoming)
    channel.listen('.message.updated', handleIncoming)

    return () => {
      echo.leave(channelName)
    }
  }, [echoClient, selectedId])

  function resetComposer() {
    setComposerMode('direct')
    setGroupTitle('')
    setGroupMembers([])
    setUserQuery('')
    setUserResults([])
    setIsSearchOpen(false)
  }

  async function handleSelectUser(result) {
    if (!result?.id || isStarting) {
      return
    }

    if (composerMode === 'group') {
      setGroupMembers((current) => (
        current.some((member) => Number(member.id) === Number(result.id))
          ? current
          : [...current, result]
      ))
      setUserQuery('')
      setUserResults([])
      setIsSearchOpen(false)
      return
    }

    setIsStarting(true)
    setErrorMessage('')
    try {
      const thread = await createDirectChatThread({ user_id: result.id }, { token })
      if (thread?.id) {
        resetComposer()
        setIsThreadListOpen(false)
        await loadThreads()
        navigate(`/chat/${thread.id}`)
      }
    } catch (error) {
      setErrorMessage(error?.message || t('chat.ui.start_error'))
    } finally {
      setIsStarting(false)
    }
  }

  async function handleCreateGroup(event) {
    event.preventDefault()
    const title = groupTitle.trim()
    if (!title || groupMembers.length < 1 || isStarting) {
      return
    }

    setIsStarting(true)
    setErrorMessage('')
    try {
      const thread = await createGroupChatThread({
        title,
        user_ids: groupMembers.map((member) => member.id),
      }, { token })
      if (thread?.id) {
        resetComposer()
        setIsThreadListOpen(false)
        await loadThreads()
        navigate(`/chat/${thread.id}`)
      }
    } catch (error) {
      setErrorMessage(error?.message || t('chat.ui.group_create_error'))
    } finally {
      setIsStarting(false)
    }
  }

  async function handleAddMember(result) {
    if (!result?.id || !selectedId || isManagingMember) {
      return
    }

    setIsManagingMember(true)
    setErrorMessage('')
    try {
      const thread = await addChatThreadMember(selectedId, result.id, { token })
      if (thread) {
        replaceThread(thread)
      }
      setMemberQuery('')
      setMemberResults([])
      setIsMemberSearchOpen(false)
    } catch (error) {
      setErrorMessage(error?.message || t('chat.ui.add_member_error'))
    } finally {
      setIsManagingMember(false)
    }
  }

  async function handleRemoveMember(member) {
    if (!member?.id || !selectedId || isManagingMember) {
      return
    }

    const name = memberLabel(member) || member.nickname || ''
    if (!window.confirm(t('chat.ui.remove_member_confirm', { name }))) {
      return
    }

    setIsManagingMember(true)
    setErrorMessage('')
    try {
      const thread = await removeChatThreadMember(selectedId, member.id, { token })
      if (thread) {
        replaceThread(thread)
      }
    } catch (error) {
      setErrorMessage(error?.message || t('chat.ui.remove_member_error'))
    } finally {
      setIsManagingMember(false)
    }
  }

  async function handleSubmit(event) {
    event.preventDefault()
    const content = draft.trim()
    if (!content || !selectedId || isSending) {
      return
    }

    setIsSending(true)
    setErrorMessage('')
    try {
      const created = await sendChatMessage(selectedId, { content }, { token })
      if (created) {
        setMessages((current) => upsertMessage(current, created))
        setDraft('')
        await loadThreads()
      }
    } catch (error) {
      setErrorMessage(error?.message || t('chat.ui.send_error'))
    } finally {
      setIsSending(false)
    }
  }

  const selectedThread = useMemo(
    () => threads.find((thread) => Number(thread.id) === selectedId) ?? null,
    [selectedId, threads],
  )
  const isListOpen = isThreadListOpen
  const selectedMembers = selectedThread?.members || []
  const canManageMembers = Boolean(selectedThread?.can_manage_members)
  const showMembersPanel = isFreeGroupThread(selectedThread)
  const groupMemberIds = groupMembers.map((member) => Number(member.id))
  const selectedMemberIds = selectedMembers.map((member) => Number(member.id))

  function closeThreadList() {
    setIsThreadListOpen(false)
  }

  return (
    <MainLayout showFooter={false} pageClassName={styles.shell} mainClassName={styles.main}>
      <section className={styles.page}>
        {isListOpen ? (
          <button
            type="button"
            className={styles.backdrop}
            aria-label={t('chat.ui.close_list')}
            onClick={closeThreadList}
          />
        ) : null}
        <aside className={[styles.sidebar, isListOpen ? styles.sidebarOpen : ''].join(' ')}>
          <div className={styles.sidebarHead}>
            <h1 className={styles.title}>{t('chat.ui.title')}</h1>
            <button
              type="button"
              className={styles.closeList}
              onClick={closeThreadList}
              aria-label={t('chat.ui.close_list')}
            >
              ×
            </button>
          </div>
          {composerMode === 'group' ? (
            <form className={styles.groupComposer} onSubmit={handleCreateGroup}>
              <input
                className={styles.startInput}
                value={groupTitle}
                onChange={(event) => setGroupTitle(event.target.value)}
                placeholder={t('chat.ui.group_title_placeholder')}
                autoComplete="off"
                maxLength={255}
                disabled={isStarting}
              />
              {groupMembers.length > 0 ? (
                <ul className={styles.chips}>
                  {groupMembers.map((member) => (
                    <li key={member.id} className={styles.chip}>
                      <span>{memberLabel(member)}</span>
                      <button
                        type="button"
                        className={styles.chipRemove}
                        onClick={() => setGroupMembers((current) => current.filter((item) => item.id !== member.id))}
                        disabled={isStarting}
                        aria-label={t('chat.ui.remove_member')}
                      >
                        ×
                      </button>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className={styles.status}>{t('chat.ui.group_members_hint')}</p>
              )}
              <div className={styles.groupActions}>
                <button
                  type="submit"
                  className={styles.createGroup}
                  disabled={isStarting || !groupTitle.trim() || groupMembers.length < 1}
                >
                  {isStarting ? t('common.loading') : t('chat.ui.create_group')}
                </button>
                <button
                  type="button"
                  className={styles.modeButton}
                  onClick={resetComposer}
                  disabled={isStarting}
                >
                  {t('chat.ui.cancel_group')}
                </button>
              </div>
            </form>
          ) : (
            <button
              type="button"
              className={styles.modeButton}
              onClick={() => {
                setComposerMode('group')
                setUserQuery('')
                setUserResults([])
                setIsSearchOpen(false)
              }}
            >
              {t('chat.ui.new_group')}
            </button>
          )}
          <div className={styles.userCombobox}>
            <input
              className={styles.startInput}
              value={userQuery}
              onChange={(event) => {
                const value = event.target.value
                setUserQuery(value)
                setIsSearchOpen(true)
                if (value.trim().length >= 2) {
                  setIsSearchingUsers(true)
                }
              }}
              onFocus={() => setIsSearchOpen(true)}
              onBlur={() => {
                window.setTimeout(() => setIsSearchOpen(false), 120)
              }}
              placeholder={t('chat.ui.search_placeholder')}
              autoComplete="off"
              maxLength={100}
              role="combobox"
              aria-expanded={isSearchOpen && userQuery.trim().length >= 2}
              aria-autocomplete="list"
              disabled={isStarting}
            />
            {isSearchOpen && userQuery.trim().length >= 2 ? (
              <UserResultList
                results={userResults}
                isSearching={isSearchingUsers}
                onSelect={handleSelectUser}
                disabled={isStarting}
                excludeIds={composerMode === 'group' ? groupMemberIds : []}
                t={t}
              />
            ) : null}
          </div>
          {realtime !== undefined && !isReverbConfigured(realtime) ? (
            <p className={styles.warning}>{t('chat.ui.realtime_unavailable')}</p>
          ) : null}
          {isLoadingThreads ? <p className={styles.status}>{t('common.loading')}</p> : null}
          {!isLoadingThreads && threads.length === 0 ? (
            <p className={styles.status}>{t('chat.ui.empty_threads')}</p>
          ) : null}
          <ul className={styles.threadList}>
            {threads.map((thread) => {
              const unread = Number(thread.unread_count || 0)
              const avatar = threadAvatarUrl(thread, user?.id)
              return (
                <li key={thread.id}>
                  <button
                    type="button"
                    className={[
                      styles.threadButton,
                      Number(thread.id) === selectedId ? styles.threadButtonActive : '',
                    ].join(' ')}
                    onClick={() => {
                      setIsThreadListOpen(false)
                      navigate(`/chat/${thread.id}`)
                    }}
                  >
                    {avatar ? (
                      <img src={avatar} alt="" className={styles.threadAvatar} />
                    ) : (
                      <span className={styles.threadAvatar} aria-hidden="true" />
                    )}
                    <span className={styles.threadMeta}>
                      <strong>{threadTitle(thread, t)}</strong>
                      <small>{thread.last_message?.content || ''}</small>
                    </span>
                    {unread > 0 ? <span className={styles.unread}>{unread}</span> : null}
                  </button>
                </li>
              )
            })}
          </ul>
        </aside>

        <div className={styles.conversation}>
          {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}
          {!selectedId ? (
            <div className={styles.emptyConversation}>
              <button
                type="button"
                className={styles.openList}
                onClick={() => setIsThreadListOpen(true)}
              >
                {t('chat.ui.open_list')}
              </button>
              <p className={styles.status}>{t('chat.ui.select_thread')}</p>
            </div>
          ) : (
            <>
              <header className={styles.conversationHead}>
                <button
                  type="button"
                  className={styles.openList}
                  onClick={() => setIsThreadListOpen(true)}
                >
                  {t('chat.ui.open_list')}
                </button>
                <div className={styles.conversationTitle}>
                  <h2>{selectedThread ? threadTitle(selectedThread, t) : t('chat.ui.title')}</h2>
                  {showMembersPanel ? (
                    <p className={styles.membersCount}>
                      {t('chat.ui.members_count', { count: selectedMembers.length || 0 })}
                    </p>
                  ) : null}
                </div>
                {showMembersPanel ? (
                  <button
                    type="button"
                    className={styles.membersToggle}
                    onClick={() => setIsMembersOpen((open) => !open)}
                    aria-expanded={isMembersOpen}
                  >
                    {isMembersOpen ? t('chat.ui.close_members') : t('chat.ui.open_members')}
                  </button>
                ) : null}
              </header>
              {showMembersPanel && isMembersOpen ? (
                <section className={styles.membersPanel} aria-label={t('chat.ui.members')}>
                  {canManageMembers ? (
                    <div className={styles.userCombobox}>
                      <input
                        className={styles.startInput}
                        value={memberQuery}
                        onChange={(event) => {
                          const value = event.target.value
                          setMemberQuery(value)
                          setIsMemberSearchOpen(true)
                          if (value.trim().length >= 2) {
                            setIsSearchingMembers(true)
                          }
                        }}
                        onFocus={() => setIsMemberSearchOpen(true)}
                        onBlur={() => {
                          window.setTimeout(() => setIsMemberSearchOpen(false), 120)
                        }}
                        placeholder={t('chat.ui.add_member_placeholder')}
                        autoComplete="off"
                        maxLength={100}
                        role="combobox"
                        aria-expanded={isMemberSearchOpen && memberQuery.trim().length >= 2}
                        aria-autocomplete="list"
                        disabled={isManagingMember}
                      />
                      {isMemberSearchOpen && memberQuery.trim().length >= 2 ? (
                        <UserResultList
                          results={memberResults}
                          isSearching={isSearchingMembers}
                          onSelect={handleAddMember}
                          disabled={isManagingMember}
                          excludeIds={selectedMemberIds}
                          t={t}
                        />
                      ) : null}
                    </div>
                  ) : null}
                  <ul className={styles.memberList}>
                    {selectedMembers.map((member) => {
                      const avatar = resolvePublicUrl(member.avatar_url)
                      const isCreator = Number(member.id) === Number(selectedThread?.created_by_user_id)
                      return (
                        <li key={member.id} className={styles.memberRow}>
                          {avatar ? (
                            <img src={avatar} alt="" className={styles.suggestionAvatar} />
                          ) : (
                            <span className={styles.suggestionAvatar} aria-hidden="true" />
                          )}
                          <span className={styles.suggestionMeta}>
                            <span>{memberLabel(member) || t('chat.ui.you')}</span>
                            {isCreator ? <small>{t('chat.ui.creator')}</small> : null}
                          </span>
                          {canManageMembers && !isCreator ? (
                            <button
                              type="button"
                              className={styles.memberRemove}
                              onClick={() => handleRemoveMember(member)}
                              disabled={isManagingMember}
                            >
                              {t('chat.ui.remove_member')}
                            </button>
                          ) : null}
                        </li>
                      )
                    })}
                  </ul>
                </section>
              ) : null}
              <div className={styles.messages} ref={messagesPaneRef}>
                {isLoadingMessages ? <p className={styles.status}>{t('common.loading')}</p> : null}
                {!isLoadingMessages && messages.length === 0 ? (
                  <p className={styles.status}>{t('chat.ui.empty_messages')}</p>
                ) : null}
                {messages.map((message) => {
                  const mine = Number(message.author?.id) === Number(user?.id)
                  const avatar = resolvePublicUrl(message.author?.avatar_url)
                  return (
                    <article
                      key={message.id}
                      className={[styles.bubble, mine ? styles.bubbleMine : ''].join(' ')}
                    >
                      <div className={styles.bubbleHead}>
                        {avatar ? <img src={avatar} alt="" className={styles.messageAvatar} /> : null}
                        <span>{authorLabel(message, user?.id, t)}</span>
                      </div>
                      {message.reply_to ? (
                        <p className={styles.replyPreview}>{message.reply_to.content}</p>
                      ) : null}
                      <p>{message.content}</p>
                      {Array.isArray(message.attachments) && message.attachments.length > 0 ? (
                        <ul className={styles.attachments}>
                          {message.attachments.map((attachment) => (
                            <li key={attachment.id}>
                              <a href={resolvePublicUrl(attachment.url)} target="_blank" rel="noreferrer">
                                {attachment.original_name}
                              </a>
                            </li>
                          ))}
                        </ul>
                      ) : null}
                    </article>
                  )
                })}
              </div>
              <form className={styles.composer} onSubmit={handleSubmit}>
                <input
                  className={styles.input}
                  value={draft}
                  onChange={(event) => setDraft(event.target.value)}
                  placeholder={t('chat.ui.placeholder')}
                  maxLength={5000}
                />
                <button className={styles.send} type="submit" disabled={isSending || !draft.trim()}>
                  {isSending ? t('common.loading') : t('chat.ui.send')}
                </button>
              </form>
            </>
          )}
        </div>
      </section>
    </MainLayout>
  )
}
