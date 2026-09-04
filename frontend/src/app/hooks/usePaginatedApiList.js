import { useEffect, useState } from 'react'

/**
 * Small helper hook to avoid duplicating "useEffect + fetch + meta parsing" for paginated lists.
 * `fetcher` must return: { items, meta }.
 */
export function usePaginatedApiList({
  fetcher,
  params,
  enabled = true,
  normalizeItems,
  deps = [],
}) {
  const [items, setItems] = useState([])
  const [meta, setMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 1,
    total: 0,
  })
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!enabled) {
      setIsLoading(false)
      setError('')
      setItems([])
      setMeta((m) => ({ ...m, current_page: 1, last_page: 1, total: 0 }))
      return undefined
    }

    const controller = new AbortController()
    let cancelled = false

    async function run() {
      setIsLoading(true)
      setError('')

      try {
        const result = await fetcher(params, { signal: controller.signal })
        if (cancelled) {
          return
        }

        const rawItems = Array.isArray(result?.items) ? result.items : []
        const nextItems = typeof normalizeItems === 'function' ? normalizeItems(rawItems) : rawItems

        const nextMeta = result?.meta && typeof result.meta === 'object' ? result.meta : {}
        setItems(nextItems)
        setMeta({
          current_page: Math.max(1, Number(nextMeta.current_page ?? params?.page ?? 1)),
          last_page: Math.max(1, Number(nextMeta.last_page ?? 1)),
          per_page: Math.max(1, Number(nextMeta.per_page ?? params?.perPage ?? params?.per_page ?? 1)),
          total: Math.max(0, Number(nextMeta.total ?? rawItems.length)),
        })
      } catch (err) {
        if (err?.name === 'AbortError') {
          return
        }
        if (!cancelled) {
          setItems([])
          setMeta({ current_page: 1, last_page: 1, per_page: 1, total: 0 })
          setError(err?.message || 'Request failed')
        }
      } finally {
        if (!cancelled) {
          setIsLoading(false)
        }
      }
    }

    run()
    return () => {
      cancelled = true
      controller.abort()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enabled, fetcher, normalizeItems, ...deps])

  return { items, meta, isLoading, error }
}

