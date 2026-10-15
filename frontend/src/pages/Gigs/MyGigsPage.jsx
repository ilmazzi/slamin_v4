import { useCallback, useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import GigCard from '../../components/gigs/GigCard.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import styles from './GigWorkspace.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { listManagedGigs, listMyGigApplications } from '../../app/api/gigs.js'

const MANAGED_PER_PAGE = 10
const MANAGED_STATUSES = ['draft', 'open', 'filled', 'closed', 'cancelled']

function MyApplications({ token, t, locale }) {
  const [applications, setApplications] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    const controller = new AbortController()
    listMyGigApplications({ token, signal: controller.signal })
      .then((items) => setApplications(items.filter((item) => item.gig)))
      .catch((err) => {
        if (err?.name !== 'AbortError') {
          setError(err.message)
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      })
    return () => controller.abort()
  }, [token])

  if (isLoading) {
    return <p className={styles.state}>{t('gigs.common.loading')}</p>
  }
  if (error) {
    return <p className={styles.error} role="alert">{error}</p>
  }
  if (applications.length === 0) {
    return <p className={styles.state}>{t('gigs.mine.applications_empty')}</p>
  }

  return (
    <div className={styles.cardGrid}>
      {applications.map((application) => {
        const negotiation = application.negotiation
        const job = negotiation?.translation_job
        return (
          <GigCard
            key={application.id}
            gig={{ ...application.gig, my_application: { id: application.id, status: application.status } }}
            locale={locale}
            t={t}
          >
            {negotiation ? (
              <Link className={styles.btn} to={`/gig-negotiations/${negotiation.id}`}>
                {t('gigs.mine.negotiation', { status: t(`gigs.negotiation_statuses.${negotiation.status}`) })}
              </Link>
            ) : null}
            {job ? (
              <Link className={[styles.btn, styles.btnAccent].join(' ')} to={`/translation-jobs/${job.id}`}>
                {t('gigs.mine.job', { status: t(`gigs.job_statuses.${job.status}`) })}
              </Link>
            ) : null}
          </GigCard>
        )
      })}
    </div>
  )
}

function ManagedGigs({ token, t, locale, page, status, onPageChange, onStatusChange }) {
  const fetchGigs = useCallback((params, options) => listManagedGigs(params, { ...options, token }), [token])
  const { items: gigs, meta, isLoading, error } = usePaginatedApiList({
    fetcher: fetchGigs,
    params: { page, perPage: MANAGED_PER_PAGE, status },
    deps: [page, status],
  })
  const pageStart = meta.total === 0 ? 0 : ((meta.current_page - 1) * meta.per_page) + 1
  const pageEnd = meta.total === 0 ? 0 : pageStart + gigs.length - 1

  return (
    <>
      <div className={styles.actions}>
        <select
          className={[styles.select, styles.inlineSelect].join(' ')}
          value={status}
          onChange={(event) => onStatusChange(event.target.value)}
          aria-label={t('gigs.detail.status')}
        >
          <option value="">{t('gigs.mine.status_filter')}</option>
          {MANAGED_STATUSES.map((value) => (
            <option key={value} value={value}>{t(`gigs.statuses.${value}`)}</option>
          ))}
        </select>
      </div>

      {error ? <p className={styles.error} role="alert">{error}</p> : null}
      {!error && isLoading && gigs.length === 0 ? <p className={styles.state}>{t('gigs.common.loading')}</p> : null}
      {!error && !isLoading && gigs.length === 0 ? <p className={styles.state}>{t('gigs.mine.managed_empty')}</p> : null}

      {gigs.length > 0 ? (
        <div className={styles.cardGrid}>
          {gigs.map((gig) => (
            <GigCard key={gig.id} gig={gig} locale={locale} t={t}>
              <span className={[styles.badge, styles.badgeAccent].join(' ')}>{t(`gigs.statuses.${gig.status}`)}</span>
              <span>
                {t('gigs.manage.applications_count', {
                  count: gig.applications_count ?? 0,
                  pending: gig.pending_applications_count ?? 0,
                })}
              </span>
              <Link className={styles.btn} to={`/gigs/${gig.id}/edit`}>{t('gigs.common.edit')}</Link>
            </GigCard>
          ))}
        </div>
      ) : null}

      {meta.total > 0 ? (
        <Pagination
          currentPage={Math.min(page, meta.last_page)}
          lastPage={meta.last_page}
          onPageChange={onPageChange}
          prevAriaLabel={t('gigs.pagination.prev_page')}
          nextAriaLabel={t('gigs.pagination.next_page')}
          infoText={t('gigs.pagination.results', { from: pageStart, to: pageEnd, total: meta.total })}
          showSinglePage
        />
      ) : null}
    </>
  )
}

export default function MyGigsPage() {
  const { t, locale } = useI18n()
  const { token, user } = useAuth()
  const canManage = user?.account_type === 'poet_organizer'
  const [searchParams, setSearchParams] = useSearchParams()
  const tab = canManage && searchParams.get('tab') === 'managed' ? 'managed' : 'applications'
  const page = Math.max(1, Number.parseInt(searchParams.get('page') ?? '1', 10) || 1)
  const rawStatus = searchParams.get('status') ?? ''
  const status = MANAGED_STATUSES.includes(rawStatus) ? rawStatus : ''

  const updateParams = useCallback((changes) => {
    setSearchParams((prev) => {
      const next = new URLSearchParams(prev)
      Object.entries(changes).forEach(([key, value]) => {
        if (value) {
          next.set(key, String(value))
        } else {
          next.delete(key)
        }
      })
      return next
    }, { replace: true })
  }, [setSearchParams])

  return (
    <MainLayout themeTone="gigs">
      <div className={styles.page}>
        <Link to="/gigs" className={styles.backLink}>{t('gigs.common.back_to_list')}</Link>
        <h1 className={styles.pageTitle}>
          {t('gigs.mine.lead_prefix')} <em>{t('gigs.mine.lead_emphasis')}</em>
        </h1>

        {canManage ? (
          <div className={styles.tabs} role="tablist">
            {['applications', 'managed'].map((value) => (
              <button
                key={value}
                type="button"
                role="tab"
                aria-selected={tab === value}
                className={[styles.tab, tab === value ? styles.tabActive : ''].join(' ')}
                onClick={() => updateParams({ tab: value === 'managed' ? 'managed' : '', page: '', status: '' })}
              >
                {t(`gigs.mine.tab_${value}`)}
              </button>
            ))}
          </div>
        ) : null}

        {tab === 'managed' ? (
          <ManagedGigs
            token={token}
            t={t}
            locale={locale}
            page={page}
            status={status}
            onPageChange={(next) => updateParams({ page: next > 1 ? next : '' })}
            onStatusChange={(next) => updateParams({ status: next, page: '' })}
          />
        ) : (
          <MyApplications token={token} t={t} locale={locale} />
        )}
      </div>
    </MainLayout>
  )
}
