import { useMemo } from 'react'
import { Link } from 'react-router-dom'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from '../../pages/Dashboard/DashboardPage.module.css'
import {
  ArticleIcon,
  ActivityChevronLeftIcon,
  ActivityChevronRightIcon,
  ActivityCommentIcon,
  ActivityDeleteIcon,
  ActivityLikeIcon,
  ActivityPoemIcon,
  ActivityViewIcon,
  CalendarIcon,
  CommunityFriendsIcon,
  CommunityGroupsIcon,
  CommunityInvitesIcon,
  PenIcon,
  VideoIcon,
} from '../icons/ActionIcons.jsx'

const MAX_VISIBLE_PAGES = 5

const ICON_BY_TYPE = {
  poem: ActivityPoemIcon,
  event: CalendarIcon,
  article: ArticleIcon,
  video: VideoIcon,
  user: CommunityFriendsIcon,
  group: CommunityGroupsIcon,
  photo: ActivityViewIcon,
  gig: CommunityGroupsIcon,
}

const ICON_BY_ACTION = {
  viewed: ActivityViewIcon,
  liked: ActivityLikeIcon,
  commented: ActivityCommentIcon,
  deleted: ActivityDeleteIcon,
  updated: PenIcon,
  followed: CommunityFriendsIcon,
  unfollowed: CommunityFriendsIcon,
  invited: CommunityInvitesIcon,
  accepted: CalendarIcon,
}

function buildPaginationTokens(currentPage, lastPage) {
  if (lastPage <= MAX_VISIBLE_PAGES) {
    return Array.from({ length: Math.max(1, lastPage) }, (_, index) => index + 1)
  }

  const rawStart = Math.max(1, currentPage - Math.floor(MAX_VISIBLE_PAGES / 2))
  const end = Math.min(lastPage, rawStart + MAX_VISIBLE_PAGES - 1)
  const start = Math.max(1, end - MAX_VISIBLE_PAGES + 1)
  const tokens = []

  if (start > 1) {
    tokens.push(1)
    if (start > 2) {
      tokens.push('left-ellipsis')
    }
  }

  for (let page = start; page <= end; page += 1) {
    tokens.push(page)
  }

  if (end < lastPage) {
    if (end < lastPage - 1) {
      tokens.push('right-ellipsis')
    }
    tokens.push(lastPage)
  }

  return tokens
}

export default function RecentActivities({
  data,
  page,
  onPageChange,
  isLoading = false,
  emptyText,
  tabs = null,
  activeTab = null,
  onTabChange = null,
}) {
  const { t } = useI18n()
  const items = data?.items ?? []
  const total = Number(data?.total ?? items.length)
  const perPage = Number(data?.per_page ?? 3)
  const currentPage = Number(data?.current_page ?? page)
  const lastPage = Number(data?.last_page ?? 1)
  const pageStart = total === 0 ? 0 : ((currentPage - 1) * perPage) + 1
  const pageEnd = total === 0 ? 0 : pageStart + Math.max(0, items.length - 1)
  const paginationTokens = useMemo(() => buildPaginationTokens(currentPage, lastPage), [currentPage, lastPage])
  const resultsText = t('home.dashboard_page.activity_results')
    .replace(':from', String(pageStart))
    .replace(':to', String(pageEnd))
    .replace(':total', String(total))

  return (
    <>
      <section className={styles.recentTitleWrap}>
        <h2 className={styles.recentTitle}>
          <span>{t('home.dashboard_page.recent')}</span>{' '}
          <span className={styles.recentTitleAccent}>{t('home.dashboard_page.activities')}</span>
        </h2>
        {tabs?.length ? (
          <div className={styles.recentTabs} role="tablist">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                type="button"
                role="tab"
                aria-selected={tab.id === activeTab}
                className={`${styles.recentTab} ${tab.id === activeTab ? styles.recentTabActive : ''}`}
                onClick={() => onTabChange?.(tab.id)}
              >
                {tab.label}
              </button>
            ))}
          </div>
        ) : null}
      </section>
      <section className={styles.recentList}>
        {items.map((item) => {
          const Icon = ICON_BY_ACTION[item.action] || ICON_BY_TYPE[item.type] || CalendarIcon

          return (
            <article key={item.id} className={styles.recentItem}>
              <div className={styles.recentMainInfo}>
                <span className={styles.recentIconWrap} aria-hidden="true">
                  <Icon className={styles.recentIcon} />
                </span>
                <p className={styles.recentLabelAndTitle}>
                  {item.actor ? (
                    <Link to={item.actor.url} className={[styles.recentLabel, styles.recentItemLink].join(' ')}>
                      {item.actor.name}
                    </Link>
                  ) : null}
                  <strong className={styles.recentLabel}>{item.label}</strong>
                  {item.title && item.url ? (
                    <Link to={item.url} className={[styles.recentItemTitle, styles.recentItemLink].join(' ')}>
                      {item.title}
                    </Link>
                  ) : item.title ? (
                    <span className={styles.recentItemTitle}>{item.title}</span>
                  ) : null}
                </p>
              </div>
              <time className={styles.recentTime}>{item.time_ago}</time>
            </article>
          )
        })}
        {!isLoading && items.length === 0 ? (
          <p className={styles.recentEmpty}>{emptyText || t('common.no_results')}</p>
        ) : null}
      </section>
      {total > 0 ? (
        <>
          <p className={styles.recentResultsText}>{resultsText}</p>
          <section className={styles.recentPaginationWrap}>
            <div className={styles.recentPagination}>
              <button
                type="button"
                className={styles.recentPageArrow}
                aria-label={t('events.prev_month')}
                onClick={() => onPageChange(Math.max(1, currentPage - 1))}
                disabled={currentPage <= 1}
              >
                <ActivityChevronLeftIcon className={styles.recentPageArrowIcon} />
              </button>
              {paginationTokens.map((token, index) => (
                typeof token === 'number' ? (
                  <button
                    key={token}
                    type="button"
                    className={`${styles.recentPageButton} ${token === currentPage ? styles.recentPageButtonActive : ''}`}
                    onClick={() => onPageChange(token)}
                  >
                    {token}
                  </button>
                ) : (
                  <span key={`${token}-${index}`} className={styles.recentPageEllipsis} aria-hidden="true">...</span>
                )
              ))}
              <button
                type="button"
                className={styles.recentPageArrow}
                aria-label={t('events.next_month')}
                onClick={() => onPageChange(Math.min(lastPage, currentPage + 1))}
                disabled={currentPage >= lastPage}
              >
                <ActivityChevronRightIcon className={styles.recentPageArrowIcon} />
              </button>
            </div>
          </section>
        </>
      ) : null}
    </>
  )
}
