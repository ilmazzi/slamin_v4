export function formatGigDate(value, locale, options = { day: 'numeric', month: 'short', year: 'numeric' }) {
  if (!value) {
    return ''
  }
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return new Intl.DateTimeFormat(locale || 'it', options).format(date)
}

export function formatGigDeadline(value, locale) {
  return formatGigDate(value, locale, { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' })
}

export function formatGigDateTime(value, locale) {
  return formatGigDate(value, locale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export function formatGigMoney(amount, currency, locale) {
  if (amount === null || amount === undefined || amount === '') {
    return ''
  }
  const value = Number(amount)
  if (!Number.isFinite(value)) {
    return ''
  }
  try {
    return new Intl.NumberFormat(locale || 'it', {
      style: 'currency',
      currency: currency || 'EUR',
      maximumFractionDigits: value % 1 === 0 ? 0 : 2,
    }).format(value)
  } catch {
    return `${value} ${currency || ''}`.trim()
  }
}

export function gigReferenceDate(gig) {
  return gig?.parent?.starts_at || gig?.deadline || gig?.opened_at || gig?.created_at || null
}

export function gigTitle(gig, t) {
  return gig?.parent?.title || t('gigs.card.untitled')
}
