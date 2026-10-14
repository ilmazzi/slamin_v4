import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { getApiUrl } from '../api/url.js'

window.Pusher = Pusher

export function isReverbConfigured() {
  return Boolean(import.meta.env.VITE_REVERB_APP_KEY && import.meta.env.VITE_REVERB_HOST)
}

export function createEcho(token) {
  if (!token || !isReverbConfigured()) {
    return null
  }

  return new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: getApiUrl('/broadcasting/auth'),
    bearerToken: token,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })
}
