import { authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

export async function listGroups(
  { page = 1, perPage = 12, search = '' } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  if (search) {
    params.set('q', String(search))
  }

  const { data, meta } = await authApiGet(`/api/v1/groups?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load groups',
  })

  return {
    items: Array.isArray(data?.groups) ? data.groups : [],
    meta,
  }
}

export async function getGroup(slug, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/groups/${encodeURIComponent(slug)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load group',
  })

  return data?.group ?? null
}

function appendIfPresent(formData, key, value) {
  if (value === undefined || value === null || value === '') return
  formData.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value))
}

export function buildGroupPayload(values) {
  const hasFile = typeof File !== 'undefined' && values?.image instanceof File
  const chatAudiences = Array.isArray(values?.chat_audiences) ? values.chat_audiences : undefined
  if (!hasFile) {
    return {
      ...values,
      chat_audiences: chatAudiences,
      image: undefined,
    }
  }

  const formData = new FormData()
  Object.entries(values || {}).forEach(([key, value]) => {
    if (key === 'image' || key === 'chat_audiences') return
    appendIfPresent(formData, key, value)
  })
  if (chatAudiences) {
    chatAudiences.forEach((audience) => formData.append('chat_audiences[]', String(audience)))
  }
  formData.append('image', values.image)
  return formData
}

function appendMethodOverride(formData) {
  formData.append('_method', 'PATCH')
  return formData
}

export async function updateGroupChatThreads(slug, audiences, { token } = {}) {
  const { data } = await authApiPatch(`/api/v1/groups/${encodeURIComponent(slug)}/chat-threads`, {
    audiences,
  }, {
    token,
    fallbackMessage: 'Unable to update group chats',
  })

  return Array.isArray(data?.threads) ? data.threads : []
}

export async function createGroup(payload, { token } = {}) {
  const { data } = await authApiPost('/api/v1/groups', buildGroupPayload(payload), {
    token,
    fallbackMessage: 'Unable to create group',
  })

  return data?.group ?? null
}

export async function updateGroup(slug, payload, { token } = {}) {
  const body = buildGroupPayload(payload)
  const path = `/api/v1/groups/${encodeURIComponent(slug)}`
  const options = { token, fallbackMessage: 'Unable to update group' }
  const { data } = typeof FormData !== 'undefined' && body instanceof FormData
    ? await authApiPost(path, appendMethodOverride(body), options)
    : await authApiPatch(path, body, options)

  return data?.group ?? null
}

export async function followGroup(slug, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/groups/${encodeURIComponent(slug)}/follow`, {}, {
    token,
    fallbackMessage: 'Unable to follow group',
  })

  return data?.membership ?? null
}

export async function requestGroupRole(slug, requestedRole, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/role-requests`,
    { requested_role: requestedRole },
    {
      token,
      fallbackMessage: 'Unable to request group role',
    },
  )

  return data?.membership ?? null
}

export async function listGroupMembers(slug, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/groups/${encodeURIComponent(slug)}/members`, {
    token,
    signal,
    fallbackMessage: 'Unable to load group members',
  })

  return Array.isArray(data?.members) ? data.members : []
}

export async function listGroupRequests(slug, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/groups/${encodeURIComponent(slug)}/requests`, {
    token,
    signal,
    fallbackMessage: 'Unable to load group requests',
  })

  return Array.isArray(data?.requests) ? data.requests : []
}

export async function searchGroupInviteUsers(slug, query, { token, signal } = {}) {
  const params = new URLSearchParams({ q: String(query ?? '').trim() })
  const { data } = await authApiGet(
    `/api/v1/groups/${encodeURIComponent(slug)}/invite-users?${params.toString()}`,
    {
      token,
      signal,
      fallbackMessage: 'Unable to search users',
    },
  )

  return Array.isArray(data?.users) ? data.users : []
}

export async function inviteGroupMember(slug, payload, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/invites`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to invite user',
    },
  )

  return data?.membership ?? null
}

export async function approveGroupRequest(slug, memberId, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/requests/${encodeURIComponent(memberId)}/approve`,
    {},
    {
      token,
      fallbackMessage: 'Unable to approve request',
    },
  )

  return data?.membership ?? null
}

export async function rejectGroupRequest(slug, memberId, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/requests/${encodeURIComponent(memberId)}/reject`,
    {},
    {
      token,
      fallbackMessage: 'Unable to reject request',
    },
  )

  return data?.membership ?? null
}

export async function updateGroupMemberRole(slug, memberId, role, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/groups/${encodeURIComponent(slug)}/members/${encodeURIComponent(memberId)}/role`,
    { role },
    {
      token,
      fallbackMessage: 'Unable to update member role',
    },
  )

  return data?.membership ?? null
}

export async function removeGroupMember(slug, memberId, { token } = {}) {
  const { data } = await authApiDelete(
    `/api/v1/groups/${encodeURIComponent(slug)}/members/${encodeURIComponent(memberId)}`,
    {
      token,
      fallbackMessage: 'Unable to remove member',
    },
  )

  return data?.membership ?? null
}

export async function leaveGroup(slug, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/groups/${encodeURIComponent(slug)}/leave`, {}, {
    token,
    fallbackMessage: 'Unable to leave group',
  })

  return data?.membership ?? null
}

export async function listMyGroups(
  { page = 1, perPage = 12 } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))

  const { data, meta } = await authApiGet(`/api/v1/my/groups?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load groups',
  })

  return {
    items: Array.isArray(data?.groups) ? data.groups : [],
    meta,
  }
}

export async function listGroupCategories({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/group-categories', {
    token,
    signal,
    fallbackMessage: 'Unable to load group categories',
  })

  return Array.isArray(data?.categories) ? data.categories : []
}

export async function adminListGroupCategories({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/group-categories', {
    token,
    signal,
    fallbackMessage: 'Unable to load group categories',
  })

  return Array.isArray(data?.categories) ? data.categories : []
}

export async function adminStoreGroupCategory(payload, { token } = {}) {
  const { data } = await authApiPost('/api/v1/admin/group-categories', payload, {
    token,
    fallbackMessage: 'Unable to save group category',
  })

  return data?.category ?? null
}

export async function adminUpdateGroupCategory(id, payload, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/admin/group-categories/${encodeURIComponent(id)}`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to save group category',
    },
  )

  return data?.category ?? null
}

export async function adminDeleteGroupCategory(id, { token } = {}) {
  await authApiDelete(`/api/v1/admin/group-categories/${encodeURIComponent(id)}`, {
    token,
    fallbackMessage: 'Unable to delete group category',
  })
}

export async function listGroupAnnouncements(slug, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/groups/${encodeURIComponent(slug)}/announcements`, {
    token,
    signal,
    fallbackMessage: 'Unable to load group feed',
  })

  return Array.isArray(data?.announcements) ? data.announcements : []
}

export async function createGroupAnnouncement(slug, payload, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/announcements`,
    payload,
    {
      token,
      fallbackMessage: 'Unable to publish announcement',
    },
  )

  return data?.announcement ?? null
}

export async function searchProposeableGroups(query, { token, signal } = {}) {
  const params = new URLSearchParams({ q: String(query ?? '').trim() })
  const { data } = await authApiGet(`/api/v1/groups/propose?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to search groups',
  })

  return Array.isArray(data?.groups) ? data.groups : []
}

export async function listGroupPublications(slug, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/groups/${encodeURIComponent(slug)}/publications`, {
    token,
    signal,
    fallbackMessage: 'Unable to load group publications',
  })

  return {
    poems: Array.isArray(data?.poems) ? data.poems : [],
    articles: Array.isArray(data?.articles) ? data.articles : [],
  }
}

export async function listGroupEvents(slug, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/groups/${encodeURIComponent(slug)}/events`, {
    token,
    signal,
    fallbackMessage: 'Unable to load group events',
  })

  return Array.isArray(data?.events) ? data.events : []
}

export async function listGroupContentLinks(slug, { status = 'pending', token, signal } = {}) {
  const params = new URLSearchParams()
  if (status) params.set('status', status)
  const { data } = await authApiGet(
    `/api/v1/groups/${encodeURIComponent(slug)}/content-links?${params.toString()}`,
    {
      token,
      signal,
      fallbackMessage: 'Unable to load proposed events',
    },
  )

  return Array.isArray(data?.links) ? data.links : []
}

export async function acceptGroupContentLink(slug, linkId, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/content-links/${encodeURIComponent(linkId)}/accept`,
    {},
    { token, fallbackMessage: 'Unable to accept event' },
  )

  return data?.link ?? null
}

export async function rejectGroupContentLink(slug, linkId, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/content-links/${encodeURIComponent(linkId)}/reject`,
    {},
    { token, fallbackMessage: 'Unable to reject event' },
  )

  return data?.link ?? null
}

export async function removeGroupContentLink(slug, linkId, { token } = {}) {
  const { data } = await authApiDelete(
    `/api/v1/groups/${encodeURIComponent(slug)}/content-links/${encodeURIComponent(linkId)}`,
    { token, fallbackMessage: 'Unable to remove event' },
  )

  return data?.link ?? null
}

export async function listGroupAffiliations(slug, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/groups/${encodeURIComponent(slug)}/affiliations`, {
    token,
    signal,
    fallbackMessage: 'Unable to load affiliations',
  })

  return {
    parents: Array.isArray(data?.parents) ? data.parents : [],
    children: Array.isArray(data?.children) ? data.children : [],
    incoming: Array.isArray(data?.incoming) ? data.incoming : [],
    outgoing: Array.isArray(data?.outgoing) ? data.outgoing : [],
  }
}

export async function searchAffiliableGroups(slug, query, { token, signal } = {}) {
  const params = new URLSearchParams({ q: String(query ?? '').trim() })
  const { data } = await authApiGet(
    `/api/v1/groups/${encodeURIComponent(slug)}/affiliable?${params.toString()}`,
    {
      token,
      signal,
      fallbackMessage: 'Unable to search groups',
    },
  )

  return Array.isArray(data?.groups) ? data.groups : []
}

export async function requestGroupAffiliation(slug, payload, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/affiliations`,
    payload,
    { token, fallbackMessage: 'Unable to request affiliation' },
  )

  return data?.affiliation ?? null
}

export async function acceptGroupAffiliation(slug, affiliationId, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/affiliations/${encodeURIComponent(affiliationId)}/accept`,
    {},
    { token, fallbackMessage: 'Unable to accept affiliation' },
  )

  return data?.affiliation ?? null
}

export async function rejectGroupAffiliation(slug, affiliationId, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/groups/${encodeURIComponent(slug)}/affiliations/${encodeURIComponent(affiliationId)}/reject`,
    {},
    { token, fallbackMessage: 'Unable to reject affiliation' },
  )

  return data?.affiliation ?? null
}

export async function deleteGroupAnnouncement(slug, announcementId, { token } = {}) {
  await authApiDelete(
    `/api/v1/groups/${encodeURIComponent(slug)}/announcements/${encodeURIComponent(announcementId)}`,
    {
      token,
      fallbackMessage: 'Unable to delete announcement',
    },
  )
}
