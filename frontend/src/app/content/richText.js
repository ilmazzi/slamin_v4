import DOMPurify from 'dompurify'

const RICH_TEXT_TAG_PATTERN = /<(?:p|br|strong|em|u|s|h2|h3|blockquote|ul|ol|li|hr|a)(?:\s|>|\/)/i
const ALIGNMENTS = new Set(['left', 'center', 'right', 'justify'])

const PURIFY_OPTIONS = {
  ALLOWED_TAGS: [
    'p',
    'br',
    'strong',
    'em',
    'u',
    's',
    'h2',
    'h3',
    'blockquote',
    'ul',
    'ol',
    'li',
    'hr',
    'a',
  ],
  ALLOWED_ATTR: ['href', 'title', 'rel', 'data-text-align'],
  FORBID_ATTR: ['class', 'style'],
}

function escapeHtml(value) {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function plainTextToHtml(value) {
  return value
    .split(/\n{2,}/)
    .map((paragraph) => `<p>${escapeHtml(paragraph).replaceAll('\n', '<br>')}</p>`)
    .join('')
}

function normalizeTextAlignment(value) {
  if (typeof DOMParser === 'undefined' || (!value.includes('style=') && !value.includes('data-text-align'))) {
    return value
  }

  const document = new DOMParser().parseFromString(`<body>${value}</body>`, 'text/html')

  document.body.querySelectorAll('[style], [data-text-align]').forEach((element) => {
    const alignment = element.style.textAlign || element.getAttribute('data-text-align') || ''
    element.removeAttribute('style')

    if (ALIGNMENTS.has(alignment.toLowerCase())) {
      element.setAttribute('data-text-align', alignment.toLowerCase())
    } else {
      element.removeAttribute('data-text-align')
    }
  })

  return document.body.innerHTML
}

export function toEditorHtml(value) {
  const content = String(value ?? '')

  if (!content) {
    return ''
  }

  return RICH_TEXT_TAG_PATTERN.test(content) ? content : plainTextToHtml(content)
}

export function sanitizeRichText(value) {
  return DOMPurify.sanitize(normalizeTextAlignment(toEditorHtml(value)), PURIFY_OPTIONS)
}

export function toEditorDocument(value) {
  return sanitizeRichText(value).replace(
    /\sdata-text-align="(left|center|right|justify)"/gi,
    ' style="text-align: $1"',
  )
}

export function richTextToPlainText(value) {
  if (typeof document === 'undefined') {
    return String(value ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
  }

  const container = document.createElement('div')
  container.innerHTML = sanitizeRichText(value)
    .replace(/<br\s*\/?>/gi, ' ')
    .replace(/<\/(?:p|h2|h3|blockquote|li)>/gi, ' ')

  return (container.textContent || '').replace(/\s+/g, ' ').trim()
}

export function isRichTextEmpty(value) {
  return richTextToPlainText(value) === ''
}
