import { authApiGet } from './client.js'

export async function getPersonalStatistics({ token, signal } = {}) {
  const response = await authApiGet('/api/v1/me/statistics', {
    token,
    signal,
    fallbackMessage: 'Unable to load personal statistics',
  })

  return response.data
}
