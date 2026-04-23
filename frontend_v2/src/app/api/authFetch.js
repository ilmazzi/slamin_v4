import { getApiUrl } from './url.js'

/**
 * Shared helper for authenticated API calls.
 */
export async function authFetch(path, { token, method = 'GET', headers = {}, ...options } = {}) {
  if (!token) {
    throw new Error('Missing auth token')
  }

  return fetch(getApiUrl(path), {
    method,
    ...options,
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      ...headers,
    },
  })
}
