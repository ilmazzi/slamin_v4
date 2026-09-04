/**
 * Helpers for Slamin Laravel API (`App\Support\Responses\ApiResponse`).
 * Success: { data, meta, errors: null }
 * Error:   { data: null, meta, errors: { message, fields } }
 */

export async function readJsonSafe(response) {
  const text = await response.text()
  if (!text) {
    return {}
  }
  try {
    return JSON.parse(text)
  } catch {
    return {}
  }
}

export function readApiErrorMessage(payload, fallback = '') {
  const fromEnvelope = payload?.errors?.message
  if (typeof fromEnvelope === 'string' && fromEnvelope.trim() !== '') {
    return fromEnvelope
  }
  const legacy = payload?.message
  if (typeof legacy === 'string' && legacy.trim() !== '') {
    return legacy
  }
  return fallback
}

/**
 * Flatten nested `profile` so existing UI (`user.name`, `display_name`, …) keeps working.
 */
export function normalizeSessionUser(apiUser) {
  if (!apiUser || typeof apiUser !== 'object') {
    return null
  }

  const profile = apiUser.profile && typeof apiUser.profile === 'object' ? apiUser.profile : {}
  const displayName = profile.display_name ?? profile.nickname ?? ''
  const nickname = profile.nickname ?? ''

  return {
    ...apiUser,
    id: apiUser.id ?? apiUser.public_id,
    public_id: apiUser.public_id,
    email: apiUser.email,
    status: apiUser.status,
    name: nickname || displayName || apiUser.email || '',
    nickname,
    display_name: displayName || nickname,
    profile,
  }
}
