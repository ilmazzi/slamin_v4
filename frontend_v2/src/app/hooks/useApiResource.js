import { useEffect, useState } from 'react'

/**
 * Shared hook for single-resource screens/components.
 * `fetcher` must return the resource or `null`.
 */
export function useApiResource({
  fetcher,
  params,
  enabled = true,
  deps = [],
  initialData = null,
}) {
  const [data, setData] = useState(initialData)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!enabled) {
      setData(initialData)
      setIsLoading(false)
      setError('')
      return undefined
    }

    const controller = new AbortController()
    let cancelled = false

    async function run() {
      setIsLoading(true)
      setError('')

      try {
        const result = await fetcher(params, { signal: controller.signal })
        if (!cancelled) {
          setData(result ?? null)
        }
      } catch (err) {
        if (err?.name === 'AbortError') {
          return
        }
        if (!cancelled) {
          setData(null)
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
  }, [enabled, fetcher, initialData, ...deps])

  return { data, isLoading, error, setData, setError }
}

