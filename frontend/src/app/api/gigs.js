import { authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

const encode = (value) => encodeURIComponent(String(value ?? ''))

function readPaginated(data, meta, key) {
  return {
    items: Array.isArray(data?.[key]) ? data[key] : [],
    meta,
  }
}

export async function listOpenGigs(
  { page = 1, perPage = 10, search = '', type = '', sort = 'newest' } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  if (search) {
    params.set('q', String(search))
  }
  if (type) {
    params.set('type', String(type))
  }
  if (sort && sort !== 'newest') {
    params.set('sort', String(sort))
  }

  const { data, meta } = await authApiGet(`/api/v1/gigs/open?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load gigs',
  })

  return readPaginated(data, meta, 'gigs')
}

export async function listManagedGigs({ page = 1, perPage = 10, status = '' } = {}, { token, signal } = {}) {
  const params = new URLSearchParams()
  params.set('page', String(page))
  params.set('per_page', String(perPage))
  if (status) {
    params.set('status', String(status))
  }

  const { data, meta } = await authApiGet(`/api/v1/my/gigs?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load your gigs',
  })

  return readPaginated(data, meta, 'gigs')
}

export async function getGig(gigId, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/gigs/${encode(gigId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load gig',
  })
  return data?.gig ?? null
}

export async function createEventGig(eventId, payload, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/events/${encode(eventId)}/gigs`, payload, {
    token,
    fallbackMessage: 'Unable to create gig',
  })
  return data?.gig ?? null
}

export async function updateEventGig(eventId, gigId, payload, { token } = {}) {
  const { data } = await authApiPatch(`/api/v1/events/${encode(eventId)}/gigs/${encode(gigId)}`, payload, {
    token,
    fallbackMessage: 'Unable to update gig',
  })
  return data?.gig ?? null
}

export async function deleteEventGig(eventId, gigId, { token } = {}) {
  await authApiDelete(`/api/v1/events/${encode(eventId)}/gigs/${encode(gigId)}`, {
    token,
    fallbackMessage: 'Unable to delete gig',
  })
}

export async function createPoemTranslationGig(poemId, payload, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/poems/${encode(poemId)}/translation-gigs`, payload, {
    token,
    fallbackMessage: 'Unable to create translation gig',
  })
  return data?.translation_gig ?? null
}

export async function updatePoemTranslationGig(poemId, gigId, payload, { token } = {}) {
  const { data } = await authApiPatch(`/api/v1/poems/${encode(poemId)}/translation-gigs/${encode(gigId)}`, payload, {
    token,
    fallbackMessage: 'Unable to update translation gig',
  })
  return data?.translation_gig ?? null
}

export async function deletePoemTranslationGig(poemId, gigId, { token } = {}) {
  await authApiDelete(`/api/v1/poems/${encode(poemId)}/translation-gigs/${encode(gigId)}`, {
    token,
    fallbackMessage: 'Unable to delete translation gig',
  })
}

export async function closeGig(gigId, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/gigs/${encode(gigId)}/close`, {}, {
    token,
    fallbackMessage: 'Unable to close gig',
  })
  return data?.gig ?? null
}

export async function applyToGig(gigId, message, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/gigs/${encode(gigId)}/applications`, { message: message || null }, {
    token,
    fallbackMessage: 'Unable to send application',
  })
  return data?.application ?? null
}

export async function withdrawGigApplication(gigId, applicationId, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/gigs/${encode(gigId)}/applications/${encode(applicationId)}/withdraw`,
    {},
    { token, fallbackMessage: 'Unable to withdraw application' },
  )
  return data?.application ?? null
}

export async function listMyGigApplications({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/my/gig-applications', {
    token,
    signal,
    fallbackMessage: 'Unable to load applications',
  })
  return Array.isArray(data?.applications) ? data.applications : []
}

export async function listGigApplications(gigId, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/gigs/${encode(gigId)}/applications`, {
    token,
    signal,
    fallbackMessage: 'Unable to load applications',
  })
  return Array.isArray(data?.applications) ? data.applications : []
}

export async function updateGigApplicationStatus(gigId, applicationId, status, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/gigs/${encode(gigId)}/applications/${encode(applicationId)}`,
    { status },
    { token, fallbackMessage: 'Unable to update application' },
  )
  return { application: data?.application ?? null, gig: data?.gig ?? null }
}

export async function listGigAnnouncements(gigId, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/gigs/${encode(gigId)}/announcements`, {
    token,
    signal,
    fallbackMessage: 'Unable to load announcements',
  })
  return Array.isArray(data?.announcements) ? data.announcements : []
}

export async function createGigAnnouncement(gigId, payload, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/gigs/${encode(gigId)}/announcements`, payload, {
    token,
    fallbackMessage: 'Unable to publish announcement',
  })
  return data?.announcement ?? null
}

export async function updateGigAnnouncement(gigId, announcementId, payload, { token } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/gigs/${encode(gigId)}/announcements/${encode(announcementId)}`,
    payload,
    { token, fallbackMessage: 'Unable to update announcement' },
  )
  return data?.announcement ?? null
}

export async function deleteGigAnnouncement(gigId, announcementId, { token } = {}) {
  await authApiDelete(`/api/v1/gigs/${encode(gigId)}/announcements/${encode(announcementId)}`, {
    token,
    fallbackMessage: 'Unable to delete announcement',
  })
}

export async function listGigNegotiations(gigId, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/gigs/${encode(gigId)}/negotiations`, {
    token,
    signal,
    fallbackMessage: 'Unable to load negotiations',
  })
  return Array.isArray(data?.negotiations) ? data.negotiations : []
}

export async function openGigNegotiation(gigId, applicationId, payload, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/gigs/${encode(gigId)}/applications/${encode(applicationId)}/negotiations`,
    payload,
    { token, fallbackMessage: 'Unable to open negotiation' },
  )
  return data?.negotiation ?? null
}

export async function getGigNegotiation(negotiationId, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/gig-negotiations/${encode(negotiationId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load negotiation',
  })
  return data?.negotiation ?? null
}

export async function addGigNegotiationEvent(negotiationId, payload, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/gig-negotiations/${encode(negotiationId)}/events`, payload, {
    token,
    fallbackMessage: 'Unable to send proposal',
  })
  return data?.event ?? null
}

export async function agreeGigNegotiation(negotiationId, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/gig-negotiations/${encode(negotiationId)}/agree`, {}, {
    token,
    fallbackMessage: 'Unable to confirm agreement',
  })
  return data?.negotiation ?? null
}

export async function getTranslationJob(jobId, { token, signal } = {}) {
  const { data } = await authApiGet(`/api/v1/translation-jobs/${encode(jobId)}`, {
    token,
    signal,
    fallbackMessage: 'Unable to load translation job',
  })
  return data?.translation_job ?? null
}

export async function updateTranslationJob(jobId, payload, { token } = {}) {
  const { data } = await authApiPatch(`/api/v1/translation-jobs/${encode(jobId)}`, payload, {
    token,
    fallbackMessage: 'Unable to save translation',
  })
  return data?.translation_job ?? null
}

async function postTranslationJobAction(jobId, action, fallbackMessage, token) {
  const { data } = await authApiPost(`/api/v1/translation-jobs/${encode(jobId)}/${action}`, {}, {
    token,
    fallbackMessage,
  })
  return data?.translation_job ?? null
}

export function submitTranslationJob(jobId, { token } = {}) {
  return postTranslationJobAction(jobId, 'submit', 'Unable to submit translation', token)
}

export function requestTranslationChanges(jobId, { token } = {}) {
  return postTranslationJobAction(jobId, 'request-changes', 'Unable to request changes', token)
}

export function approveTranslationJob(jobId, { token } = {}) {
  return postTranslationJobAction(jobId, 'approve', 'Unable to approve translation', token)
}

export async function addTranslationComment(jobId, payload, { token } = {}) {
  const { data } = await authApiPost(`/api/v1/translation-jobs/${encode(jobId)}/comments`, payload, {
    token,
    fallbackMessage: 'Unable to add comment',
  })
  return data?.comment ?? null
}

export async function resolveTranslationComment(jobId, commentId, { token } = {}) {
  const { data } = await authApiPost(
    `/api/v1/translation-jobs/${encode(jobId)}/comments/${encode(commentId)}/resolve`,
    {},
    { token, fallbackMessage: 'Unable to resolve comment' },
  )
  return data?.comment ?? null
}
