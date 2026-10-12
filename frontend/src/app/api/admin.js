import { authApiGet, authApiPatch } from './client.js'

export async function listContentPublicationSettings({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/content-publication-settings', {
    token,
    signal,
    fallbackMessage: 'Unable to load publication settings',
  })

  return Array.isArray(data?.settings) ? data.settings : []
}

export async function updateContentPublicationSetting(settingId, payload, { token, signal } = {}) {
  const { data } = await authApiPatch(
    `/api/v1/admin/content-publication-settings/${encodeURIComponent(settingId)}`,
    payload,
    {
      token,
      signal,
      fallbackMessage: 'Unable to save publication settings',
    },
  )

  return data?.setting ?? null
}
