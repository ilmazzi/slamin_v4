import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  createDirectChatThread,
  listChatMessages,
  listChatThreads,
  markChatThreadRead,
  sendChatMessage,
} from '../../app/api/chat.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { createEcho, isReverbConfigured } from '../../app/realtime/echo.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './ChatPage.module.css'

function threadTitle(thread, t) {
  if (thread?.type === 'direct') {
    return thread.other_user?.display_name || thread.other_user?.nickname || t('chat.ui.you')
  }
  return thread?.title || thread?.group?.name || t('chat.ui.group_fallback')
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

export default function ChatPage() {
  const { threadId } = useParams()
  const navigate = useNavigate()
  const { t } = useI18n()
  const { token, user } = useAuth()
  const [threads, setThreads] = useState([])
  const [messages, setMessages] = useState([])
  const [draft, setDraft] = useState('')
  const [nicknameDraft, setNicknameDraft] = useState('')
  const [errorMessage, setErrorMessage] = useState('')
  const [isLoadingThreads, setIsLoadingThreads] = useState(true)
  const [isLoadingMessages, setIsLoadingMessages] = useState(false)
  const [isSending, setIsSending] = useState(false)
  const [isStarting, setIsStarting] = useState(false)
  const [echoClient, setEchoClient] = useState(null)
  const selectedIdRef = useRef(null)
  const messagesEndRef = useRef(null)
  const selectedId = threadId ? Number(threadId) : null
  selectedIdRef.current = selectedId

  const loadThreads = useCallback(async ({ signal } = {}) => {
    if (!token) {
      return
    }
    const nextThreads = await listChatThreads({ token, signal })
    setThreads(nextThreads)
  }, [token])

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
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  useEffect(() => {
    if (!token || !user?.id) {
      return undefined
    }

    const echo = createEcho(token)
    setEchoClient(echo)
    if (!echo) {
      return undefined
    }

    echo.private(`chat.user.${user.id}`).listen('.message.sent', (payload) => {
      const incoming = payload?.message
      const incomingThreadId = Number(payload?.thread_id ?? incoming?.thread_id)
      if (incoming && incomingThreadId === selectedIdRef.current) {
        setMessages((current) => upsertMessage(current, incoming))
      }
      loadThreads().catch(() => null)
    })

    return () => {
      echo.leave(`chat.user.${user.id}`)
      echo.disconnect()
      setEchoClient(null)
    }
  }, [loadThreads, token, user?.id])

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

  async function handleStartConversation(event) {
    event.preventDefault()
    const nickname = nicknameDraft.trim()
    if (!nickname || isStarting) {
      return
    }

    setIsStarting(true)
    setErrorMessage('')
    try {
      const thread = await createDirectChatThread({ nickname }, { token })
      if (thread?.id) {
        setNicknameDraft('')
        await loadThreads()
        navigate(`/chat/${thread.id}`)
      }
    } catch (error) {
      setErrorMessage(error?.message || t('chat.ui.start_error'))
    } finally {
      setIsStarting(false)
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

  return (
    <MainLayout>
      <section className={styles.page}>
        <aside className={styles.sidebar}>
          <h1 className={styles.title}>{t('chat.ui.title')}</h1>
          <form className={styles.startForm} onSubmit={handleStartConversation}>
            <input
              className={styles.startInput}
              value={nicknameDraft}
              onChange={(event) => setNicknameDraft(event.target.value)}
              placeholder={t('chat.ui.nickname_placeholder')}
              autoComplete="off"
              maxLength={40}
            />
            <button className={styles.startButton} type="submit" disabled={isStarting || !nicknameDraft.trim()}>
              {isStarting ? t('common.loading') : t('chat.ui.start')}
            </button>
          </form>
          {!isReverbConfigured() ? (
            <p className={styles.warning}>{t('chat.ui.realtime_unavailable')}</p>
          ) : null}
          {isLoadingThreads ? <p className={styles.status}>{t('common.loading')}</p> : null}
          {!isLoadingThreads && threads.length === 0 ? (
            <p className={styles.status}>{t('chat.ui.empty_threads')}</p>
          ) : null}
          <ul className={styles.threadList}>
            {threads.map((thread) => {
              const unread = Number(thread.unread_count || 0)
              const avatar = resolvePublicUrl(thread.other_user?.avatar_url)
              return (
                <li key={thread.id}>
                  <button
                    type="button"
                    className={[
                      styles.threadButton,
                      Number(thread.id) === selectedId ? styles.threadButtonActive : '',
                    ].join(' ')}
                    onClick={() => navigate(`/chat/${thread.id}`)}
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
            <p className={styles.status}>{t('chat.ui.select_thread')}</p>
          ) : (
            <>
              <header className={styles.conversationHead}>
                <h2>{selectedThread ? threadTitle(selectedThread, t) : t('chat.ui.title')}</h2>
              </header>
              <div className={styles.messages}>
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
                <div ref={messagesEndRef} />
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
