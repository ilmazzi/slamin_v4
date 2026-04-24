import { useEffect, useState } from 'react'

export function useArticleFeedbackUi(t, knownArticles = []) {
  const [reportModalArticle, setReportModalArticle] = useState(null)
  const [toastMessage, setToastMessage] = useState('')

  useEffect(() => {
    const findKnownArticle = (detail = {}) => {
      if (detail.article) {
        return detail.article
      }

      if (detail.articleId != null) {
        return knownArticles.find((item) => item?.id === detail.articleId) ?? null
      }

      if (detail.articleSlug) {
        return knownArticles.find((item) => item?.slug === detail.articleSlug) ?? null
      }

      return null
    }

    const handleReport = (event) => {
      const nextArticle = findKnownArticle(event?.detail ?? {})
      setReportModalArticle(nextArticle)
    }

    const handleShareSuccess = (event) => {
      const mode = event?.detail?.mode
      if (mode === 'clipboard') {
        setToastMessage(t('articles.actions.share_copied'))
      } else {
        setToastMessage(t('articles.actions.share_sent'))
      }
    }

    const handleShareError = () => {
      setToastMessage(t('articles.actions.share_failed'))
    }

    window.addEventListener('article:report', handleReport)
    window.addEventListener('article:share-success', handleShareSuccess)
    window.addEventListener('article:share-error', handleShareError)

    return () => {
      window.removeEventListener('article:report', handleReport)
      window.removeEventListener('article:share-success', handleShareSuccess)
      window.removeEventListener('article:share-error', handleShareError)
    }
  }, [knownArticles, t])

  const handleReportConfirm = () => {
    setReportModalArticle(null)
    setToastMessage(t('articles.actions.report_submitted'))
  }

  return {
    reportModalArticle,
    toastMessage,
    setReportModalArticle,
    setToastMessage,
    handleReportConfirm,
  }
}

