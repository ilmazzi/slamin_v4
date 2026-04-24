export function formatCurrency(value, locale = 'it-IT', currency = 'EUR', fallback = null) {
  if (value === null || value === undefined || value === '') {
    return fallback
  }

  const numericValue = Number(value)
  if (!Number.isFinite(numericValue)) {
    return fallback
  }

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
  }).format(numericValue)
}

export function formatLocaleNumber(value, locale = 'it-IT') {
  return new Intl.NumberFormat(locale).format(value)
}

