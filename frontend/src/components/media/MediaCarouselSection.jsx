import MediaCard from '../home/MediaCard.jsx'
import Pagination from '../shared/Pagination.jsx'
import styles from './MediaCarouselSection.module.css'

function ChevronIcon({ direction }) {
  const isPrev = direction === 'prev'
  return (
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <path
        d={isPrev ? 'M15 5L8 12L15 19' : 'M9 5L16 12L9 19'}
        stroke="currentColor"
        strokeWidth="2.4"
        strokeLinecap="round"
        strokeLinejoin="round"
      />
    </svg>
  )
}

function SectionIcon({ kind }) {
  if (kind === 'gallery') {
    return (
      <span className={`${styles.icon} ${styles.galleryIcon}`} aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="12" r="8.5" stroke="currentColor" strokeWidth="2" />
          <circle cx="12" cy="12" r="3" fill="currentColor" />
        </svg>
      </span>
    )
  }

  return (
    <span className={`${styles.icon} ${styles.videoIcon}`} aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="10" fill="currentColor" />
        <path d="M10 8.5L16 12L10 15.5V8.5Z" fill="#fff" />
      </svg>
    </span>
  )
}

export default function MediaCarouselSection({
  title,
  kind = 'video',
  items,
  isLoading,
  error,
  emptyLabel,
  page,
  lastPage,
  onPageChange,
  t,
}) {
  const canGoPrev = page > 1
  const canGoNext = page < lastPage

  return (
    <section className={styles.section} aria-label={title}>
      <header className={styles.header}>
        <h2 className={styles.title}>
          <SectionIcon kind={kind} />
          <span>{title}</span>
        </h2>
        {!isLoading && items.length > 0 ? (
          <div className={styles.paginationWrap}>
            <Pagination
              currentPage={page}
              lastPage={lastPage}
              onPageChange={onPageChange}
              prevAriaLabel={t('media.pagination.prev_page')}
              nextAriaLabel={t('media.pagination.next_page')}
              showSinglePage
            />
          </div>
        ) : null}
      </header>

      {isLoading ? <p className={styles.state}>{t('common.loading')}</p> : null}
      {!isLoading && error ? <p className={styles.state}>{error}</p> : null}
      {!isLoading && !error && items.length === 0 ? <p className={styles.state}>{emptyLabel}</p> : null}

      {!isLoading && !error && items.length > 0 ? (
        <div className={styles.slider}>
          <button
            type="button"
            className={`${styles.arrow} ${styles.prev}`}
            aria-label={t('media.pagination.prev_slide')}
            onClick={() => onPageChange(page - 1)}
            disabled={!canGoPrev}
          >
            <ChevronIcon direction="prev" />
          </button>

          <div className={styles.grid}>
            {items.map((item, index) => (
              <MediaCard
                key={item.id}
                fluid
                item={item}
                title={item.title || t('media.ui.untitled')}
                subtitle={item.description || item.author?.display_name || item.author?.nickname || t('common.unknown_user')}
                tone={['charcoal', 'sepia', 'graphite'][index % 3]}
                type={item.type}
                href={`/media/${item.id}`}
                mediaUrl={item.peertube_url || item.file_url}
                coverUrl={item.peertube_thumbnail_url || item.file_url}
                views={item.views_count ?? 0}
                likes={item.likes_count ?? 0}
                comments={item.comments_count ?? 0}
                playAria={t('media.ui.play')}
                openAria={t('media.ui.open')}
                shareLabel={t('media.ui.share')}
                reportLabel={t('media.ui.report')}
              />
            ))}
          </div>

          <button
            type="button"
            className={`${styles.arrow} ${styles.next}`}
            aria-label={t('media.pagination.next_slide')}
            onClick={() => onPageChange(page + 1)}
            disabled={!canGoNext}
          >
            <ChevronIcon direction="next" />
          </button>
        </div>
      ) : null}
    </section>
  )
}
