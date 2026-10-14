import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { getApiUrl } from '../api/url.js'

window.Pusher = Pusher

export function isReverbConfigured(config) {
  return Boolean(config?.enabled && config.key && config.host)
}

export function createEcho(token, config) {
  if (!token || !isReverbConfigured(config)) {
    return null
  }

  return new Echo({
    broadcaster: 'reverb',
    key: config.key,
    wsHost: config.host,
    wsPort: Number(config.port ?? 80),
    wssPort: Number(config.port ?? 443),
    forceTLS: (config.scheme ?? 'https') === 'https',
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
