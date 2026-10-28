import { resolvePublicUrl } from '../../app/api/url.js'
import { formatShortPublishedDate } from '../../app/formatters/date.js'
import { useArticleDetail } from '../../app/hooks/useArticleDetail.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import AuthorLink from '../shared/AuthorLink.jsx'
import SocialActions from '../shared/SocialActions.jsx'
import { useAuth } from '../../app/hooks/useAuth.js'
import {
  useContentTranslationView,
  withoutTranslation,
  withSavedTranslation,
} from '../../app/hooks/useContentTranslationView.js'
import ContentLanguageSwitcher from '../shared/ContentLanguageSwitcher.jsx'
import ContentTranslationsManager from '../shared/ContentTranslationsManager.jsx'
import RichTextContent from '../shared/RichTextContent.jsx'
import OwnerContentActions from '../shared/OwnerContentActions.jsx'
import styles from './ArticleCard.module.css'
import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { deleteArticle } from '../../app/api/articles.js'

export default function ArticleCard({ slug, onComments, onLoaded }) {
  const { t, locale } = useI18n()
  const { user, token } = useAuth()
  const navigate = useNavigate()
  const { data: article, isLoading, error, setData } = useArticleDetail(slug)
  const translationView = useContentTranslationView('article', article)

  useEffect(() => {
    if (article) {
      onLoaded?.(article)
    }
  }, [article, onLoaded])

  if (!slug) {
    return <div className={styles.article}>{t('articles.missing_slug')}</div>
  }

  if (isLoading) {
    return <div className={styles.article}>{t('common.loading')}</div>
  }

  if (error) {
    return <div className={styles.article}>{error}</div>
  }

  if (!article) {
    return <div className={styles.article}>{t('articles.not_found')}</div>
  }

  const authorLabel = article?.author?.display_name || article?.author?.nickname || 'Slamin'
  const authorAvatar = resolvePublicUrl(article?.author?.avatar_url)
  const publishedDate = formatShortPublishedDate(article?.published_at, locale, '')
  const categoryLabel = article?.category?.name || t('articles.no_category')
  const coverUrl = article?.cover_image_url || ''
  const viewsCount = Number.isFinite(Number(article?.views_count)) ? Number(article.views_count) : 0
  const likesCount = Number.isFinite(Number(article?.likes_count)) ? Number(article.likes_count) : 0
  const commentsCount = Number.isFinite(Number(article?.comments_count)) ? Number(article.comments_count) : 0
  const translation = translationView.translation
  const displayTitle = translation?.title ?? article.title
  const displayExcerpt = translation ? translation.summary : article.excerpt
  const displayContent = translation?.content ?? article.content
  const contentLang = translation?.locale ?? article.language ?? undefined
  const isOwner = Boolean(user?.id) && Number(user.id) === Number(article?.author?.id)

  return (
    <>
    <article className={styles.card}>
      <header className={styles.topBar}>
        <AuthorLink
          author={article?.author}
          className={styles.author}
          ariaLabel={t('common.view_profile', { name: authorLabel })}
        >
          {authorAvatar ? (
            <img src={authorAvatar} alt="" className={styles.avatar} />
          ) : (
            <span className={styles.avatar} aria-hidden="true" />
          )}
          <span className={styles.authorName}>{authorLabel}</span>
        </AuthorLink>

        <div className={styles.meta}>
          {publishedDate ? <span className={styles.date}>{publishedDate}</span> : null}
          <SocialActions
            article={article}
            contentType="article"
            apiBasePath="/articles"
            publicBasePath="/articles"
            metrics={{ views: viewsCount, likes: likesCount, comments: commentsCount }}
            shareLabel={t('articles.featured.share')}
            reportLabel={t('articles.actions.report')}
            variant="list"
            className={styles.socialActions}
            metricsClassName={styles.metrics}
            metricClassName={styles.metric}
            metricValueClassName={styles.metricValue}
            viewIconClassName={styles.metricIcon}
            likeIconClassName={styles.metricIcon}
            commentIconClassName={styles.metricIcon}
            buttonsClassName={styles.actions}
            iconClassName={styles.actionIcon}
            shareIconClassName={styles.actionIconShare}
            reportIconClassName={styles.actionIconReport}
            onComments={onComments}
          />
        </div>

        <div className={styles.actionsWrap}>
          <span className={styles.categoryPill}>{categoryLabel}</span>
         
        </div>
      </header>

      <div className={styles.inner}>
        <ContentLanguageSwitcher item={article} view={translationView} className={styles.languageSwitcher} />
        <h1 className={styles.title} lang={contentLang}>{displayTitle}</h1>

        <div className={styles.contentGrid}>
          <div className={styles.coverWrap}>
            {coverUrl ? <img src={coverUrl} alt="" className={styles.cover} /> : <div className={styles.coverPlaceholder} aria-hidden="true" />}
          </div>

          <div className={styles.copy} lang={contentLang}>
            {displayExcerpt ? <p className={styles.lead}>{displayExcerpt}</p> : null}
            <div className={styles.rule} aria-hidden="true" />
            <RichTextContent content={displayContent} className={styles.bodyText} lang={contentLang} />
          </div>
        </div>
      </div>
    </article>
    {isOwner ? (
      <div className={styles.ownerActions}>
        <OwnerContentActions
          editHref={`/articles/${encodeURIComponent(article.slug)}/edit`}
          editLabel={t('articles.actions.edit')}
          deleteLabel={t('articles.actions.delete')}
          deletingLabel={t('articles.actions.deleting')}
          confirmMessage={t('articles.actions.delete_confirm')}
          deleteErrorMessage={t('articles.actions.delete_failed')}
          onDelete={async () => {
            await deleteArticle(article.id, { token })
            navigate('/articles', { replace: true })
          }}
        />
      </div>
    ) : null}
    {isOwner ? (
      <div className={styles.translationsManager}>
        <ContentTranslationsManager
          contentType="article"
          item={article}
          withSummary
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
    </>
  )
}
