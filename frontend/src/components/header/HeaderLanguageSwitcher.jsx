import styles from './Header.module.css'

const languageToRegionFallback = {
  ar: 'SA',
  de: 'DE',
  en: 'US',
  es: 'ES',
  fr: 'FR',
  it: 'IT',
  ja: 'JP',
  nl: 'NL',
  pl: 'PL',
  pt: 'PT',
  ru: 'RU',
  zh: 'CN',
}

const getFlagImageUrl = (regionCode) => {
  const code = String(regionCode || '').trim().toUpperCase()
  if (!/^[A-Z]{2}$/.test(code)) {
    return ''
  }

  return `https://flagcdn.com/w20/${code.toLowerCase()}.png`
}

const resolveRegionCode = (code, entry) => {
  const rawCode = String(code || '').trim()
  const explicitRegion =
    String(entry?.region || entry?.country || entry?.country_code || '')
      .trim()
      .toUpperCase()

  if (/^[A-Z]{2}$/.test(explicitRegion)) {
    return explicitRegion
  }

  const normalized = rawCode.replace('_', '-')
  const [, candidateRegion = ''] = normalized.split('-')
  const upperCandidateRegion = candidateRegion.toUpperCase()

  if (/^[A-Z]{2}$/.test(upperCandidateRegion)) {
    return upperCandidateRegion
  }

  const [language = ''] = normalized.split('-')
  return languageToRegionFallback[language.toLowerCase()] || ''
}

const normalizeLocaleOption = (entry) => {
  if (typeof entry === 'string') {
    const code = entry.toLowerCase()
    const region = resolveRegionCode(code)
    return {
      code,
      name: entry.toUpperCase(),
      region,
      flagImageUrl: getFlagImageUrl(region),
    }
  }

  const code = (entry?.code || '').toLowerCase()
  const region = resolveRegionCode(code, entry)

  return {
    code,
    name: entry?.name || code.toUpperCase(),
    region,
    flagImageUrl: getFlagImageUrl(region),
  }
}

export default function HeaderLanguageSwitcher({
  locale = 'it',
  availableLocales = [],
  onChange,
  ariaLabel = 'Select language',
}) {
  const options = (availableLocales.length ? availableLocales : [locale])
    .map(normalizeLocaleOption)
    .filter((option) => option.code)

  const currentLocale = (locale || 'it').toLowerCase()
  const selectedOption =
    options.find((option) => option.code === currentLocale) || options[0] || null

  return (
    <div className={styles.languageSwitcher}>
      <span className={styles.languageMeta}>
        <span className={styles.languageFlag} aria-hidden="true">
          {selectedOption?.flagImageUrl ? (
            <img
              src={selectedOption.flagImageUrl}
              alt=""
              className={styles.languageFlagImage}
            />
          ) : (
            '🌐'
          )}
        </span>
        <span className={styles.languageLabel}>
          {(selectedOption?.code || currentLocale).toUpperCase()}
        </span>
      </span>

      <svg
        viewBox="0 0 18 11"
        fill="none"
        className={styles.chevron}
        aria-hidden="true"
      >
        <path
          d="M1.5 1.5L9 9L16.5 1.5"
          stroke="currentColor"
          strokeWidth="3"
        />
      </svg>

      <select
        className={styles.languageSelect}
        value={currentLocale}
        onChange={(event) => {
          onChange?.(event.target.value)
          event.target.blur()
        }}
        aria-label={ariaLabel}
      >
        {options.map((option) => (
          <option key={option.code} value={option.code}>
            {option.name}
          </option>
        ))}
      </select>
    </div>
  )
}
