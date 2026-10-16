import { getApiUrl } from './url.js'

export async function submitSupportRequest(formData, { token } = {}) {
  const headers = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  const response = await fetch(getApiUrl('/api/v1/support'), {
    method: 'POST',
    headers,
    body: formData,
  })
  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    const error = new Error(payload?.errors?.message || 'Support request failed')
    error.fields = payload?.errors?.fields || {}
    throw error
  }

  return payload?.data ?? null
}
