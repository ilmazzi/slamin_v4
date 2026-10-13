import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { getMyMediaLimits, uploadPhotoMedia, uploadVideoMedia } from '../../app/api/media.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import AppToast from '../../components/shared/AppToast.jsx'
import MainLayout from '../../components/layout/MainLayout.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import styles from './MediaUploadPage.module.css'

function parseTags(value) {
  return String(value ?? '')
    .split(',')
    .map((item) => item.trim())
    .filter((item) => item.length >= 2 && item.length <= 30)
    .slice(0, 5)
}

function formatMaxSize(sizeKb) {
  const kilobytes = Number(sizeKb)
  if (!Number.isFinite(kilobytes) || kilobytes <= 0) {
    return ''
  }

  if (kilobytes >= 1024) {
    const megabytes = kilobytes / 1024
    return `${megabytes >= 10 ? Math.round(megabytes) : megabytes.toFixed(1)} MB`
  }

  return `${Math.round(kilobytes)} KB`
}

function mimeLabel(mimes) {
  return (Array.isArray(mimes) ? mimes : [])
    .map((item) => String(item).replace(/^(image|video)\//, ''))
    .filter(Boolean)
    .join(', ')
}

export default function MediaUploadPage() {
  const navigate = useNavigate()
  const { t } = useI18n()
  const { token, isAuthenticated, user } = useAuth()
  const canUpload = user?.account_type !== 'audience'

  const [limits, setLimits] = useState(null)
  const [isLoadingLimits, setIsLoadingLimits] = useState(canUpload)
  const [mediaType, setMediaType] = useState('video')
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [visibility, setVisibility] = useState('public')
  const [tags, setTags] = useState('')
  const [file, setFile] = useState(null)
  const [errorMessage, setErrorMessage] = useState('')
  const [toastMessage, setToastMessage] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    if (!token || !canUpload) {
      setIsLoadingLimits(false)
      return undefined
    }

    const controller = new AbortController()
    let cancelled = false

    async function loadLimits() {
      setIsLoadingLimits(true)
      setErrorMessage('')

      try {
        const nextLimits = await getMyMediaLimits({ token, signal: controller.signal })
        if (cancelled) {
          return
        }

        setLimits(nextLimits)
      } catch (error) {
        if (cancelled || error?.name === 'AbortError') {
          return
        }

        if (error?.status === 403 || error?.message === 'FORBIDDEN') {
          setLimits(null)
          setErrorMessage(t('media.upload.error_forbidden'))
        } else {
          setErrorMessage(error?.message || t('media.upload.error'))
        }
      } finally {
        if (!cancelled) {
          setIsLoadingLimits(false)
        }
      }
    }

    loadLimits()

    return () => {
      cancelled = true
      controller.abort()
    }
  }, [token, canUpload, t])

  const remainingPhotos = limits?.remaining_photo_uploads ?? 0
  const remainingVideos = limits?.remaining_video_uploads ?? 0
  const peertubeConfigured = Boolean(limits?.peertube_configured)
  const photoMimes = limits?.photo_allowed_mimes ?? ['image/jpeg', 'image/png', 'image/webp']
  const videoMimes = limits?.video_allowed_mimes ?? ['video/mp4', 'video/webm']

  useEffect(() => {
    if (!limits) {
      return
    }

    if ((remainingVideos <= 0 || !peertubeConfigured) && remainingPhotos > 0) {
      setMediaType('photo')
    }
  }, [limits, remainingPhotos, remainingVideos, peertubeConfigured])

  const isVideo = mediaType === 'video'
  const remaining = isVideo ? remainingVideos : remainingPhotos
  const allowedMimes = isVideo ? videoMimes : photoMimes
  const maxSizeKb = isVideo ? limits?.video_max_size_kb : limits?.photo_max_size_kb
  const videoBlocked = isVideo && !peertubeConfigured
  const limitReached = remaining <= 0
  const canSubmit = canUpload && !isLoadingLimits && !videoBlocked && !limitReached && !isSubmitting

  const fileHelp = useMemo(() => {
    const size = formatMaxSize(maxSizeKb)
    const mimes = mimeLabel(allowedMimes) || allowedMimes.join(', ')
    return t(isVideo ? 'media.upload.file_help_video' : 'media.upload.file_help_photo', {
      mimes,
      size,
    })
  }, [allowedMimes, isVideo, maxSizeKb, t])

  const onTypeChange = (nextType) => {
    setMediaType(nextType)
    setFile(null)
    setErrorMessage('')
  }

  const onFileChange = (event) => {
    const nextFile = event.target.files?.[0] ?? null
    if (!nextFile) {
      setFile(null)
      return
    }

    if (maxSizeKb && nextFile.size / 1024 > Number(maxSizeKb)) {
      setErrorMessage(t('media.upload.error_file_too_large'))
      event.target.value = ''
      setFile(null)
      return
    }

    if (Array.isArray(allowedMimes) && allowedMimes.length > 0 && !allowedMimes.includes(nextFile.type)) {
      setErrorMessage(t('media.upload.error_file_type'))
      event.target.value = ''
      setFile(null)
      return
    }

    setErrorMessage('')
    setFile(nextFile)
  }

  const handleSubmit = async (event) => {
    event.preventDefault()

    if (!isAuthenticated || !token) {
      setErrorMessage(t('media.upload.error_not_authenticated'))
      return
    }

    if (!canUpload) {
      setErrorMessage(t('media.upload.error_forbidden'))
      return
    }

    if (!file) {
      setErrorMessage(t('media.upload.error_missing_file'))
      return
    }

    if (videoBlocked) {
      setErrorMessage(t('media.upload.peertube_missing'))
      return
    }

    if (limitReached) {
      setErrorMessage(t(isVideo ? 'media.upload.video_limit_reached' : 'media.upload.photo_limit_reached'))
      return
    }

    const formData = new FormData()
    formData.append('title', title.trim())
    formData.append('description', description.trim())
    formData.append('visibility', visibility)
    formData.append('file', file)

    if (isVideo) {
      parseTags(tags).forEach((tag) => {
        formData.append('tags[]', tag)
      })
    }

    setIsSubmitting(true)
    setErrorMessage('')

    try {
      const media = isVideo
        ? await uploadVideoMedia(formData, { token })
        : await uploadPhotoMedia(formData, { token })

      setToastMessage(t('media.upload.success'))
      if (media?.id) {
        navigate(`/media/${media.id}`, { replace: true })
        return
      }

      navigate('/media', { replace: true })
    } catch (error) {
      setErrorMessage(error?.message || t('media.upload.error'))
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <MainLayout themeTone="media" syncHeaderWithTheme={false}>
      <div className={pageStyles.mediaPage}>
        <form className={styles.form} onSubmit={handleSubmit}>
          <header className={styles.header}>
            <p className={styles.back}>
              <Link to="/media">{t('media.title')}</Link>
            </p>
            <h1 className={styles.title}>{t('media.upload.title')}</h1>
            <p className={styles.lead}>{t('media.upload.lead')}</p>
          </header>

          {!canUpload ? (
            <p className={styles.error}>{t('media.upload.error_forbidden')}</p>
          ) : null}

          {canUpload && isLoadingLimits ? (
            <p className={styles.loading}>{t('media.upload.loading_limits')}</p>
          ) : null}

          {canUpload && limits ? (
            <p className={styles.limits}>
              {t('media.upload.limits', {
                photos_used: limits.current_photo_count ?? 0,
                photos_limit: limits.photo_limit ?? 0,
                videos_used: limits.current_video_count ?? 0,
                videos_limit: limits.video_limit ?? 0,
              })}
            </p>
          ) : null}

          {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}

          {canUpload && videoBlocked && mediaType === 'video' ? (
            <p className={styles.warning}>{t('media.upload.peertube_missing')}</p>
          ) : null}

          <fieldset className={styles.typeSwitch} disabled={!canUpload || isSubmitting}>
            <legend className={styles.label}>{t('media.upload.type_label')}</legend>
            <div className={styles.typeRow}>
              <button
                type="button"
                className={mediaType === 'video' ? styles.typeActive : styles.typeButton}
                onClick={() => onTypeChange('video')}
              >
                {t('media.upload.type_video')}
              </button>
              <button
                type="button"
                className={mediaType === 'photo' ? styles.typeActive : styles.typeButton}
                onClick={() => onTypeChange('photo')}
              >
                {t('media.upload.type_photo')}
              </button>
            </div>
          </fieldset>

          <div className={styles.grid}>
            <label className={`${styles.field} ${styles.full}`}>
              <span className={styles.label}>{t('media.upload.title_label')}</span>
              <input
                className={styles.input}
                name="title"
                value={title}
                onChange={(event) => setTitle(event.target.value)}
                maxLength={255}
                required
                disabled={!canUpload || isSubmitting}
              />
            </label>

            <label className={`${styles.field} ${styles.full}`}>
              <span className={styles.label}>{t('media.upload.description_label')}</span>
              <textarea
                className={styles.textarea}
                name="description"
                value={description}
                onChange={(event) => setDescription(event.target.value)}
                rows={5}
                disabled={!canUpload || isSubmitting}
              />
            </label>

            <label className={styles.field}>
              <span className={styles.label}>{t('media.upload.visibility_label')}</span>
              <select
                className={styles.input}
                name="visibility"
                value={visibility}
                onChange={(event) => setVisibility(event.target.value)}
                disabled={!canUpload || isSubmitting}
              >
                <option value="public">{t('media.upload.visibility_public')}</option>
                <option value="unlisted">{t('media.upload.visibility_unlisted')}</option>
                <option value="private">{t('media.upload.visibility_private')}</option>
              </select>
            </label>

            {isVideo ? (
              <label className={styles.field}>
                <span className={styles.label}>{t('media.upload.tags_label')}</span>
                <input
                  className={styles.input}
                  name="tags"
                  value={tags}
                  onChange={(event) => setTags(event.target.value)}
                  placeholder={t('media.upload.tags_placeholder')}
                  disabled={!canUpload || isSubmitting}
                />
                <span className={styles.hint}>{t('media.upload.tags_help')}</span>
              </label>
            ) : null}

            <label className={`${styles.field} ${styles.full}`}>
              <span className={styles.label}>{t('media.upload.file_label')}</span>
              <input
                className={styles.fileInput}
                type="file"
                name="file"
                accept={allowedMimes.join(',')}
                onChange={onFileChange}
                required
                disabled={!canUpload || isSubmitting || videoBlocked || limitReached}
              />
              <span className={styles.hint}>{fileHelp}</span>
            </label>
          </div>

          <div className={styles.actions}>
            <button className={styles.submit} type="submit" disabled={!canSubmit}>
              {isSubmitting ? t('media.upload.submitting') : t('media.upload.submit')}
            </button>
          </div>
        </form>
      </div>
      <AppToast message={toastMessage} onDismiss={() => setToastMessage('')} />
    </MainLayout>
  )
}
