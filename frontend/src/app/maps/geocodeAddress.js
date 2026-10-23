const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search'
const ACCEPT_LANGUAGE = 'it,es,en,fr,de,pt'
const RETRY_GAP_MS = 1100

const COUNTRY_ALIASES = {
  it: 'IT',
  italia: 'IT',
  italy: 'IT',
  italien: 'IT',
  italie: 'IT',
  es: 'ES',
  spain: 'ES',
  spagna: 'ES',
  espana: 'ES',
  spanien: 'ES',
  espagne: 'ES',
  espanha: 'ES',
  fr: 'FR',
  france: 'FR',
  francia: 'FR',
  frankreich: 'FR',
  franca: 'FR',
  de: 'DE',
  germany: 'DE',
  germania: 'DE',
  deutschland: 'DE',
  allemagne: 'DE',
  alemanha: 'DE',
  pt: 'PT',
  portugal: 'PT',
  portogallo: 'PT',
  gb: 'GB',
  uk: 'GB',
  'united kingdom': 'GB',
  england: 'GB',
  'regno unito': 'GB',
  us: 'US',
  usa: 'US',
  'united states': 'US',
  'stati uniti': 'US',
  ch: 'CH',
  switzerland: 'CH',
  svizzera: 'CH',
  schweiz: 'CH',
  suisse: 'CH',
  suica: 'CH',
  at: 'AT',
  austria: 'AT',
  osterreich: 'AT',
  autriche: 'AT',
  be: 'BE',
  belgium: 'BE',
  belgio: 'BE',
  belgien: 'BE',
  belgique: 'BE',
  belgica: 'BE',
  nl: 'NL',
  netherlands: 'NL',
  olanda: 'NL',
  holland: 'NL',
  niederlande: 'NL',
  'pays-bas': 'NL',
  ie: 'IE',
  ireland: 'IE',
  irlanda: 'IE',
  irland: 'IE',
  irlande: 'IE',
}

function stripDiacritics(value) {
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
}

export function resolveCountryCode(raw, fallback = null) {
  const value = String(raw ?? '').trim()
  if (!value) return fallback

  const upper = value.toUpperCase()
  if (/^[A-Z]{2}$/.test(upper)) return upper

  const key = stripDiacritics(value).toLowerCase()
  return COUNTRY_ALIASES[key] || fallback
}

function parseCoords(results) {
  const first = Array.isArray(results) ? results[0] : null
  const lat = first?.lat ? Number(first.lat) : NaN
  const lon = first?.lon ? Number(first.lon) : NaN
  if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
    return null
  }

  const countryCode = resolveCountryCode(first?.address?.country_code)
  return { lat, lon, countryCode }
}

function sleep(ms, signal) {
  if (ms <= 0) {
    return Promise.resolve()
  }

  return new Promise((resolve, reject) => {
    const timer = setTimeout(resolve, ms)
    if (!signal) {
      return
    }

    const onAbort = () => {
      clearTimeout(timer)
      reject(Object.assign(new Error('Aborted'), { name: 'AbortError' }))
    }

    if (signal.aborted) {
      onAbort()
      return
    }

    signal.addEventListener('abort', onAbort, { once: true })
  })
}

const memoryCache = new Map()

function readCachedResult(key) {
  if (memoryCache.has(key)) {
    return memoryCache.get(key)
  }

  try {
    const stored = sessionStorage.getItem(`geocode:${key}`)
    if (!stored) {
      return undefined
    }
    const parsed = JSON.parse(stored)
    memoryCache.set(key, parsed)
    return parsed
  } catch {
    return undefined
  }
}

function writeCachedResult(key, result) {
  memoryCache.set(key, result)
  try {
    sessionStorage.setItem(`geocode:${key}`, JSON.stringify(result))
  } catch {
    // Ignore quota / non-browser environments.
  }
}

async function nominatimSearch(params, signal) {
  const url = new URL(NOMINATIM_URL)
  url.searchParams.set('format', 'jsonv2')
  url.searchParams.set('limit', '1')
  url.searchParams.set('addressdetails', '1')

  Object.entries(params).forEach(([key, value]) => {
    if (value) {
      url.searchParams.set(key, value)
    }
  })

  const response = await fetch(url.toString(), {
    signal,
    headers: {
      Accept: 'application/json',
      'Accept-Language': ACCEPT_LANGUAGE,
      'User-Agent': 'Slamin/1.0 (https://slamin.it)',
    },
  })

  if (!response.ok) {
    throw new Error('Geocoding failed')
  }

  return parseCoords(await response.json())
}

export async function geocodeAddress({
  placeName = '',
  addressLine = '',
  postalCode = '',
  city = '',
  country = '',
  signal,
} = {}) {
  const street = String(addressLine ?? '').trim()
  const cityName = String(city ?? '').trim()
  const postal = String(postalCode ?? '').trim()
  const venue = String(placeName ?? '').trim()
  const countryCode = resolveCountryCode(country)
  const cacheKey = [venue, street, postal, cityName, countryCode || ''].join('|')
  const cached = readCachedResult(cacheKey)
  if (cached !== undefined) {
    return cached
  }

  const structured = {}
  if (street) structured.street = street
  if (cityName) structured.city = cityName
  if (postal) structured.postalcode = postal

  const attempts = []
  if (Object.keys(structured).length > 0) {
    if (countryCode) {
      attempts.push({ ...structured, countrycodes: countryCode.toLowerCase() })
    }
    attempts.push({ ...structured })
  }

  const freeform = [street, postal, cityName].filter(Boolean).join(', ')
  if (freeform) {
    attempts.push({ q: freeform })
  }

  if (venue && cityName) {
    attempts.push({ q: `${venue}, ${cityName}` })
  }

  for (let index = 0; index < attempts.length; index += 1) {
    if (index > 0) {
      await sleep(RETRY_GAP_MS, signal)
    }

    const coords = await nominatimSearch(attempts[index], signal)
    if (coords) {
      const result = {
        ...coords,
        countryCode: coords.countryCode || countryCode || null,
      }
      writeCachedResult(cacheKey, result)
      return result
    }
  }

  return null
}
