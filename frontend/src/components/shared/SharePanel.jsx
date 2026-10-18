import { useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { Check, Download, Link, Mail, MessageCircle, Send, Share2, X } from 'lucide-react'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './SharePanel.module.css'

const SHARE_FORMATS = [
  { id: 'square', ratio: '1 / 1', labelKey: 'square' },
  { id: 'portrait', ratio: '4 / 5', labelKey: 'portrait' },
  { id: 'story', ratio: '9 / 16', labelKey: 'story' },
]

function buildShareTargets(title, url, t) {
  const encodedUrl = encodeURIComponent(url)
  const encodedTitle = encodeURIComponent(title)
  const textWithUrl = encodeURIComponent(title ? `${title} ${url}` : url)

  return [
    {
      id: 'whatsapp',
      label: 'WhatsApp',
      href: `https://wa.me/?text=${textWithUrl}`,
      icon: <MessageCircle aria-hidden="true" />,
    },
    {
      id: 'telegram',
      label: 'Telegram',
      href: `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`,
      icon: <Send aria-hidden="true" />,
    },
    {
      id: 'facebook',
      label: 'Facebook',
      href: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
      icon: <span className={styles.glyph} aria-hidden="true">f</span>,
    },
    {
      id: 'x',
      label: 'X',
      href: `https://x.com/intent/post?url=${encodedUrl}&text=${encodedTitle}`,
      icon: <span className={styles.glyph} aria-hidden="true">X</span>,
    },
    {
      id: 'linkedin',
      label: 'LinkedIn',
      href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
      icon: <span className={styles.glyph} aria-hidden="true">in</span>,
    },
    {
      id: 'email',
      label: t('common.share_panel.email'),
      href: `mailto:?subject=${encodedTitle}&body=${encodeURIComponent(title ? `${title}\n\n${url}` : url)}`,
      icon: <Mail aria-hidden="true" />,
      sameTab: true,
    },
  ]
}

export default function SharePanel({
  isOpen,
  title = '',
  url,
  imageName = 'slamin-share.png',
  imageSrc,
  loadImage,
  templates = [],
  defaultTemplate,
  onNativeShare,
  onClose,
}) {
  const { t } = useI18n()
  const [isCopied, setIsCopied] = useState(false)
  const [isDownloading, setIsDownloading] = useState(false)
  const [isSharing, setIsSharing] = useState(false)
  const [downloadFailed, setDownloadFailed] = useState(false)
  const [selectedFormat, setSelectedFormat] = useState('square')
  const [selectedTemplate, setSelectedTemplate] = useState(defaultTemplate || templates[0] || 'poster')
  const [previewUrl, setPreviewUrl] = useState('')
  const [loadedPreviewSrc, setLoadedPreviewSrc] = useState('')
  const [previewStatus, setPreviewStatus] = useState('idle')
  const [thumbsEnabled, setThumbsEnabled] = useState(false)
  const inputRef = useRef(null)
  const closeRef = useRef(null)
  const templateKey = templates.join('|')
  const canPreview = Boolean(imageSrc || loadImage)
  const previewSrc = isOpen && imageSrc
    ? imageSrc(selectedFormat, selectedTemplate)
    : ''
  const previewReady = Boolean(previewSrc) && loadedPreviewSrc === previewSrc
  const previewFailed = Boolean(previewSrc) && loadedPreviewSrc === `error:${previewSrc}`
  const shownPreviewUrl = previewSrc || previewUrl
  const isPreviewReady = previewSrc ? previewReady : previewStatus === 'ready'
  const isPreviewLoading = previewSrc ? !previewReady && !previewFailed : previewStatus === 'loading'
  const isPreviewError = previewSrc ? previewFailed : previewStatus === 'error'

  useEffect(() => {
    if (!isOpen) {
      return undefined
    }

    closeRef.current?.focus()
    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        onClose()
      }
    }

    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [isOpen, onClose])

  useEffect(() => {
    if (!isCopied) {
      return undefined
    }

    const timeout = window.setTimeout(() => setIsCopied(false), 2000)
    return () => window.clearTimeout(timeout)
  }, [isCopied])

  useEffect(() => {
    if (!isOpen) {
      return
    }

    const next = defaultTemplate || templates[0] || 'poster'
    setSelectedTemplate((current) => (
      templates.includes(current) ? current : next
    ))
  }, [defaultTemplate, isOpen, templateKey, templates])

  useEffect(() => {
    if (!isOpen) {
      setPreviewUrl('')
      setLoadedPreviewSrc('')
      setPreviewStatus('idle')
      setThumbsEnabled(false)
      return undefined
    }

    if (previewSrc) {
      setPreviewStatus(previewReady ? 'ready' : (previewFailed ? 'error' : 'loading'))
      if (previewReady) {
        setThumbsEnabled(true)
      }
      return undefined
    }

    if (!loadImage) {
      setPreviewUrl('')
      setPreviewStatus('idle')
      return undefined
    }

    let active = true
    let objectUrl = ''
    setPreviewStatus('loading')

    loadImage(selectedFormat, selectedTemplate)
      .then((blob) => {
        if (!active || !blob) {
          if (active) setPreviewStatus('error')
          return
        }
        objectUrl = URL.createObjectURL(blob)
        setPreviewUrl(objectUrl)
        setPreviewStatus('ready')
        setThumbsEnabled(true)
      })
      .catch(() => {
        if (active) setPreviewStatus('error')
      })

    return () => {
      active = false
      if (objectUrl) URL.revokeObjectURL(objectUrl)
    }
  }, [isOpen, loadImage, previewFailed, previewReady, previewSrc, selectedFormat, selectedTemplate])

  if (!isOpen || !url) {
    return null
  }

  async function handleCopy() {
    try {
      await navigator.clipboard.writeText(url)
      setIsCopied(true)
    } catch {
      inputRef.current?.select()
    }
  }

  async function handleDownload() {
    setIsDownloading(true)
    setDownloadFailed(false)
    try {
      const blob = await loadImage(selectedFormat, selectedTemplate)
      if (!blob) {
        throw new Error('missing image')
      }
      const objectUrl = URL.createObjectURL(blob)
      const anchor = document.createElement('a')
      anchor.href = objectUrl
      anchor.download = imageName.replace(/\.png$/i, `-${selectedFormat}-${selectedTemplate}.png`)
      anchor.click()
      URL.revokeObjectURL(objectUrl)
    } catch {
      setDownloadFailed(true)
    } finally {
      setIsDownloading(false)
    }
  }

  async function handleNativeShare() {
    if (!onNativeShare) return
    setIsSharing(true)
    try {
      await onNativeShare(selectedFormat, selectedTemplate)
    } finally {
      setIsSharing(false)
    }
  }

  const stop = (event) => event.stopPropagation()

  return createPortal(
    <div
      className={styles.overlay}
      role="presentation"
      onClick={(event) => {
        event.stopPropagation()
        onClose()
      }}
    >
      <section
        className={styles.panel}
        role="dialog"
        aria-modal="true"
        aria-labelledby="share-panel-title"
        onClick={stop}
      >
        <header className={styles.header}>
          <h3 id="share-panel-title" className={styles.title}>{t('common.share_panel.title')}</h3>
          <button
            ref={closeRef}
            type="button"
            className={styles.close}
            aria-label={t('common.share_panel.close')}
            onClick={onClose}
          >
            <X aria-hidden="true" />
          </button>
        </header>

        {title ? <p className={styles.subject}>{title}</p> : null}

        {canPreview ? (
          <div className={styles.composer}>
            <div className={styles.previewColumn}>
              <div
                className={styles.preview}
                style={{
                  aspectRatio: isPreviewReady
                    ? undefined
                    : SHARE_FORMATS.find((format) => format.id === selectedFormat)?.ratio,
                }}
              >
                {shownPreviewUrl ? (
                  <img
                    src={shownPreviewUrl}
                    alt={t('common.share_panel.preview_alt')}
                    onLoad={() => {
                      if (previewSrc) {
                        setLoadedPreviewSrc(previewSrc)
                      }
                    }}
                    onError={() => {
                      if (previewSrc) {
                        setLoadedPreviewSrc(`error:${previewSrc}`)
                      }
                    }}
                  />
                ) : null}
                {isPreviewLoading ? (
                  <span className={styles.previewState}>{t('common.loading')}</span>
                ) : null}
                {isPreviewError ? (
                  <span className={styles.previewState}>{t('common.share_panel.preview_failed')}</span>
                ) : null}
              </div>
            </div>

            <div className={styles.composerControls}>
              {templates.length > 1 ? (
                <fieldset className={styles.formatFieldset}>
                  <legend>{t('common.share_panel.choose_template')}</legend>
                  <div className={styles.templateOptions}>
                    {templates.map((template) => (
                      <button
                        key={template}
                        type="button"
                        className={styles.templateButton}
                        data-selected={selectedTemplate === template ? 'true' : 'false'}
                        aria-pressed={selectedTemplate === template}
                        onClick={() => setSelectedTemplate(template)}
                      >
                        <span className={styles.templateThumb} aria-hidden="true">
                          {thumbsEnabled && imageSrc ? (
                            <img src={imageSrc('square', template)} alt="" />
                          ) : null}
                        </span>
                        <span>{t(`common.share_panel.templates.${template}`)}</span>
                      </button>
                    ))}
                  </div>
                </fieldset>
              ) : null}

              <fieldset className={styles.formatFieldset}>
                <legend>{t('common.share_panel.choose_format')}</legend>
                <div className={styles.formatOptions}>
                  {SHARE_FORMATS.map((format) => (
                    <button
                      key={format.id}
                      type="button"
                      className={styles.formatButton}
                      data-selected={selectedFormat === format.id ? 'true' : 'false'}
                      aria-pressed={selectedFormat === format.id}
                      onClick={() => setSelectedFormat(format.id)}
                    >
                      <span
                        className={styles.formatShape}
                        style={{ aspectRatio: format.ratio }}
                        aria-hidden="true"
                      />
                      <span>{t(`common.share_panel.formats.${format.labelKey}`)}</span>
                    </button>
                  ))}
                </div>
              </fieldset>

              {onNativeShare && typeof navigator.share === 'function' ? (
                <button
                  type="button"
                  className={styles.nativeShareButton}
                  onClick={handleNativeShare}
                  disabled={isSharing || isPreviewLoading}
                >
                  <Share2 aria-hidden="true" />
                  <span>{isSharing ? t('common.loading') : t('common.share_panel.share_now')}</span>
                </button>
              ) : null}

              <button
                type="button"
                className={styles.downloadButton}
                onClick={handleDownload}
                disabled={isDownloading || isPreviewLoading}
              >
                <Download aria-hidden="true" />
                <span className={styles.downloadText}>
                  <strong>{isDownloading ? t('common.loading') : t('common.share_panel.download_image')}</strong>
                  <small>{downloadFailed ? t('common.share_panel.download_failed') : t('common.share_panel.download_hint')}</small>
                </span>
              </button>
            </div>
          </div>
        ) : null}

        <h4 className={styles.sectionTitle}>{t('common.share_panel.share_link')}</h4>

        <ul className={styles.targets}>
          {buildShareTargets(title, url, t).map((target) => (
            <li key={target.id}>
              <a
                href={target.href}
                className={`${styles.target} ${styles[target.id]}`}
                target={target.sameTab ? undefined : '_blank'}
                rel={target.sameTab ? undefined : 'noopener noreferrer'}
                onClick={onClose}
              >
                <span className={styles.targetIcon}>{target.icon}</span>
                <span className={styles.targetLabel}>{target.label}</span>
              </a>
            </li>
          ))}
        </ul>

        <div className={styles.linkRow}>
          <input
            ref={inputRef}
            className={styles.linkInput}
            value={url}
            readOnly
            aria-label={t('common.share_panel.copy_link')}
            onFocus={(event) => event.target.select()}
          />
          <button type="button" className={styles.copyButton} onClick={handleCopy}>
            {isCopied ? <Check aria-hidden="true" /> : <Link aria-hidden="true" />}
            <span>{isCopied ? t('common.share_panel.copied') : t('common.share_panel.copy_link')}</span>
          </button>
        </div>

      </section>
    </div>,
    document.body,
  )
}
