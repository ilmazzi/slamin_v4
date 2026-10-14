import { apiGet } from './client.js'

export async function searchCatalog(query, { signal, token } = {}) {
  const params = new URLSearchParams({ q: String(query ?? '').trim() })
  const { data } = await apiGet(`/api/v1/search?${params.toString()}`, {
    signal,
    token,
    fallbackMessage: 'Unable to search',
  })

  return {
    query: data?.query ?? String(query ?? '').trim(),
    groups: Array.isArray(data?.groups) ? data.groups : [],
  }
}
