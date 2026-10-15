import { useEffect, useState } from 'react'
import { getRealtimeConfig } from '../api/realtime.js'

let cachedRequest = null

function loadRealtimeConfig(token) {
  if (cachedRequest?.token !== token) {
    const promise = getRealtimeConfig({ token }).catch((error) => {
      if (cachedRequest?.promise === promise) {
        cachedRequest = null
      }
      throw error
    })
    cachedRequest = { token, promise }
  }

  return cachedRequest.promise
}

export function useRealtimeConfig(token) {
  const [config, setConfig] = useState(undefined)

  useEffect(() => {
    if (!token) {
      return undefined
    }

    let active = true
    loadRealtimeConfig(token)
      .then((value) => {
        if (active) {
          setConfig(value)
        }
      })
      .catch(() => {
        if (active) {
          setConfig(null)
        }
      })

    return () => {
      active = false
    }
  }, [token])

  return token ? config : null
}
