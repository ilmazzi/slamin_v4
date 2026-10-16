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
    authEndpoint: getApiUrl('/api/broadcasting/auth'),
    bearerToken: token,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })
}

const sharedConnections = new Map()

function connectionKey(token, config) {
  return [token, config.key, config.host, config.port, config.scheme].join('|')
}

// Shared clients must never `leave()` a channel another consumer may still listen to: use stopListening instead.
export function acquireEcho(token, config) {
  if (!token || !isReverbConfigured(config)) {
    return null
  }

  const key = connectionKey(token, config)
  let entry = sharedConnections.get(key)
  if (!entry) {
    entry = { echo: createEcho(token, config), refs: 0 }
    sharedConnections.set(key, entry)
  }

  entry.refs += 1
  return entry.echo
}

export function releaseEcho(echo) {
  for (const [key, entry] of sharedConnections) {
    if (entry.echo !== echo) {
      continue
    }

    entry.refs -= 1
    if (entry.refs <= 0) {
      sharedConnections.delete(key)
      entry.echo.disconnect()
    }
    return
  }
}
