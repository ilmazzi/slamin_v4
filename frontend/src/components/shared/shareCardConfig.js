const TEMPLATES_BY_TYPE = {
  event: ['poster', 'typographic', 'brand'],
  article: ['poster', 'typographic', 'brand'],
  gig: ['promo', 'typographic', 'brand'],
  poem: ['verse', 'typographic', 'brand'],
  media: ['photo_clean', 'photo_logo', 'brand'],
}

const IMAGE_TEMPLATES = new Set(['poster', 'promo', 'photo_clean', 'photo_logo'])

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

export function templatesFor(contentType, target) {
  return (TEMPLATES_BY_TYPE[contentType] ?? ['brand']).filter((template) => (
    !IMAGE_TEMPLATES.has(template) || hasShareImage(target, contentType)
  ))
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
