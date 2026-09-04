import { useEffect, useState } from 'react'

/**
 * Client-only matchMedia hook; initial render uses `false` until subscribed (avoid SSR mismatch).
 */
export function useMediaQuery(query) {
  const [matches, setMatches] = useState(false)

  useEffect(() => {
    if (typeof window === 'undefined') {
      return undefined
    }

    const mq = window.matchMedia(query)

    function apply() {
      setMatches(Boolean(mq.matches))
    }

    apply()
    mq.addEventListener('change', apply)
    return () => mq.removeEventListener('change', apply)
  }, [query])

  return matches
}
