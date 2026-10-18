export const SHARE_CARD_VERSION = 10

const SHARE_CARD_TEMPLATES = [
  'poster',
  'date_cut',
  'diagonal',
  'framed',
  'typographic',
  'brand',
  'promo',
  'verse',
  'photo_clean',
  'photo_logo',
]

export function hasShareImage(target, contentType) {
  if (contentType === 'media') {
    return String(target?.type ?? '').toLowerCase() === 'photo'
      && Boolean(target?.file_path || target?.file_url || target?.image_url)
  }

  if (contentType === 'gig') {
    return Boolean(target?.parent?.image_url || target?.cover_image_path)
  }

  return Boolean(
    target?.cover_image_url
    || target?.cover_image_path
    || target?.image_url
    || target?.image_path,
  )
}

export function templatesFor() {
  return SHARE_CARD_TEMPLATES
}

export function defaultTemplateFor(contentType, target) {
  const available = templatesFor(contentType, target)
  const preferred = contentType === 'event' || contentType === 'article'
    ? 'poster'
    : contentType === 'gig'
      ? 'promo'
      : contentType === 'media'
        ? 'photo_logo'
        : contentType === 'poem'
          ? 'verse'
          : 'brand'

  return available.includes(preferred) ? preferred : (available[0] ?? 'brand')
}
