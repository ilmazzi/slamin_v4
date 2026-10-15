import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  defaultVisibility,
  normalizeWebsiteUrl,
  ownerProfileFromUser,
  PROFILE_SECTIONS,
  updateMyProfile,
} from '../../app/api/profile.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import AppToast from '../../components/shared/AppToast.jsx'
import MainLayout from '../../components/layout/MainLayout.jsx'
import NotificationEmailPreferences from './NotificationEmailPreferences.jsx'
import styles from './ProfilePage.module.css'

const MAX_AVATAR_BYTES = 2 * 1024 * 1024
const MAX_BANNER_BYTES = 5 * 1024 * 1024

function formFromProfile(profile) {
  const visibility = defaultVisibility(profile?.visibility)

  return {
    display_name: profile?.display_name ?? '',
    nickname: profile?.nickname ?? '',
    bio: profile?.bio ?? '',
    location: profile?.location ?? '',
    website_url: profile?.website_url ?? '',
    is_public: profile?.is_public !== false,
    visibility: {
      bio: visibility.bio !== false,
      location: visibility.location !== false,
      website_url: visibility.website_url !== false,
      email: visibility.email === true,
      ...Object.fromEntries(PROFILE_SECTIONS.map((section) => [section, visibility[section] !== false])),
    },
  }
}

function revokePreview(ref) {
  if (ref.current) {
    URL.revokeObjectURL(ref.current)
    ref.current = ''
  }
}

export default function ProfileEditPage() {
  const { t } = useI18n()
  const { token, user, refreshUser } = useAuth()
  const avatarInputRef = useRef(null)
  const bannerInputRef = useRef(null)
  const avatarPreviewRef = useRef('')
  const bannerPreviewRef = useRef('')
  const profile = ownerProfileFromUser(user)
  const [form, setForm] = useState(() => formFromProfile(profile))
  const [avatarFile, setAvatarFile] = useState(null)
  const [bannerFile, setBannerFile] = useState(null)
  const [removeAvatar, setRemoveAvatar] = useState(false)
  const [removeBanner, setRemoveBanner] = useState(false)
  const [previewUrl, setPreviewUrl] = useState(profile?.avatar_url ? resolvePublicUrl(profile.avatar_url) : '')
  const [bannerPreviewUrl, setBannerPreviewUrl] = useState(
    profile?.banner_url ? resolvePublicUrl(profile.banner_url) : '',
  )
  const [errorMessage, setErrorMessage] = useState('')
  const [toastMessage, setToastMessage] = useState('')
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    const next = ownerProfileFromUser(user)
    setForm(formFromProfile(next))
    setPreviewUrl((current) => {
      if (avatarPreviewRef.current) {
        return current
      }
      return next?.avatar_url ? resolvePublicUrl(next.avatar_url) : ''
    })
    setBannerPreviewUrl((current) => {
      if (bannerPreviewRef.current) {
        return current
      }
      return next?.banner_url ? resolvePublicUrl(next.banner_url) : ''
    })
  }, [user])

  useEffect(() => {
    return () => {
      revokePreview(avatarPreviewRef)
      revokePreview(bannerPreviewRef)
    }
  }, [])

  const onFieldChange = (event) => {
    const { name, value } = event.target
    setForm((current) => ({ ...current, [name]: value }))
  }

  const onToggle = (name) => {
    setForm((current) => ({ ...current, [name]: !current[name] }))
  }

  const onVisibilityToggle = (name) => {
    setForm((current) => ({
      ...current,
      visibility: {
        ...current.visibility,
        [name]: !current.visibility[name],
      },
    }))
  }

  const onImageChange = ({ event, maxBytes, tooLargeKey, previewRef, setFile, setRemove, setPreview }) => {
    const file = event.target.files?.[0]
    if (!file) {
      return
    }

    if (file.size > maxBytes) {
      setErrorMessage(t(tooLargeKey))
      event.target.value = ''
      return
    }

    revokePreview(previewRef)
    const objectUrl = URL.createObjectURL(file)
    previewRef.current = objectUrl
    setFile(file)
    setRemove(false)
    setPreview(objectUrl)
    setErrorMessage('')
  }

  const onAvatarChange = (event) => {
    onImageChange({
      event,
      maxBytes: MAX_AVATAR_BYTES,
      tooLargeKey: 'profile.page.avatar_too_large',
      previewRef: avatarPreviewRef,
      setFile: setAvatarFile,
      setRemove: setRemoveAvatar,
      setPreview: setPreviewUrl,
    })
  }

  const onBannerChange = (event) => {
    onImageChange({
      event,
      maxBytes: MAX_BANNER_BYTES,
      tooLargeKey: 'profile.page.banner_too_large',
      previewRef: bannerPreviewRef,
      setFile: setBannerFile,
      setRemove: setRemoveBanner,
      setPreview: setBannerPreviewUrl,
    })
  }

  const onRemoveAvatar = () => {
    revokePreview(avatarPreviewRef)
    setAvatarFile(null)
    setRemoveAvatar(Boolean(profile?.avatar_url))
    setPreviewUrl('')
    if (avatarInputRef.current) {
      avatarInputRef.current.value = ''
    }
  }

  const onRemoveBanner = () => {
    revokePreview(bannerPreviewRef)
    setBannerFile(null)
    setRemoveBanner(Boolean(profile?.banner_url))
    setBannerPreviewUrl('')
    if (bannerInputRef.current) {
      bannerInputRef.current.value = ''
    }
  }

  const onSubmit = async (event) => {
    event.preventDefault()
    setErrorMessage('')
    setIsSaving(true)

    try {
      await updateMyProfile(
        {
          display_name: form.display_name.trim() || null,
          nickname: form.nickname.trim(),
          bio: form.bio.trim() || null,
          location: form.location.trim() || null,
          website_url: normalizeWebsiteUrl(form.website_url),
          is_public: form.is_public,
          visibility: form.visibility,
          ...(avatarFile ? { avatar: avatarFile } : {}),
          ...(removeAvatar ? { remove_avatar: true } : {}),
          ...(bannerFile ? { banner: bannerFile } : {}),
          ...(removeBanner ? { remove_banner: true } : {}),
        },
        { token },
      )

      revokePreview(avatarPreviewRef)
      revokePreview(bannerPreviewRef)
      setAvatarFile(null)
      setBannerFile(null)
      setRemoveAvatar(false)
      setRemoveBanner(false)
      await refreshUser()
      setToastMessage(t('profile.page.saved'))
    } catch (error) {
      setErrorMessage(error?.message || t('profile.page.save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <h1 className={styles.title}>{t('profile.page.edit_title')}</h1>
          {profile?.nickname ? (
            <Link className={styles.secondaryLink} to={`/u/${encodeURIComponent(profile.nickname)}`}>
              {t('profile.page.view_public')}
            </Link>
          ) : null}
        </header>

        {errorMessage ? <p className={styles.error}>{errorMessage}</p> : null}

        <form className={styles.form} onSubmit={onSubmit}>
          <div className={styles.bannerEditor}>
            {bannerPreviewUrl ? (
              <img className={styles.bannerPreview} src={bannerPreviewUrl} alt="" />
            ) : (
              <span className={styles.bannerFallback} aria-hidden="true" />
            )}
            <div className={styles.avatarActions}>
              <p className={styles.label}>{t('profile.page.cover')}</p>
              <p className={styles.hint}>{t('profile.page.banner_hint')}</p>
              <div className={styles.avatarButtons}>
                <label className={styles.fileButton}>
                  {t('profile.page.choose_banner')}
                  <input
                    ref={bannerInputRef}
                    className={styles.fileInput}
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    onChange={onBannerChange}
                  />
                </label>
                {bannerPreviewUrl || profile?.banner_url ? (
                  <button className={styles.textButton} type="button" onClick={onRemoveBanner}>
                    {t('profile.page.remove_banner')}
                  </button>
                ) : null}
              </div>
            </div>
          </div>

          <div className={styles.avatarEditor}>
            {previewUrl ? (
              <img className={styles.avatar} src={previewUrl} alt="" />
            ) : (
              <span className={styles.avatarFallback} aria-hidden="true">
                {(form.display_name || form.nickname || '?').slice(0, 2).toUpperCase()}
              </span>
            )}
            <div className={styles.avatarActions}>
              <p className={styles.label}>{t('profile.page.avatar')}</p>
              <p className={styles.hint}>{t('profile.page.avatar_hint')}</p>
              <div className={styles.avatarButtons}>
                <label className={styles.fileButton}>
                  {t('profile.page.choose_photo')}
                  <input
                    ref={avatarInputRef}
                    className={styles.fileInput}
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    onChange={onAvatarChange}
                  />
                </label>
                {previewUrl || profile?.avatar_url ? (
                  <button className={styles.textButton} type="button" onClick={onRemoveAvatar}>
                    {t('profile.page.remove_avatar')}
                  </button>
                ) : null}
              </div>
            </div>
          </div>

          <div className={styles.grid}>
            <label className={styles.field}>
              <span className={styles.label}>{t('profile.page.display_name')}</span>
              <input
                className={styles.input}
                name="display_name"
                value={form.display_name}
                onChange={onFieldChange}
                maxLength={255}
              />
            </label>
            <label className={styles.field}>
              <span className={styles.label}>{t('profile.page.nickname')}</span>
              <input
                className={styles.input}
                name="nickname"
                value={form.nickname}
                onChange={onFieldChange}
                minLength={3}
                maxLength={30}
                required
              />
            </label>
            <label className={`${styles.field} ${styles.full}`}>
              <span className={styles.label}>{t('profile.page.bio')}</span>
              <textarea
                className={styles.textarea}
                name="bio"
                value={form.bio}
                onChange={onFieldChange}
                maxLength={2000}
                rows={5}
                placeholder={t('profile.page.bio_placeholder')}
              />
            </label>
            <label className={styles.field}>
              <span className={styles.label}>{t('profile.page.location')}</span>
              <input
                className={styles.input}
                name="location"
                value={form.location}
                onChange={onFieldChange}
                maxLength={120}
              />
            </label>
            <label className={styles.field}>
              <span className={styles.label}>{t('profile.page.website')}</span>
              <input
                className={styles.input}
                name="website_url"
                value={form.website_url}
                onChange={onFieldChange}
                maxLength={255}
                inputMode="url"
                placeholder={t('profile.page.website_placeholder')}
              />
            </label>
          </div>

          <fieldset className={styles.visibility}>
            <legend className={styles.visibilityTitle}>{t('profile.page.visibility_title')}</legend>
            <label className={styles.switchRow}>
              <input
                type="checkbox"
                checked={form.is_public}
                onChange={() => onToggle('is_public')}
              />
              <span>
                <strong>{t('profile.page.profile_public')}</strong>
                <span className={styles.hint}>{t('profile.page.profile_public_hint')}</span>
              </span>
            </label>
            <label className={styles.switchRow}>
              <input
                type="checkbox"
                checked={form.visibility.bio}
                onChange={() => onVisibilityToggle('bio')}
              />
              <span>{t('profile.page.show_bio')}</span>
            </label>
            <label className={styles.switchRow}>
              <input
                type="checkbox"
                checked={form.visibility.location}
                onChange={() => onVisibilityToggle('location')}
              />
              <span>{t('profile.page.show_location')}</span>
            </label>
            <label className={styles.switchRow}>
              <input
                type="checkbox"
                checked={form.visibility.website_url}
                onChange={() => onVisibilityToggle('website_url')}
              />
              <span>{t('profile.page.show_website')}</span>
            </label>
            <label className={styles.switchRow}>
              <input
                type="checkbox"
                checked={form.visibility.email}
                onChange={() => onVisibilityToggle('email')}
              />
              <span>{t('profile.page.show_email')}</span>
            </label>
          </fieldset>

          <fieldset className={styles.visibility}>
            <legend className={styles.visibilityTitle}>{t('profile.page.sections_title')}</legend>
            <p className={styles.hint}>{t('profile.page.sections_hint')}</p>
            {PROFILE_SECTIONS.map((section) => (
              <label key={section} className={styles.switchRow}>
                <input
                  type="checkbox"
                  checked={form.visibility[section]}
                  onChange={() => onVisibilityToggle(section)}
                />
                <span>{t(`profile.page.show_${section}`)}</span>
              </label>
            ))}
          </fieldset>

          <div className={styles.formActions}>
            <button className={styles.submit} type="submit" disabled={isSaving}>
              {isSaving ? t('profile.page.saving') : t('profile.page.save')}
            </button>
          </div>
        </form>
        <div className={`${styles.form} ${styles.settingsCard}`}>
          <NotificationEmailPreferences onMessage={setToastMessage} />
        </div>
      </section>
      <AppToast
        message={toastMessage}
        isVisible={Boolean(toastMessage)}
        onClose={() => setToastMessage('')}
      />
    </MainLayout>
  )
}
