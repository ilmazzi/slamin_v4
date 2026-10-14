import { useEffect, useState } from 'react'
import { getRealtimeConfig } from '../api/realtime.js'

export function useRealtimeConfig(token) {
  const [config, setConfig] = useState(undefined)

  useEffect(() => {
    if (!token) {
      return undefined
    }

    const controller = new AbortController()
    getRealtimeConfig({ token, signal: controller.signal })
      .then(setConfig)
      .catch(() => {
        if (!controller.signal.aborted) {
          setConfig(null)
        }
      })

    return () => controller.abort()
  }, [token])

  return token ? config : null
}
