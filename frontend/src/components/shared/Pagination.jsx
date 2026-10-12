import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './Pagination.module.css'

function ChevronLeftIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 37 23" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M36 1.5L18.5 21L1 1.5" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" transform="rotate(90 18.5 11.25)" />
    </svg>
  )
}

function ChevronRightIcon({ className }) {
  return (
    <svg className={className} viewBox="0 0 37 23" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M1 1.5L18.5 21L36 1.5" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" transform="rotate(-90 18.5 11.25)" />
    </svg>
  )
}

function buildPaginationTokens(currentPage, lastPage, maxVisiblePages) {
  if (lastPage <= 1) {
    return [1]
  }

  if (lastPage <= maxVisiblePages) {
    return Array.from({ length: lastPage }, (_, index) => index + 1)
  }

  const rawStartPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2))
  const rawEndPage = Math.min(lastPage, rawStartPage + maxVisiblePages - 1)
  const startPage = Math.max(1, rawEndPage - maxVisiblePages + 1)
  const endPage = rawEndPage
  const visiblePages = Array.from(
    { length: Math.max(0, endPage - startPage + 1) },
    (_, index) => startPage + index,
  )

  const firstVisible = visiblePages[0]
  const lastVisible = visiblePages[visiblePages.length - 1]
  const tokens = []

  if (firstVisible > 1) {
    tokens.push(1)
    if (firstVisible > 2) {
      tokens.push('left-ellipsis')
    }
  }

  tokens.push(...visiblePages)

  if (lastVisible < lastPage) {
    if (lastVisible < lastPage - 1) {
      tokens.push('right-ellipsis')
    }
    tokens.push(lastPage)
  }

  return tokens
}

export default function Pagination({
  currentPage,
  lastPage,
  onPageChange,
  prevAriaLabel,
  nextAriaLabel,
  infoText = '',
  maxVisiblePages = 5,
  showSinglePage = false,
}) {
  const { t } = useI18n()
  const resolvedPrevAriaLabel = prevAriaLabel ?? t('articles.pagination.prev_page')
  const resolvedNextAriaLabel = nextAriaLabel ?? t('articles.pagination.next_page')
  if (lastPage <= 1 && !showSinglePage) {
    return null
  }

  const tokens = buildPaginationTokens(currentPage, lastPage, maxVisiblePages)

  return (
    <>
      {infoText ? <p className={styles.resultsText}>{infoText}</p> : null}
      <section className={styles.wrap}>
        <div className={styles.pagination}>
          <button
            type="button"
            className={styles.pageArrow}
            aria-label={resolvedPrevAriaLabel}
            onClick={() => onPageChange(Math.max(1, currentPage - 1))}
            disabled={currentPage <= 1}
          >
            <ChevronLeftIcon className={styles.pageArrowIcon} />
          </button>

          {tokens.map((token, index) => (
            typeof token === 'number' ? (
              <button
                key={token}
                type="button"
                className={`${styles.pageButton} ${token === currentPage ? styles.pageButtonActive : ''}`}
                onClick={() => onPageChange(token)}
              >
                {token}
              </button>
            ) : (
              <span key={`${token}-${index}`} className={styles.pageEllipsis} aria-hidden="true">
                ...
              </span>
            )
          ))}

          <button
            type="button"
            className={styles.pageArrow}
            aria-label={resolvedNextAriaLabel}
            onClick={() => onPageChange(Math.min(lastPage, currentPage + 1))}
            disabled={currentPage >= lastPage}
          >
            <ChevronRightIcon className={styles.pageArrowIcon} />
          </button>
        </div>
      </section>
    </>
  )
}
