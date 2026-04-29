import { useEffect, useState } from 'react'

function buildEventName(contentType, action) {
  const normalizedType = String(contentType ?? '').trim().toLowerCase()
  if (!normalizedType) {
    return action
  }

  return `${normalizedType}:${action}`
}

export function useContentFeedbackUi({
  t,
  contentType,
  knownItems = [],
  shareCopiedMessage,
  shareSentMessage,
  shareFailedMessage,
  reportSubmittedMessage,
}) {
  const [reportModalItem, setReportModalItem] = useState(null)
  const [toastMessage, setToastMessage] = useState('')

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

  const handleReportConfirm = () => {
    setReportModalItem(null)
    setToastMessage(reportSubmittedMessage)
  }

  return {
    reportModalItem,
    toastMessage,
    setReportModalItem,
    setToastMessage,
    handleReportConfirm,
  }
}
