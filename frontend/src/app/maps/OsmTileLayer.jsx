import { TileLayer } from 'react-leaflet'

/** OpenStreetMap standard raster tiles. No API key. */
export const OSM_TILE_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
export const OSM_TILE_ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'

export default function OsmTileLayer() {
  return (
    <TileLayer
      url={OSM_TILE_URL}
      attribution={OSM_TILE_ATTRIBUTION}
      subdomains="abc"
      maxZoom={19}
    />
  )
}

