import { authApiDelete, authApiGet, authApiPatch, authApiPost } from './client.js'

export async function getMailSettings({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/mail-settings', {
    token,
    signal,
    fallbackMessage: 'Unable to load mail settings',
  })

  return data?.settings ?? null
}

export async function updateMailSettings(payload, { token, signal } = {}) {
  const { data } = await authApiPatch('/api/v1/admin/mail-settings', payload, {
    token,
    signal,
    fallbackMessage: 'Unable to save mail settings',
  })

  return data?.settings ?? null
}

export async function testMailSettings(email, { token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/admin/mail-settings/test', { email }, {
    token,
    signal,
    fallbackMessage: 'Unable to send test email',
  })

  return data?.message ?? ''
}

export async function sendTestNotificationEmails(category, userIds, { token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/admin/notification-emails/test', { category, user_ids: userIds }, {
    token,
    signal,
    fallbackMessage: 'Impossibile inviare le email di prova',
  })

  return { mailer: data?.mailer ?? '', results: Array.isArray(data?.results) ? data.results : [] }
}

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

export async function listAdminUsers(filters = {}, { token, signal } = {}) {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params.set(key, String(value))
    }
  })

  const { data, meta } = await authApiGet(`/api/v1/admin/users?${params.toString()}`, {
    token,
    signal,
    fallbackMessage: 'Impossibile caricare gli utenti',
  })

  return {
    users: Array.isArray(data?.users) ? data.users : [],
    roles: Array.isArray(data?.roles) ? data.roles : [],
    meta,
  }
}

export async function updateAdminUser(userId, payload, { token, signal } = {}) {
  const { data } = await authApiPatch(`/api/v1/admin/users/${encodeURIComponent(userId)}`, payload, {
    token,
    signal,
    fallbackMessage: 'Impossibile aggiornare l’utente',
  })

  return data?.user ?? null
}

export async function listAdminBackups({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/backups', {
    token,
    signal,
    fallbackMessage: 'Unable to load backups',
  })

  return data ?? {
    configured: false,
    reachable: false,
    healthy: false,
    disk: 'backups',
    used_bytes: 0,
    newest_at: null,
    error: null,
    backups: [],
    run: {
      state: 'idle',
      queued_at: null,
      finished_at: null,
      error: null,
    },
  }
}

export async function runAdminBackup({ token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/admin/backups', {}, {
    token,
    signal,
    fallbackMessage: 'Unable to start backup',
  })

  return data ?? { queued: true }
}

export async function deleteAdminUser(userId, { token, signal } = {}) {
  await authApiDelete(`/api/v1/admin/users/${encodeURIComponent(userId)}`, {
    token,
    signal,
    fallbackMessage: 'Impossibile eliminare l’utente',
  })
}
