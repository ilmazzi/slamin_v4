import { authFetch } from './authFetch.js'
import { getApiUrl } from './url.js'
import { localeHeaders } from '../i18n/locale.js'
import { readApiErrorMessage, readJsonSafe } from './slaminApi.js'

function normalizeMeta(payload) {
  return payload?.meta && typeof payload.meta === 'object' ? payload.meta : {}
}

export async function apiGet(path, { signal, cache = 'no-store', fallbackMessage = 'Request failed' } = {}) {
  const response = await fetch(getApiUrl(path), {
    signal,
    headers: { Accept: 'application/json', ...localeHeaders() },
    cache,
  })

  const payload = await readJsonSafe(response)

  if (!response.ok) {
    throw new Error(readApiErrorMessage(payload, fallbackMessage))
  }

  return {
    data: payload?.data,
    meta: normalizeMeta(payload),
    payload,
  }
}

export async function authApiRequest(
  path,
  {
    token,
    signal,
    method = 'GET',
    headers = {},
    body,
    cache = 'no-store',
    fallbackMessage = 'Request failed',
    allowStatuses = [],
  } = {},
) {
  const response = await authFetch(path, {
    token,
    signal,
    method,
    headers,
    body,
    cache,
  })
  const payload = await readJsonSafe(response)

  if (!response.ok && !allowStatuses.includes(response.status)) {
    throw new Error(readApiErrorMessage(payload, fallbackMessage))
  }

  return {
    ok: response.ok,
    status: response.status,
    data: payload?.data,
    meta: normalizeMeta(payload),
    payload,
  }
}

export async function authApiGet(
  path,
  { token, signal, fallbackMessage = 'Request failed', allowStatuses = [] } = {},
) {
  return authApiRequest(path, {
    token,
    signal,
    fallbackMessage,
    allowStatuses,
  })
}

export async function authApiPost(path, data, {token, signal, cache = 'no-store', fallbackMessage = 'Request failed' } = {}) {
  const isFormData = typeof FormData !== 'undefined' && data instanceof FormData

  return authApiRequest(path, {
    token,
    signal,
    method: 'POST',
    headers: isFormData
      ? { Accept: 'application/json' }
      : { Accept: 'application/json', 'Content-Type': 'application/json' },
    cache,
    fallbackMessage,
    allowStatuses: [200, 201],
    body: isFormData ? data : JSON.stringify(data),
  })
}

export async function authApiPut(path, data, { token, signal, fallbackMessage = 'Request failed' } = {}) {
  return authApiRequest(path, {
    token,
    signal,
    method: 'PUT',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    fallbackMessage,
    body: JSON.stringify(data),
  })
}

export async function authApiPatch(path, data, { token, signal, fallbackMessage = 'Request failed' } = {}) {
  const isFormData = typeof FormData !== 'undefined' && data instanceof FormData

  return authApiRequest(path, {
    token,
    signal,
    method: 'PATCH',
    headers: isFormData
      ? { Accept: 'application/json' }
      : { Accept: 'application/json', 'Content-Type': 'application/json' },
    fallbackMessage,
    body: isFormData ? data : JSON.stringify(data),
  })
}

export async function authApiDelete(path, { token, signal, fallbackMessage = 'Request failed' } = {}) {
  return authApiRequest(path, {
    token,
    signal,
    method: 'DELETE',
    headers: { Accept: 'application/json' },
    fallbackMessage,
  })
}
