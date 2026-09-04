import { apiGet } from './client.js'

export async function getHomeMedia(locale, { signal } = {}) {
  const path = `/api/v1/home/media?locale=${encodeURIComponent(locale)}`
  const { data } = await apiGet(path, {
    signal,
    fallbackMessage: 'Unable to load home media',
  })

  return data?.community_media?.items ?? []
}

export async function getHomeStats(locale, { signal } = {}) {
  const path = `/api/v1/home/stats?locale=${encodeURIComponent(locale)}`
  const { data } = await apiGet(path, {
    signal,
    fallbackMessage: 'Unable to load home stats',
  })

  return data?.community_stats ?? null
}

