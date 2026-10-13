import { TileLayer } from 'react-leaflet'

/** CARTO Voyager raster tiles. No API key. */
export const OSM_TILE_URL = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
export const OSM_TILE_ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'

export default function OsmTileLayer() {
  return (
    <TileLayer
      url={OSM_TILE_URL}
      attribution={OSM_TILE_ATTRIBUTION}
      subdomains="abcd"
      maxZoom={20}
    />
  )
}

