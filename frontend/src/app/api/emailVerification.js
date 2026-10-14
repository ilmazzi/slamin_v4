import { authApiPost } from './client.js'
import { getApiUrl } from './url.js'
import { localeHeaders } from '../i18n/locale.js'
import { readApiErrorMessage, readJsonSafe } from './slaminApi.js'

export async function verifyEmail(params, { signal } = {}) {
  const response = await fetch(getApiUrl('/api/v1/email/verify'), {
    method: 'POST',
    signal,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...localeHeaders(),
    },
    body: JSON.stringify({
      id: params.get('id'),
      hash: params.get('hash'),
      expires: params.get('expires'),
      signature: params.get('signature'),
    }),
  })
  const payload = await readJsonSafe(response)

  if (!response.ok) {
    throw new Error(readApiErrorMessage(payload, 'Unable to verify email'))
  }

  return payload?.data ?? null
}

export async function resendEmailVerification({ token, signal } = {}) {
  const { data } = await authApiPost('/api/v1/email/verification-notification', {}, {
    token,
    signal,
    fallbackMessage: 'Unable to resend verification email',
  })

  return data ?? null
}
