import { authApiGet, authApiPost, authApiRequest } from './client.js'

export async function searchChatUsers(query, { token, signal } = {}) {
  const params = new URLSearchParams({ q: String(query ?? '').trim() })
  const { data } = await authApiGet(`/api/v1/chat/users?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to search users',
  })

  return Array.isArray(data?.users) ? data.users : []
}

export async function listChatThreads({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/chat/threads', {
    token,
    signal,
    fallbackMessage: 'Unable to load chat threads',
  })
  return Array.isArray(data?.threads) ? data.threads : []
}

export async function getChatThread(threadId, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/chat/threads/${encodeURIComponent(threadId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load chat',
  })
  return data?.thread ?? null
}

export async function listChatMessages(threadId, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/chat/threads/${encodeURIComponent(threadId)}/messages`, {
    token,
    signal,
    fallbackMessage: 'Unable to load messages',
  })
  return Array.isArray(data?.messages) ? data.messages : []
}

export async function sendChatMessage(threadId, payload, { token, signal } = {}) {
  const { data } = await authApiPost(
    `/api/v1/chat/threads/${encodeURIComponent(threadId)}/messages`,
    payload,
    { token, signal, fallbackMessage: 'Unable to send message' },
  )
  return data?.message ?? null
}

export async function markChatThreadRead(threadId, { token, signal } = {}) {
  await authApiPost(
    `/api/v1/chat/threads/${encodeURIComponent(threadId)}/read`,
    {},
    { token, signal, fallbackMessage: 'Unable to mark chat as read' },
  )
}

export async function createDirectChatThread(payload, { token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/chat/direct-threads', payload, {
    token,
    signal,
    fallbackMessage: 'Unable to open chat',
  })
  return data?.thread ?? null
}

export async function addChatMessageReaction(messageId, emoji, { token, signal } = {}) {
  const { data } = await authApiPost(
    `/api/v1/chat/messages/${encodeURIComponent(messageId)}/reactions`,
    { emoji },
    { token, signal, fallbackMessage: 'Unable to add reaction' },
  )
  return data?.message ?? null
}

export async function removeChatMessageReaction(messageId, emoji, { token, signal } = {}) {
  const { data } = await authApiRequest(
    `/api/v1/chat/messages/${encodeURIComponent(messageId)}/reactions`,
    {
      token,
      signal,
      method: 'DELETE',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ emoji }),
      fallbackMessage: 'Unable to remove reaction',
    },
  )
  return data?.message ?? null
}
