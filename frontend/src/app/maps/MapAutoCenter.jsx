import { useEffect } from 'react'
import { useMap } from 'react-leaflet'

export default function MapAutoCenter({ lat, lng, zoom = 14 }) {
  const map = useMap()

  useEffect(() => {
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return
    }
    map.flyTo([lat, lng], zoom, { animate: true, duration: 0.6 })
  }, [lat, lng, map, zoom])

  return null
}
