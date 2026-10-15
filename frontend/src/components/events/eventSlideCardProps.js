/**
 * Maps an event normalized by normalizeEventForUi to SlideCard props.
 */
export function eventSlideCardProps(event, { t, locale }) {
  return {
    title: event.title || t('events.event_title'),
    description: event.subtitle || event.description || t('events.event_description'),
    city: event.city || '—',
    dateLabel: event.start_datetime
      ? new Date(event.start_datetime).toLocaleDateString(locale || 'it-IT')
      : '—',
    imageUrl: event.image_url,
    badge: event.category || '',
    organizerName: event.organizer_name || '',
    organizerAvatarUrl: event.organizer_avatar_url,
    href: event.slug ? `/events/${encodeURIComponent(event.slug)}` : '/events',
    openAriaLabel: t('events.ui.open'),
    item: event,
  }
}
