import { listFeaturedPoems } from '../../app/api/poems.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import AppToast from '../shared/AppToast.jsx'
import ContentReportModal from '../shared/ContentReportModal.jsx'
import FeaturedPoemCard from './FeaturedPoemCard.jsx'
import SocialActions from '../shared/SocialActions.jsx'
import styles from './FeaturedPoemSection.module.css'


const FEATURED_POEMS_PER_PAGE = 3

export default  function FeaturedPoemsSection() {
  const { t, locale } = useI18n()

  const {
    items: poems,
    isLoading,
    error,
  } = usePaginatedApiList({
    fetcher: listFeaturedPoems,
    params: {
      page: 1,
      perPage: FEATURED_POEMS_PER_PAGE,
    },
    deps: [],
  })

  const featuredTitle = t('poems.featured.title') === 'poems.featured.title'
    ? 'Featured Poems'
    : t('poems.featured.title')
  const loadingLabel = t('common.loading') === 'common.loading' ? 'Loading...' : t('common.loading')
  const shareCopiedMessage = t('poems.actions.share_copied') === 'poems.actions.share_copied'
    ? 'Link poesia copiato'
    : t('poems.actions.share_copied')
  const shareSentMessage = t('poems.actions.share_sent') === 'poems.actions.share_sent'
    ? 'Condivisione avviata'
    : t('poems.actions.share_sent')
  const shareFailedMessage = t('poems.actions.share_failed') === 'poems.actions.share_failed'
    ? 'Impossibile condividere la poesia'
    : t('poems.actions.share_failed')
  const reportSubmittedMessage = t('poems.actions.report_submitted') === 'poems.actions.report_submitted'
    ? 'Segnalazione inviata'
    : t('poems.actions.report_submitted')
  const reportTitle = t('poems.actions.report_title') === 'poems.actions.report_title'
    ? 'Segnala questa poesia?'
    : t('poems.actions.report_title')
  const reportDescription = t('poems.actions.report_description') === 'poems.actions.report_description'
    ? 'Invieremo la segnalazione al team di moderazione per una verifica.'
    : t('poems.actions.report_description')
  const reportCancelLabel = t('poems.actions.report_cancel') === 'poems.actions.report_cancel'
    ? 'Annulla'
    : t('poems.actions.report_cancel')
  const reportConfirmLabel = t('poems.actions.report_confirm') === 'poems.actions.report_confirm'
    ? 'Segnala'
    : t('poems.actions.report_confirm')
  const noTitleLabel = t('poems.all.no_title') === 'poems.all.no_title'
    ? 'Poesia senza titolo'
    : t('poems.all.no_title')
  const noAuthorLabel = t('poems.all.no_author') === 'poems.all.no_author'
    ? 'Autore sconosciuto'
    : t('poems.all.no_author')
  const otherPoemsTitle = t('poems.featured.other_title') === 'poems.featured.other_title'
    ? 'Other poems'
    : t('poems.featured.other_title')
  const readMoreLabel = t('common.discover_more') === 'common.discover_more'
    ? 'Scopri di piu'
    : t('common.discover_more')
  const shareLabel = t('poems.actions.share') === 'poems.actions.share' ? 'Condividi poesia' : t('poems.actions.share')
  const reportLabel = t('poems.actions.report') === 'poems.actions.report' ? 'Segnala poesia' : t('poems.actions.report')

  const {
    reportModalItem,
    toastMessage,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    t,
    contentType: 'poem',
    knownItems: poems,
    shareCopiedMessage,
    shareSentMessage,
    shareFailedMessage,
    reportSubmittedMessage,
  })

  if (isLoading) {
    return (
      <section className={styles.section}>
        <h1 className={styles.title}>{featuredTitle}</h1>
        <p>{loadingLabel}</p>
      </section>
    )
  }

  if (error) {
    return (
      <section className={styles.section}>
        <h1 className={styles.title}>{featuredTitle}</h1>
        <p>{error}</p>
      </section>
    )
  }

  if (!poems || poems.length === 0) {
    return null
  }
  const mainPoem = poems[0] ?? null
  const otherPoems = poems.slice(1)

  const [titlePrefix = '', ...titleRest] = t('poems.featured.title').split(' ')
  const titleEmphasis = titleRest.join(' ')

  return (
    <>
      <section className={styles.section}>
          <h1 className={styles.title}>
            <span className={styles.titlePrefix}>{titlePrefix} </span>
            <span className={styles.titleEmphasis}>{titleEmphasis}</span>
          </h1>
          <div className={styles.grid}>
          <FeaturedPoemCard poem={mainPoem} />

          {otherPoems.length > 0 ? (
            <aside className={styles.otherPanel} aria-label={otherPoemsTitle}>
              <h2 className={styles.otherTitle}>{otherPoemsTitle}</h2>
              <div className={styles.otherList}>
                {otherPoems.map((poem) => {
                  const slugOrId = poem?.slug ?? poem?.id
                  const href = slugOrId ? `/poems/${slugOrId}` : '/poems'
                  const imageUrl = poem?.image_url ? resolvePublicUrl(poem.image_url) : ''
                  const authorName = poem?.author?.nickname ?? poem?.author?.display_name ?? noAuthorLabel
                  const publishedDate = formatShortPublishedDate(poem?.published_at, locale, '-')
                  const metrics = {
                    views: poem?.views_count ?? 0,
                    likes: poem?.likes_count ?? 0,
                    comments: poem?.comments_count ?? 0,
                  }

                  return (
                    <article key={poem?.id ?? poem?.slug ?? href} className={styles.otherItem}>
                      <a href={href} className={styles.otherCoverLink} aria-label={poem?.title ?? noTitleLabel}>
                        {imageUrl ? (
                          <img src={imageUrl} alt="" className={styles.otherCover} />
                        ) : (
                          <span className={styles.otherCoverFallback} aria-hidden="true" />
                        )}
                      </a>

                      <div className={styles.otherContent}>
                        <p className={styles.otherMeta}>
                          <span>{authorName}</span>
                          <span>{publishedDate}</span>
                        </p>
                        <a href={href} className={styles.otherItemTitle}>
                          {poem?.title ?? noTitleLabel}
                        </a>
                        <p className={styles.otherExcerpt}>{poem?.description ?? poem?.content ?? ''}</p>
                        <div className={styles.otherBottom}>
                          <a href={href} className={styles.otherLink}>
                            {readMoreLabel}
                          </a>
                          <SocialActions
                            target={poem}
                            contentType="poem"
                            metrics={metrics}
                            shareLabel={shareLabel}
                            reportLabel={reportLabel}
                            publicBasePath="/poems"
                            apiBasePath="/poems"
                            variant="list"
                            className={styles.otherSocialActions}
                            metricsClassName={styles.otherMetrics}
                            metricClassName={styles.otherMetric}
                            metricValueClassName={styles.otherMetricValue}
                            viewIconClassName={styles.otherMetricIcon}
                            likeIconClassName={styles.otherMetricIcon}
                            commentIconClassName={styles.otherMetricIcon}
                            buttonsClassName={styles.otherActionButtons}
                            iconClassName={styles.otherActionIcon}
                          />
                        </div>
                      </div>
                    </article>
                  )
                })}
              </div>
            </aside>
          ) : null}
  </div>

      </section>

      <ContentReportModal
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? noTitleLabel}
        title={reportTitle}
        description={reportDescription}
        cancelLabel={reportCancelLabel}
        confirmLabel={reportConfirmLabel}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />

      <AppToast
        message={toastMessage}
        isVisible={Boolean(toastMessage)}
        onClose={() => setToastMessage('')}
      />
    </>
)
}
