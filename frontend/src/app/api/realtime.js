import { authApiGet } from './client.js'

export async function getRealtimeConfig({ token, signal } = {}) {
  const { data } = await authApiGet('/api/v1/realtime', {
    token,
    signal,
    fallbackMessage: 'Unable to load realtime configuration',
  })

  return data?.realtime ?? null
}
