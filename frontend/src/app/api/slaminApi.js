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
  const fields = payload?.errors?.fields
  if (fields && typeof fields === 'object') {
    const firstFieldError = Object.values(fields)
      .flat()
      .find((item) => typeof item === 'string' && item.trim() !== '')
    if (firstFieldError) {
      return firstFieldError
    }
  }

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
    email_verified_at: apiUser.email_verified_at ?? null,
    email_verified: Boolean(apiUser.email_verified ?? apiUser.email_verified_at),
    status: apiUser.status,
    roles: Array.isArray(apiUser.roles) ? apiUser.roles : [],
    name: nickname || displayName || apiUser.email || '',
    nickname,
    display_name: displayName || nickname,
    profile_photo_url: profile.avatar_url ?? apiUser.profile_photo_url ?? null,
    banner_image_url: profile.banner_url ?? apiUser.banner_image_url ?? null,
    account_type: apiUser.account_type ?? null,
    bio: profile.bio ?? '',
    location: profile.location ?? '',
    website_url: profile.website_url ?? '',
    is_public: profile.is_public !== false,
    visibility: profile.visibility ?? {},
    profile,
  }
}
