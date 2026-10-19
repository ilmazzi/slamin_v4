import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Link, useLocation, useParams } from 'react-router-dom'
import { getGroup, listGroupEvents, listGroupPublications } from '../../app/api/groups.js'
import { createGroupReport } from '../../app/api/reports.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { expandEventOccurrencesForCalendar } from '../../app/normalizers/event.js'
import { scheduleScrollToComments } from '../../app/utils/scrollToComments.js'
import GroupFeed from '../../components/groups/GroupFeed.jsx'
import GroupMembershipActions from '../../components/groups/GroupMembershipActions.jsx'
import GroupStaffPanel from '../../components/groups/GroupStaffPanel.jsx'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PersonalEventsCalendar from '../../components/PersonalEventsCalendar.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import CommentsThread from '../../components/shared/CommentsThread.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import SocialActions from '../../components/shared/SocialActions.jsx'
import styles from './GroupWorkspace.module.css'

function Fact({ label, value }) {
  if (!value && value !== 0) return null
  return (
    <div className={styles.fact}>
      <dt>{label}</dt>
      <dd>{value}</dd>
    </div>
  )
}

export default function GroupShowPage() {
  const { slug } = useParams()
  const location = useLocation()
  const { t, locale } = useI18n()
  const { token } = useAuth()
  const commentsRef = useRef(null)
  const [group, setGroup] = useState(null)
  const [events, setEvents] = useState([])
  const [publications, setPublications] = useState({ poems: [], articles: [] })
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')

  const load = useCallback(async ({ signal } = {}) => {
    const nextGroup = await getGroup(slug, { token, signal })
    setGroup(nextGroup)
    const membership = nextGroup?.current_user_membership
    const isTeaser = nextGroup?.visibility === 'private' && membership?.status !== 'active'
    if (nextGroup && !isTeaser) {
      const [nextEvents, nextPublications] = await Promise.all([
        listGroupEvents(slug, { token, signal }),
        listGroupPublications(slug, { token, signal }),
      ])
      setEvents(nextEvents)
      setPublications(nextPublications)
    } else {
      setEvents([])
      setPublications({ poems: [], articles: [] })
    }
  }, [slug, token])

  useEffect(() => {
    if (!token || !slug) return undefined
    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    load({ signal: controller.signal })
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('group.ui.show_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) setIsLoading(false)
      })
    return () => controller.abort()
  }, [load, slug, t, token])

  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    contentType: 'group',
    knownItems: group ? [group] : [],
    shareCopiedMessage: t('group.ui.share_copied'),
    shareSentMessage: t('group.ui.share_sent'),
    shareFailedMessage: t('group.ui.share_failed'),
    reportSubmittedMessage: t('group.ui.report_submitted'),
    reportFailedMessage: t('report.ui.submit_error'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id ?? group?.id ?? null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createGroupReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  const membership = group?.current_user_membership
  const isActiveMember = membership?.status === 'active'
  const showTeaser = group?.visibility === 'private' && !isActiveMember

  useEffect(() => {
    if (showTeaser || (location.hash !== '#comments' && !location.state?.focusComments)) {
      return undefined
    }
    if (!group?.id || !commentsRef.current) {
      return undefined
    }

    return scheduleScrollToComments({ behavior: 'smooth' })
  }, [group?.id, location.hash, location.state, showTeaser])

  const canManageStaff = Boolean(
    group?.permissions?.invite
    || group?.permissions?.manage_content_links
    || group?.permissions?.view_affiliations
    || group?.permissions?.manage_chat_threads,
  )
  const calendarEvents = useMemo(
    () => (showTeaser ? [] : events).flatMap((event) => expandEventOccurrencesForCalendar(event, { relations: ['owned'] })),
    [events, showTeaser],
  )
  const links = [
    ['website', group?.website_url],
    ['facebook', group?.facebook_url],
    ['instagram', group?.instagram_url],
    ['youtube', group?.youtube_url],
  ].filter(([, url]) => url)

  return (
    <MainLayout themeTone="groups">
      <div className={styles.shell}>
      <div className={styles.page}>
        <Link to="/groups" className={styles.backLink}>{t('group.ui.back')}</Link>

        {isLoading || error || !group ? (
          <p className={styles.state} role={error ? 'alert' : undefined}>
            {isLoading ? t('group.ui.loading') : (error || t('group.ui.show_error'))}
          </p>
        ) : (
          <>
          <div className={styles.layout}>
            <div className={styles.stack}>
              <article className={styles.panel}>
                <header className={styles.panelHead}>
                  <h1 className={styles.panelTitle}>{group.name}</h1>
                  {group.visibility ? (
                    <span className={styles.panelMeta}>{t(`group.visibility.${group.visibility}`)}</span>
                  ) : null}
                </header>
                {group.image_url ? (
                  <img
                    src={resolvePublicUrl(group.image_url)}
                    alt=""
                    className={styles.coverBleed}
                  />
                ) : null}
                <div className={styles.panelBody}>
                  {group.category?.name || group.permissions?.update ? (
                    <div className={styles.actions}>
                      {group.category?.name ? (
                        <span className={styles.badge}>{group.category.name}</span>
                      ) : null}
                      {group.permissions?.update ? (
                        <Link to={`/groups/${group.slug}/edit`} className={styles.btn}>
                          {t('group.ui.edit')}
                        </Link>
                      ) : null}
                    </div>
                  ) : null}

                  {showTeaser ? (
                    <p className={styles.muted}>
                      {group.is_listed ? t('group.ui.private_teaser') : t('group.ui.unlisted_teaser')}
                    </p>
                  ) : group.description ? (
                    <p className={styles.text}>{group.description}</p>
                  ) : (
                    <p className={styles.muted}>{t('group.ui.no_description')}</p>
                  )}

                  <dl className={styles.facts}>
                    <Fact
                      label={t('group.ui.followers_label')}
                      value={t('group.ui.followers', { count: group.active_members_count ?? 0 })}
                    />
                    <Fact
                      label={t('group.ui.listed_label')}
                      value={group.is_listed ? t('group.ui.listed_yes') : t('group.ui.listed_no')}
                    />
                    {membership?.role ? (
                      <Fact label={t('group.ui.your_role')} value={t(`group.roles.${membership.role}`)} />
                    ) : null}
                  </dl>

                  <SocialActions
                    target={group}
                    contentType="group"
                    apiBasePath="/groups"
                    publicBasePath="/groups"
                    variant="list"
                    metrics={{
                      likes: group.likes_count ?? 0,
                      comments: group.comments_count ?? 0,
                    }}
                    metricsOrder={showTeaser ? ['likes'] : ['likes', 'comments']}
                    shareLabel={t('group.ui.share')}
                    reportLabel={t('group.ui.report')}
                    onComments={() => commentsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })}
                    className={styles.social}
                    metricsClassName={styles.socialMetrics}
                    metricClassName={styles.socialMetric}
                    buttonsClassName={styles.socialButtons}
                  />

                  {links.length > 0 && !showTeaser ? (
                    <>
                      <h2 className={styles.sectionTitle}>{t('group.ui.links')}</h2>
                      <div className={styles.links}>
                        {links.map(([key, url]) => (
                          <a key={key} href={url} className={styles.link} target="_blank" rel="noreferrer">
                            {t(`group.ui.${key}`)}
                          </a>
                        ))}
                      </div>
                    </>
                  ) : null}
                </div>
              </article>
            </div>

            <aside className={styles.stack}>
              <section className={styles.panel}>
                <header className={styles.panelHead}>
                  <h2 className={styles.panelTitle}>{t('group.ui.membership')}</h2>
                </header>
                <div className={styles.panelBody}>
                  <GroupMembershipActions group={group} token={token} t={t} onUpdated={load} />
                </div>
              </section>
            </aside>
          </div>
          {!showTeaser ? (
            <div ref={commentsRef} id="comments" className={styles.commentsBand}>
              <CommentsThread contentType="group" contentId={group.id} title={t('group.ui.comments')} />
            </div>
          ) : null}
          <GroupFeed
            group={group}
            token={token}
            t={t}
            locale={locale}
            locked={showTeaser}
            events={showTeaser ? [] : events}
            poems={showTeaser ? [] : publications.poems}
            articles={showTeaser ? [] : publications.articles}
          />
          {canManageStaff ? (
            <GroupStaffPanel group={group} token={token} t={t} onUpdated={load} />
          ) : null}
          </>
        )}
      </div>
      {!isLoading && group ? (
        <PersonalEventsCalendar
          tone="groups"
          showVisibilityToggle={false}
          events={calendarEvents}
          canCreate={false}
          titleAccent={t('group.ui.calendar_title_accent')}
          titleMiddle={t('group.ui.calendar_title_middle')}
          titleTail={t('group.ui.calendar_title_tail')}
        />
      ) : null}
      </div>
      <ContentReportModal
        key={reportModalItem?.id ?? 'group-report-closed'}
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.name ?? group?.name ?? t('group.ui.title')}
        title={t('group.ui.report_title')}
        description={t('group.ui.report_description')}
        cancelLabel={t('group.ui.report_cancel')}
        confirmLabel={t('group.ui.report_confirm')}
        isSubmitting={isSubmittingReport}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
