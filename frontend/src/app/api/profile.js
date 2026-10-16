import { apiGet, authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

export const PROFILE_SECTIONS = ['poems', 'events', 'articles', 'media']
export const SOCIAL_PLATFORMS = [
  { id: 'instagram', label: 'Instagram' },
  { id: 'facebook', label: 'Facebook' },
  { id: 'youtube', label: 'YouTube' },
  { id: 'tiktok', label: 'TikTok' },
  { id: 'twitter', label: 'X / Twitter' },
  { id: 'bluesky', label: 'Bluesky' },
  { id: 'mastodon', label: 'Mastodon' },
]

const DEFAULT_VISIBILITY = {
  bio: true,
  location: true,
  website_url: true,
  email: false,
  ...Object.fromEntries(PROFILE_SECTIONS.map((section) => [section, true])),
}

function appendIfDefined(formData, key, value) {
  if (value === undefined) {
    return
  }
  if (value === null) {
    formData.append(key, '')
    return
  }
  formData.append(key, String(value))
}

export function defaultVisibility(visibility) {
  return {
    ...DEFAULT_VISIBILITY,
    ...(visibility && typeof visibility === 'object' ? visibility : {}),
  }
}

export function ownerProfileFromUser(user) {
  if (!user) {
    return null
  }

  const profile = user.profile && typeof user.profile === 'object' ? user.profile : {}

  return {
    public_id: profile.public_id ?? null,
    nickname: profile.nickname ?? user.nickname ?? '',
    display_name: profile.display_name ?? user.display_name ?? '',
    bio: profile.bio ?? user.bio ?? '',
    location: profile.location ?? user.location ?? '',
    website_url: profile.website_url ?? user.website_url ?? '',
    social_links: Array.isArray(profile.social_links) ? profile.social_links : [],
    spoken_languages: Array.isArray(profile.spoken_languages) ? profile.spoken_languages : [],
    preferred_locale: profile.preferred_locale ?? null,
    is_public: profile.is_public ?? user.is_public ?? true,
    visibility: defaultVisibility(profile.visibility ?? user.visibility),
    avatar_url: profile.avatar_url ?? user.profile_photo_url ?? null,
    banner_url: profile.banner_url ?? user.banner_image_url ?? null,
    email: profile.email ?? user.email ?? null,
    is_owner: true,
  }
}

export function normalizeWebsiteUrl(value) {
  const trimmed = String(value ?? '').trim()
  if (!trimmed) {
    return null
  }
  if (/^https?:\/\//i.test(trimmed)) {
    return trimmed
  }
  return `https://${trimmed}`
}

export function normalizeSocialLinks(platformValues, customLinks = []) {
  const platformLinks = SOCIAL_PLATFORMS.flatMap(({ id, label }) => {
    const url = normalizeWebsiteUrl(platformValues?.[id])
    return url ? [{ platform: id, label, url }] : []
  })

  const additionalLinks = customLinks.flatMap((link) => {
    const label = String(link?.label ?? '').trim()
    const url = normalizeWebsiteUrl(link?.url)
    return label && url ? [{ platform: 'custom', label, url }] : []
  })

  return [...platformLinks, ...additionalLinks]
}

export function normalizeSpokenLanguages(value) {
  const seen = new Set()

  return String(value ?? '')
    .split(',')
    .map((language) => language.trim())
    .filter((language) => {
      const key = language.toLocaleLowerCase()
      if (!language || seen.has(key)) {
        return false
      }
      seen.add(key)
      return true
    })
    .slice(0, 20)
}

export async function getPublicProfile(nickname, { token, signal } = {}) {
  const safeNickname = String(nickname ?? '').trim()
  if (!safeNickname) {
    return { ok: false, status: 400, profile: null }
  }

  const path = `/api/v1/profiles/${encodeURIComponent(safeNickname)}`

  if (token) {
    const result = await authApiGet(path, {
      token,
      signal,
      fallbackMessage: 'Unable to load profile.',
      allowStatuses: [404],
    })

    return {
      ok: result.ok,
      status: result.status,
      profile: result.data?.profile ?? null,
      stats: result.data?.stats ?? null,
      follow: result.data?.follow ?? null,
    }
  }

  try {
    const result = await apiGet(path, {
      signal,
      fallbackMessage: 'Unable to load profile.',
    })
    return {
      ok: true,
      status: 200,
      profile: result.data?.profile ?? null,
      stats: result.data?.stats ?? null,
      follow: result.data?.follow ?? null,
    }
  } catch {
    return { ok: false, status: 404, profile: null, stats: null, follow: null }
  }
}

export async function getPublicProfileActivities(nickname, { page = 1, token, signal } = {}) {
  const path = `/api/v1/profiles/${encodeURIComponent(String(nickname ?? '').trim())}/activities?page=${page}`
  const options = { signal, fallbackMessage: 'Unable to load activities.' }
  const result = token ? await authApiGet(path, { ...options, token }) : await apiGet(path, options)

  return result.data?.recent_activities ?? null
}

export async function setProfileFollow(nickname, following, { token, signal } = {}) {
  const path = `/api/v1/profiles/${encodeURIComponent(String(nickname ?? '').trim())}/follow`
  const options = { token, signal, fallbackMessage: 'Unable to update follow.' }
  const result = following ? await authApiPost(path, {}, options) : await authApiDelete(path, options)

  return result.data?.follow ?? null
}

export async function updateMyProfile(values, { token, signal } = {}) {
  const hasAvatar = typeof File !== 'undefined' && values?.avatar instanceof File
  const hasBanner = typeof File !== 'undefined' && values?.banner instanceof File
  let body = values

  if (hasAvatar || hasBanner || values?.remove_avatar || values?.remove_banner) {
    const formData = new FormData()
    Object.entries(values || {}).forEach(([key, value]) => {
      if (key === 'avatar' || key === 'banner') {
        return
      }
      if (['visibility', 'social_links', 'spoken_languages'].includes(key) && value && typeof value === 'object') {
        formData.append(key, JSON.stringify(value))
        return
      }
      if (typeof value === 'boolean') {
        formData.append(key, value ? '1' : '0')
        return
      }
      appendIfDefined(formData, key, value)
    })
    if (hasAvatar) {
      formData.append('avatar', values.avatar)
    }
    if (hasBanner) {
      formData.append('banner', values.banner)
    }
    body = formData
  }

  const result = await authApiPatch('/api/v1/me/profile', body, {
    token,
    signal,
    fallbackMessage: 'Unable to update profile.',
  })

  return result.data?.profile ?? null
}
