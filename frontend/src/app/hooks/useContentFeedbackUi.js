import { useEffect, useState } from 'react'

function buildEventName(contentType, action) {
  const normalizedType = String(contentType ?? '').trim().toLowerCase()
  if (!normalizedType) {
    return action
  }

  return `${normalizedType}:${action}`
}

export function useContentFeedbackUi({
  contentType,
  knownItems = [],
  shareCopiedMessage,
  shareSentMessage,
  shareFailedMessage,
  reportSubmittedMessage,
  reportFailedMessage = 'Unable to submit report',
  submitReport,
}) {
  const [reportModalItem, setReportModalItem] = useState(null)
  const [toastMessage, setToastMessage] = useState('')
  const [isSubmittingReport, setIsSubmittingReport] = useState(false)

  useEffect(() => {
    const normalizedType = String(contentType ?? '').trim().toLowerCase()

    const findKnownItem = (detail = {}) => {
      if (detail.target) {
        return detail.target
      }

      if (detail[normalizedType]) {
        return detail[normalizedType]
      }

      if (detail.targetId != null) {
        return knownItems.find((item) => item?.id === detail.targetId) ?? null
      }

      const idKey = `${normalizedType}Id`
      if (detail[idKey] != null) {
        return knownItems.find((item) => item?.id === detail[idKey]) ?? null
      }

      if (detail.targetSlug) {
        return knownItems.find((item) => item?.slug === detail.targetSlug) ?? null
      }

      const slugKey = `${normalizedType}Slug`
      if (detail[slugKey]) {
        return knownItems.find((item) => item?.slug === detail[slugKey]) ?? null
      }

      return null
    }

    const handleReport = (event) => {
      const nextItem = findKnownItem(event?.detail ?? {})
      setReportModalItem(nextItem)
    }

    const handleShareSuccess = (event) => {
      const mode = event?.detail?.mode
      if (mode === 'clipboard') {
        setToastMessage(shareCopiedMessage)
      } else {
        setToastMessage(shareSentMessage)
      }
    }

    const handleShareError = () => {
      setToastMessage(shareFailedMessage)
    }

    const reportEvent = buildEventName(normalizedType, 'report')
    const shareSuccessEvent = buildEventName(normalizedType, 'share-success')
    const shareErrorEvent = buildEventName(normalizedType, 'share-error')

    window.addEventListener(reportEvent, handleReport)
    window.addEventListener(shareSuccessEvent, handleShareSuccess)
    window.addEventListener(shareErrorEvent, handleShareError)

    return () => {
      window.removeEventListener(reportEvent, handleReport)
      window.removeEventListener(shareSuccessEvent, handleShareSuccess)
      window.removeEventListener(shareErrorEvent, handleShareError)
    }
  }, [contentType, knownItems, reportSubmittedMessage, shareCopiedMessage, shareFailedMessage, shareSentMessage])

  const handleReportConfirm = async ({ reason, details } = {}) => {
    const successMessage = String(reportSubmittedMessage || '').trim() || 'Segnalazione inviata.'
    const failureMessage = String(reportFailedMessage || '').trim() || 'Impossibile inviare la segnalazione.'

    if (!reportModalItem || typeof submitReport !== 'function') {
      setToastMessage(failureMessage)
      return
    }

    try {
      setIsSubmittingReport(true)
      await submitReport(reportModalItem, { reason, details })
      setReportModalItem(null)
      setToastMessage(successMessage)
    } catch (error) {
      const message = typeof error?.message === 'string' && error.message.trim() !== ''
        ? error.message
        : failureMessage
      setToastMessage(message)
    } finally {
      setIsSubmittingReport(false)
    }
  }

  return {
    reportModalItem,
    toastMessage,
    isSubmittingReport,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  }
}
