import { useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { Check, Download, Link, Mail, MessageCircle, Send, X } from 'lucide-react'
import { useI18n } from '../../app/hooks/useI18n.js'
import styles from './SharePanel.module.css'

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

export default function SharePanel({ isOpen, title = '', url, imageName = 'slamin-share.png', loadImage, onClose }) {
  const { t } = useI18n()
  const [isCopied, setIsCopied] = useState(false)
  const [isDownloading, setIsDownloading] = useState(false)
  const [downloadFailed, setDownloadFailed] = useState(false)
  const inputRef = useRef(null)
  const closeRef = useRef(null)

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
      const blob = await loadImage()
      if (!blob) {
        throw new Error('missing image')
      }
      const objectUrl = URL.createObjectURL(blob)
      const anchor = document.createElement('a')
      anchor.href = objectUrl
      anchor.download = imageName
      anchor.click()
      URL.revokeObjectURL(objectUrl)
    } catch {
      setDownloadFailed(true)
    } finally {
      setIsDownloading(false)
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

        {loadImage ? (
          <button type="button" className={styles.downloadButton} onClick={handleDownload} disabled={isDownloading}>
            <Download aria-hidden="true" />
            <span className={styles.downloadText}>
              <strong>{isDownloading ? t('common.loading') : t('common.share_panel.download_image')}</strong>
              <small>{downloadFailed ? t('common.share_panel.download_failed') : t('common.share_panel.download_hint')}</small>
            </span>
          </button>
        ) : null}
      </section>
    </div>,
    document.body,
  )
}
