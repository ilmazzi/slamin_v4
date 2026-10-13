import { useEffect, useState } from 'react'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import styles from './MediaPage.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useMediaQuery } from '../../app/hooks/useMediaQuery.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import { useMediaPageSearchParams } from '../../app/hooks/useMediaPageSearchParams.js'
import { useContentFeedbackUi } from '../../app/hooks/useContentFeedbackUi.js'
import { listMedia } from '../../app/api/media.js'
import { createMediaReport } from '../../app/api/reports.js'
import MediaFilterBar from '../../components/media/MediaFilterBar.jsx'
import MediaCarouselSection from '../../components/media/MediaCarouselSection.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import ContentReportModal from '../../components/shared/ContentReportModal.jsx'
import AppToast from '../../components/shared/AppToast.jsx'

const CARDS_PER_PAGE = 3
const SEARCH_DEBOUNCE_MS = 300

export default function MediaPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const isDesktop = useMediaQuery('(min-width: 1025px)')
  const cardsPerPage = isDesktop ? CARDS_PER_PAGE : 1
  const {
    search,
    category,
    sort,
    videoPage,
    galleryPage,
    setSearch,
    setCategory,
    setSort,
    setVideoPage,
    setGalleryPage,
  } = useMediaPageSearchParams()

  const [searchInput, setSearchInput] = useState(search)
  const showVideos = category !== 'photo'
  const showGallery = category !== 'video'

  useEffect(() => {
    setSearchInput(search)
  }, [search])

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (searchInput !== search) {
        setSearch(searchInput)
      }
    }, SEARCH_DEBOUNCE_MS)

    return () => window.clearTimeout(timer)
  }, [search, searchInput, setSearch])

  const videos = usePaginatedApiList({
    fetcher: listMedia,
    params: {
      page: videoPage,
      perPage: cardsPerPage,
      q: search,
      type: 'video',
      sort,
      token,
    },
    enabled: showVideos,
    deps: [videoPage, search, sort, token, showVideos, cardsPerPage],
  })

  const photos = usePaginatedApiList({
    fetcher: listMedia,
    params: {
      page: galleryPage,
      perPage: cardsPerPage,
      q: search,
      type: 'photo',
      sort,
      token,
    },
    enabled: showGallery,
    deps: [galleryPage, search, sort, token, showGallery, cardsPerPage],
  })

  const knownItems = [...videos.items, ...photos.items]

  useEffect(() => {
    const last = Math.max(1, Number(videos.meta.last_page || 1))
    if (showVideos && videoPage > last) {
      setVideoPage(last)
    }
  }, [showVideos, videoPage, videos.meta.last_page, setVideoPage])

  useEffect(() => {
    const last = Math.max(1, Number(photos.meta.last_page || 1))
    if (showGallery && galleryPage > last) {
      setGalleryPage(last)
    }
  }, [showGallery, galleryPage, photos.meta.last_page, setGalleryPage])

  const {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  } = useContentFeedbackUi({
    contentType: 'media',
    knownItems,
    shareCopiedMessage: t('media.ui.share_copied'),
    shareSentMessage: t('media.ui.share_sent'),
    shareFailedMessage: t('media.ui.share_failed'),
    reportSubmittedMessage: t('media.ui.report_submitted'),
    reportFailedMessage: t('common.no_results'),
    submitReport: async (item, { reason, details }) => {
      const target = item?.id ?? null
      if (!target) {
        throw new Error(t('common.no_results'))
      }
      await createMediaReport(target, { reason: reason || 'spam', details: details || null }, { token })
    },
  })

  return (
    <MainLayout themeTone="media" syncHeaderWithTheme={false}>
      <div className={pageStyles.mediaPage}>
        <PageHero
          title={t('media.title')}
          leadPrefix={t('media.lead_prefix')}
          leadEmphasis={t('media.lead_emphasis')}
          ctaLabel={t('media.create_media')}
          ctaHref="/media/upload"
          ctaVariant="red"
        />

        <section className={styles.filtersSection}>
          <MediaFilterBar
            searchValue={searchInput}
            onSearchChange={setSearchInput}
            sortValue={sort}
            onSortChange={setSort}
            categoryValue={category}
            onCategoryChange={setCategory}
            t={t}
          />
        </section>

        {showVideos ? (
          <MediaCarouselSection
            title={t('media.sections.video')}
            kind="video"
            items={videos.items}
            isLoading={videos.isLoading}
            error={videos.error}
            emptyLabel={t('media.sections.empty_video')}
            page={Math.min(videoPage, videos.meta.last_page || 1)}
            lastPage={videos.meta.last_page || 1}
            onPageChange={setVideoPage}
            t={t}
          />
        ) : null}

        {showGallery ? (
          <MediaCarouselSection
            title={t('media.sections.gallery')}
            kind="gallery"
            items={photos.items}
            isLoading={photos.isLoading}
            error={photos.error}
            emptyLabel={t('media.sections.empty_gallery')}
            page={Math.min(galleryPage, photos.meta.last_page || 1)}
            lastPage={photos.meta.last_page || 1}
            onPageChange={setGalleryPage}
            t={t}
          />
        ) : null}
      </div>

      <HomeDashboardPromoSection compactTop />

      <ContentReportModal
        key={reportModalItem?.id ?? 'media-report-closed'}
        isOpen={Boolean(reportModalItem)}
        item={reportModalItem}
        itemTitle={reportModalItem?.title ?? t('media.ui.untitled')}
        title={t('media.ui.report_title')}
        description={t('media.ui.report_description')}
        cancelLabel={t('media.ui.report_cancel')}
        confirmLabel={t('media.ui.report_confirm')}
        isSubmitting={isSubmittingReport}
        onClose={() => setReportModalItem(null)}
        onConfirm={handleReportConfirm}
      />
      <AppToast message={toastMessage} isVisible={Boolean(toastMessage)} onClose={() => setToastMessage('')} />
    </MainLayout>
  )
}
