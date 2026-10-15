const ISO_639_1_CODES = [
  'aa', 'ab', 'af', 'ak', 'am', 'an', 'ar', 'as', 'av', 'ay', 'az', 'ba', 'be', 'bg', 'bi', 'bm', 'bn', 'bo', 'br', 'bs',
  'ca', 'ce', 'ch', 'co', 'cr', 'cs', 'cu', 'cv', 'cy', 'da', 'de', 'dv', 'dz', 'ee', 'el', 'en', 'eo', 'es', 'et', 'eu',
  'fa', 'ff', 'fi', 'fj', 'fo', 'fr', 'fy', 'ga', 'gd', 'gl', 'gn', 'gu', 'gv', 'ha', 'he', 'hi', 'ho', 'hr', 'ht', 'hu',
  'hy', 'hz', 'ia', 'id', 'ie', 'ig', 'ii', 'ik', 'io', 'is', 'it', 'iu', 'ja', 'jv', 'ka', 'kg', 'ki', 'kj', 'kk', 'kl',
  'km', 'kn', 'ko', 'kr', 'ks', 'ku', 'kv', 'kw', 'ky', 'la', 'lb', 'lg', 'li', 'ln', 'lo', 'lt', 'lu', 'lv', 'mg', 'mh',
  'mi', 'mk', 'ml', 'mn', 'mr', 'ms', 'mt', 'my', 'na', 'nb', 'nd', 'ne', 'ng', 'nl', 'nn', 'no', 'nr', 'nv', 'ny', 'oc',
  'oj', 'om', 'or', 'os', 'pa', 'pi', 'pl', 'ps', 'pt', 'qu', 'rm', 'rn', 'ro', 'ru', 'rw', 'sa', 'sc', 'sd', 'se', 'sg',
  'si', 'sk', 'sl', 'sm', 'sn', 'so', 'sq', 'sr', 'ss', 'st', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'ti', 'tk', 'tl',
  'tn', 'to', 'tr', 'ts', 'tt', 'tw', 'ty', 'ug', 'uk', 'ur', 'uz', 've', 'vi', 'vo', 'wa', 'wo', 'xh', 'yi', 'yo', 'za',
  'zh', 'zu',
]

const displayNamesCache = new Map()

function displayNamesFor(uiLocale) {
  const key = uiLocale || 'it'
  if (!displayNamesCache.has(key)) {
    let instance = null
    try {
      instance = new Intl.DisplayNames([key], { type: 'language' })
    } catch {
      instance = null
    }
    displayNamesCache.set(key, instance)
  }
  return displayNamesCache.get(key)
}

export function languageName(code, uiLocale) {
  const safeCode = String(code ?? '').trim().toLowerCase()
  if (!safeCode) {
    return ''
  }

  let name = ''
  try {
    name = displayNamesFor(uiLocale)?.of(safeCode) ?? ''
  } catch {
    name = ''
  }

  if (!name || name.toLowerCase() === safeCode) {
    return safeCode.toUpperCase()
  }

  return name.charAt(0).toLocaleUpperCase(uiLocale) + name.slice(1)
}

export function contentLanguageOptions(uiLocale) {
  const seenLabels = new Set()

  return ISO_639_1_CODES
    .map((code) => ({ value: code, label: languageName(code, uiLocale) }))
    .filter((option) => {
      if (option.label === option.value.toUpperCase() || seenLabels.has(option.label)) {
        return false
      }
      seenLabels.add(option.label)
      return true
    })
    .sort((a, b) => a.label.localeCompare(b.label, uiLocale))
}
