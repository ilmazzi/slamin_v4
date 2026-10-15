const TONES = ['charcoal', 'sepia', 'graphite']

/**
 * Maps a media item from the API to MediaCard props.
 */
export function mediaCardProps(item, index, t) {
  return {
    item,
    title: item.title || t('media.ui.untitled'),
    subtitle: item.description || item.author?.display_name || item.author?.nickname || t('common.unknown_user'),
    tone: TONES[index % TONES.length],
    type: item.type,
    href: `/media/${item.id}`,
    mediaUrl: item.peertube_url || item.file_url,
    coverUrl: item.peertube_thumbnail_url || item.file_url,
    views: item.views_count ?? 0,
    likes: item.likes_count ?? 0,
    comments: item.comments_count ?? 0,
    playAria: t('media.ui.play'),
    openAria: t('media.ui.open'),
    shareLabel: t('media.ui.share'),
    reportLabel: t('media.ui.report'),
  }
}
