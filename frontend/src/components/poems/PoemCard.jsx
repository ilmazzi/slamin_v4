import { formatShortPublishedDate } from '../../app/formatters/date.js'
import { resolvePublicUrl } from '../../app/api/url.js'

import { useI18n } from '../../app/hooks/useI18n.js'
import SocialActions from '../shared/SocialActions.jsx'
import styles from './PoemCard.module.css'
import { usePoemDetail } from '../../app/hooks/usePoemDetail.js'
import { useMediaQuery } from '../../app/hooks/useMediaQuery.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import {
  useContentTranslationView,
  withoutTranslation,
  withSavedTranslation,
} from '../../app/hooks/useContentTranslationView.js'
import ContentLanguageSwitcher from '../shared/ContentLanguageSwitcher.jsx'
import ContentTranslationsManager from '../shared/ContentTranslationsManager.jsx'
import RichTextContent from '../shared/RichTextContent.jsx'
import { useEffect } from 'react'

export default function PoemCard({ slug, onComments, onLoaded }) {
  const { t, locale } = useI18n()
  const { user } = useAuth()
  const isMobileStrip = useMediaQuery('(max-width: 768px)')
  const { data: poem, isLoading, error, setData } = usePoemDetail(slug)
  const translationView = useContentTranslationView('poem', poem)

  useEffect(() => {
    if (poem) {
      onLoaded?.(poem)
    }
  }, [onLoaded, poem])

  if (!slug) {
    return <div className={styles.poem}>{t('poems.missing_slug')}</div>
  }

  if (isLoading) {
    return <div className={styles.poem}>{t('common.loading')}</div>
  }

  if (error) {
    return <div className={styles.poem}>{error}</div>
  }

  if (!poem) {
    return <div className={styles.poem}>{t('poems.not_found')}</div>
  }

  const authorLabel = poem?.author?.display_name || poem?.author?.nickname || 'Slamin'
  const authorAvatar = resolvePublicUrl(poem?.author?.avatar_url)
  const publishedDate = formatShortPublishedDate(poem?.published_at, locale, '')
  const noCategoryLabel = t('poems.all.no_category')
  const categoryLabel = poem?.category?.name || noCategoryLabel
  const rawCover = poem?.cover_image_url || poem?.image_url || ''
  const coverUrl = rawCover ? resolvePublicUrl(rawCover) : ''
  const viewsCount = Number.isFinite(Number(poem?.views_count)) ? Number(poem.views_count) : 0
  const likesCount = Number.isFinite(Number(poem?.likes_count)) ? Number(poem.likes_count) : 0
  const commentsCount = Number.isFinite(Number(poem?.comments_count)) ? Number(poem.comments_count) : 0
  const translation = translationView.translation
  const displayTitle = translation?.title ?? poem.title
  const displayContent = translation?.content ?? poem.content
  const contentLang = translation?.locale ?? poem.language ?? undefined
  const isOwner = Boolean(user?.id) && Number(user.id) === Number(poem?.author?.id)

  return (
    <section className={styles.poemCard}>
      <article className={styles.card}>
        <div className={styles.top}>
          <ContentLanguageSwitcher item={poem} view={translationView} />
          <div className={styles.titleRow}>
            <h1 className={styles.title} lang={contentLang}>&ldquo;{displayTitle}&rdquo;</h1>
            <div className={styles.categoryColumn}>
              <span className={styles.categoryPill}>{categoryLabel}</span>
            </div>
          </div>
        </div>

        {coverUrl ? (
          <div className={styles.coverSection}>
            <img src={coverUrl} alt="" className={styles.cover} />
          </div>
        ) : null}

        <div className={styles.corpo} lang={contentLang}>
          {poem.excerpt ? <p className={styles.lead}>{poem.excerpt}</p> : null}
          {poem.excerpt && displayContent ? <div className={styles.rule} aria-hidden="true" /> : null}
          <RichTextContent content={displayContent} className={styles.bodyText} lang={contentLang} />
        </div>

        {/* Figma: autore sotto il testo, su sfondo bianco (non nella barra nera) */}
        <div className={styles.authorRow}>
          {authorAvatar ? (
            <img src={authorAvatar} alt="" className={styles.authorAvatar} />
          ) : (
            <span className={styles.authorAvatar} aria-hidden="true" />
          )}
          <span className={styles.authorNameLight}>{authorLabel}</span>
        </div>

        {/* Desktop: data a sinistra, metriche + azioni a destra. Mobile Figma: share+flag a sinistra, metriche colonna, data a destra */}
        <footer className={styles.metaStrip}>
          <div className={styles.metaStripInner}>
            {publishedDate ? (
              <time className={styles.stripDate} dateTime={poem?.published_at ?? undefined}>
                {publishedDate}
              </time>
            ) : null}

            <SocialActions
              target={poem}
              contentType="poem"
              apiBasePath="/poems"
              publicBasePath="/poems"
              metrics={{ views: viewsCount, likes: likesCount, comments: commentsCount }}
              metricsOrder={
                isMobileStrip ? ['views', 'likes', 'comments'] : ['likes', 'comments', 'views']
              }
              metricsPlacement={isMobileStrip ? 'end' : 'start'}
              shareLabel={t('poems.featured.share')}
              reportLabel={t('poems.actions.report')}
              variant="list"
              layout="row"
              className={styles.stripSocial}
              metricsClassName={styles.stripMetrics}
              metricClassName={styles.stripMetric}
              metricValueClassName={styles.stripMetricValue}
              viewIconClassName={styles.stripMetricIcon}
              likeIconClassName={styles.stripMetricIcon}
              commentIconClassName={styles.stripMetricIcon}
              buttonsClassName={styles.stripActionButtons}
              iconClassName={styles.stripActionIcon}
              shareIconClassName={styles.stripActionIcon}
              reportIconClassName={styles.stripActionIcon}
              onComments={onComments}
            />
          </div>
        </footer>
      </article>
      {isOwner ? (
        <div className={styles.translationsManager}>
          <ContentTranslationsManager
            contentType="poem"
            item={poem}
            onSaved={(saved) => {
              setData((prev) => withSavedTranslation(prev, saved))
              translationView.remember(saved)
            }}
            onDeleted={(deletedLocale) => {
              setData((prev) => withoutTranslation(prev, deletedLocale))
              translationView.forget(deletedLocale)
            }}
          />
        </div>
      ) : null}
    </section>
  )
}
