import { authApiGet, authApiPost } from './client.js'

export async function subscribeNewsletter(email, locale, { signal } = {}) {
  await authApiPost('/api/v1/newsletter/subscribe', { email, locale }, {
    signal,
    fallbackMessage: 'Impossibile completare l’iscrizione',
  })
}

export async function confirmNewsletter(token, { signal } = {}) {
  await authApiPost('/api/v1/newsletter/confirm', { token }, {
    signal,
    fallbackMessage: 'Link di conferma non valido',
  })
}

export async function unsubscribeNewsletter(token, { signal } = {}) {
  await authApiPost('/api/v1/newsletter/unsubscribe', { token }, {
    signal,
    fallbackMessage: 'Impossibile completare la disiscrizione',
  })
}

export async function getNewsletterAdmin({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/admin/newsletter', {
    token,
    signal,
    fallbackMessage: 'Impossibile caricare la newsletter',
  })

  return {
    subscriberCount: Number(data?.subscriber_count ?? 0),
    lastSentAt: data?.last_sent_at ?? null,
    campaigns: Array.isArray(data?.campaigns) ? data.campaigns : [],
  }
}

export async function sendNewsletterCampaign(payload, { token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/admin/newsletter/send', payload, {
    token,
    signal,
    fallbackMessage: 'Impossibile inviare la newsletter',
  })

  return data?.campaign ?? null
}
