import { useCallback, useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listContentPublicationSettings, updateContentPublicationSetting } from '../../app/api/admin.js'
import { getMediaSettings, testPeerTubeConnection, updateMediaSettings } from '../../app/api/media.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './AdminSettingsPage.module.css'

const CONTENT_TYPES = ['poem', 'article', 'event']

function asList(value) {
  return Array.isArray(value) ? value.join(', ') : ''
}

function parseList(value) {
  return String(value ?? '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
}

function toInt(value, fallback) {
  const parsed = Number.parseInt(String(value), 10)
  return Number.isFinite(parsed) ? parsed : fallback
}

function toNullable(value) {
  const next = String(value ?? '').trim()
  return next === '' ? null : next
}

function formFromSettings(settings) {
  return {
    peertube_base_url: settings?.peertube_base_url ?? '',
    peertube_admin_username: settings?.peertube_admin_username ?? '',
    peertube_admin_password: '',
    peertube_upload_channel_id: settings?.peertube_upload_channel_id ?? '',
    peertube_verify_ssl: settings?.peertube_verify_ssl !== false,
    peertube_download_enabled: settings?.peertube_download_enabled !== false,
    peertube_default_privacy: String(settings?.peertube_default_privacy ?? 1),
    photo_limit: String(settings?.photo_limit ?? 50),
    video_limit: String(settings?.video_limit ?? 3),
    photo_max_size_kb: String(settings?.photo_max_size_kb ?? 5120),
    video_max_size_kb: String(settings?.video_max_size_kb ?? 102400),
    photo_allowed_mimes: asList(settings?.photo_allowed_mimes),
    video_allowed_mimes: asList(settings?.video_allowed_mimes),
    password_set: Boolean(settings?.peertube_admin_password_set),
  }
}

export default function AdminSettingsPage() {
  const { t } = useI18n()
  const { token } = useAuth()
  const [form, setForm] = useState(() => formFromSettings(null))
  const [publicationSettings, setPublicationSettings] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [isSavingModeration, setIsSavingModeration] = useState(false)
  const [isTesting, setIsTesting] = useState(false)
  const [error, setError] = useState('')
  const [message, setMessage] = useState('')

  const buildPayload = () => {
    const payload = {
      peertube_base_url: toNullable(form.peertube_base_url),
      peertube_admin_username: toNullable(form.peertube_admin_username),
      peertube_upload_channel_id: toNullable(form.peertube_upload_channel_id),
      peertube_verify_ssl: Boolean(form.peertube_verify_ssl),
      peertube_download_enabled: Boolean(form.peertube_download_enabled),
      peertube_default_privacy: toInt(form.peertube_default_privacy, 1),
      photo_limit: toInt(form.photo_limit, 0),
      video_limit: toInt(form.video_limit, 0),
      photo_max_size_kb: toInt(form.photo_max_size_kb, 1),
      video_max_size_kb: toInt(form.video_max_size_kb, 1),
      photo_allowed_mimes: parseList(form.photo_allowed_mimes),
      video_allowed_mimes: parseList(form.video_allowed_mimes),
    }

    const password = String(form.peertube_admin_password ?? '').trim()
    if (password) {
      payload.peertube_admin_password = password
    }

    return payload
  }

  const loadSettings = useCallback(async () => {
    if (!token) {
      return
    }

    setIsLoading(true)
    setError('')

    try {
      const [settings, publication] = await Promise.all([
        getMediaSettings({ token }),
        listContentPublicationSettings({ token }),
      ])
      setForm(formFromSettings(settings))
      setPublicationSettings(publication)
    } catch (loadError) {
      setError(loadError?.message || t('admin.settings.load_error'))
    } finally {
      setIsLoading(false)
    }
  }, [token, t])

  useEffect(() => {
    loadSettings()
  }, [loadSettings])

  const onFieldChange = (event) => {
    const { name, value, type, checked } = event.target
    setForm((current) => ({
      ...current,
      [name]: type === 'checkbox' ? checked : value,
    }))
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    if (!token) {
      return
    }

    setIsSaving(true)
    setError('')
    setMessage('')

    try {
      const settings = await updateMediaSettings(buildPayload(), { token })
      setForm(formFromSettings(settings))
      setMessage(t('admin.settings.saved'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.settings.save_error'))
    } finally {
      setIsSaving(false)
    }
  }

  const onPublicationFlowChange = (settingId, publicationFlow) => {
    setPublicationSettings((current) =>
      current.map((setting) => (
        setting.id === settingId
          ? { ...setting, publication_flow: publicationFlow }
          : setting
      )),
    )
  }

  const handleSaveModeration = async (event) => {
    event.preventDefault()
    if (!token) {
      return
    }

    setIsSavingModeration(true)
    setError('')
    setMessage('')

    try {
      const nextSettings = await Promise.all(
        publicationSettings.map((setting) =>
          updateContentPublicationSetting(
            setting.id,
            {
              publication_flow: setting.publication_flow,
              is_active: true,
            },
            { token },
          ),
        ),
      )
      setPublicationSettings(nextSettings.filter(Boolean))
      setMessage(t('admin.settings.moderation_saved'))
    } catch (saveError) {
      setError(saveError?.message || t('admin.settings.moderation_save_error'))
    } finally {
      setIsSavingModeration(false)
    }
  }

  const contentLabel = (contentType) => {
    if (contentType === 'article') {
      return t('admin.settings.content_article')
    }
    if (contentType === 'event') {
      return t('admin.settings.content_event')
    }
    return t('admin.settings.content_poem')
  }

  const handleTestConnection = async () => {
    if (!token) {
      return
    }

    setIsTesting(true)
    setError('')
    setMessage('')

    try {
      const settings = await updateMediaSettings(buildPayload(), { token })
      setForm(formFromSettings(settings))
      const connected = await testPeerTubeConnection({ token })
      setMessage(connected ? t('admin.settings.test_ok') : t('admin.settings.test_fail'))
    } catch (testError) {
      setError(testError?.message || t('admin.settings.test_error'))
    } finally {
      setIsTesting(false)
    }
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <header className={styles.header}>
          <div>
            <p className={styles.back}>
              <Link to="/admin">{t('admin.hub.back')}</Link>
            </p>
            <h1 className={styles.title}>{t('admin.settings.title')}</h1>
            <p className={styles.subtitle}>{t('admin.settings.subtitle')}</p>
          </div>
        </header>

        {isLoading ? <p className={styles.status}>{t('admin.settings.loading')}</p> : null}
        {message ? <p className={styles.message}>{message}</p> : null}
        {error ? <p className={styles.error}>{error}</p> : null}

        <form className={styles.form} onSubmit={handleSaveModeration}>
          <section className={styles.card}>
            <h2>{t('admin.settings.moderation_title')}</h2>
            <p className={styles.help}>{t('admin.settings.moderation_help')}</p>

            {publicationSettings.length === 0 && !isLoading ? (
              <p className={styles.status}>{t('admin.settings.moderation_empty')}</p>
            ) : (
              <div className={styles.flowList}>
                {CONTENT_TYPES.map((contentType) => {
                  const setting = publicationSettings.find((item) => item.content_type === contentType)
                  if (!setting) {
                    return null
                  }

                  return (
                    <label key={setting.id} className={styles.flowRow}>
                      <span className={styles.flowLabel}>{contentLabel(contentType)}</span>
                      <select
                        value={setting.publication_flow}
                        onChange={(event) => onPublicationFlowChange(setting.id, event.target.value)}
                        disabled={isLoading || isSavingModeration}
                      >
                        <option value="auto_publish">{t('admin.settings.flow_auto_publish')}</option>
                        <option value="manual_review">{t('admin.settings.flow_manual_review')}</option>
                      </select>
                      <small>
                        {setting.publication_flow === 'manual_review'
                          ? t('admin.settings.flow_manual_review_help')
                          : t('admin.settings.flow_auto_publish_help')}
                      </small>
                    </label>
                  )
                })}
              </div>
            )}

            <div className={styles.cardActions}>
              <button type="submit" disabled={isLoading || isSavingModeration || publicationSettings.length === 0}>
                {isSavingModeration ? t('admin.settings.saving') : t('admin.settings.moderation_save')}
              </button>
            </div>
          </section>
        </form>

        <form className={styles.form} onSubmit={handleSubmit}>
          <section className={styles.card}>
            <h2>{t('admin.settings.peertube_title')}</h2>
            <p className={styles.help}>{t('admin.settings.peertube_help')}</p>

            <div className={styles.grid}>
              <label className={`${styles.field} ${styles.full}`}>
                <span>{t('admin.settings.base_url')}</span>
                <input
                  name="peertube_base_url"
                  type="url"
                  value={form.peertube_base_url}
                  onChange={onFieldChange}
                  placeholder="https://video.example.com"
                  disabled={isLoading || isSaving}
                />
              </label>

              <label className={styles.field}>
                <span>{t('admin.settings.admin_username')}</span>
                <input
                  name="peertube_admin_username"
                  value={form.peertube_admin_username}
                  onChange={onFieldChange}
                  autoComplete="off"
                  disabled={isLoading || isSaving}
                />
              </label>

              <label className={styles.field}>
                <span>{t('admin.settings.admin_password')}</span>
                <input
                  name="peertube_admin_password"
                  type="password"
                  value={form.peertube_admin_password}
                  onChange={onFieldChange}
                  autoComplete="new-password"
                  placeholder={t('admin.settings.password_placeholder')}
                  disabled={isLoading || isSaving}
                />
                {form.password_set ? (
                  <small>{t('admin.settings.password_set')}</small>
                ) : null}
              </label>

              <label className={styles.field}>
                <span>{t('admin.settings.channel_id')}</span>
                <input
                  name="peertube_upload_channel_id"
                  value={form.peertube_upload_channel_id}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
              </label>

              <label className={styles.field}>
                <span>{t('admin.settings.default_privacy')}</span>
                <select
                  name="peertube_default_privacy"
                  value={form.peertube_default_privacy}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                >
                  <option value="1">{t('admin.settings.privacy_public')}</option>
                  <option value="2">{t('admin.settings.privacy_unlisted')}</option>
                  <option value="3">{t('admin.settings.privacy_private')}</option>
                </select>
              </label>

              <label className={styles.check}>
                <input
                  type="checkbox"
                  name="peertube_verify_ssl"
                  checked={form.peertube_verify_ssl}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
                <span>{t('admin.settings.verify_ssl')}</span>
              </label>

              <label className={styles.check}>
                <input
                  type="checkbox"
                  name="peertube_download_enabled"
                  checked={form.peertube_download_enabled}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
                <span>{t('admin.settings.download_enabled')}</span>
              </label>
            </div>

            <div className={styles.cardActions}>
              <button
                type="button"
                className={styles.secondary}
                onClick={handleTestConnection}
                disabled={isLoading || isSaving || isTesting}
              >
                {isTesting ? t('admin.settings.testing_connection') : t('admin.settings.test_connection')}
              </button>
            </div>
          </section>

          <section className={styles.card}>
            <h2>{t('admin.settings.limits_title')}</h2>
            <div className={styles.grid}>
              <label className={styles.field}>
                <span>{t('admin.settings.photo_limit')}</span>
                <input
                  name="photo_limit"
                  type="number"
                  min="0"
                  value={form.photo_limit}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
              </label>

              <label className={styles.field}>
                <span>{t('admin.settings.video_limit')}</span>
                <input
                  name="video_limit"
                  type="number"
                  min="0"
                  value={form.video_limit}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
              </label>

              <label className={styles.field}>
                <span>{t('admin.settings.photo_max_size')}</span>
                <input
                  name="photo_max_size_kb"
                  type="number"
                  min="1"
                  value={form.photo_max_size_kb}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
              </label>

              <label className={styles.field}>
                <span>{t('admin.settings.video_max_size')}</span>
                <input
                  name="video_max_size_kb"
                  type="number"
                  min="1"
                  value={form.video_max_size_kb}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
              </label>

              <label className={`${styles.field} ${styles.full}`}>
                <span>{t('admin.settings.photo_mimes')}</span>
                <input
                  name="photo_allowed_mimes"
                  value={form.photo_allowed_mimes}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
                <small>{t('admin.settings.mimes_help')}</small>
              </label>

              <label className={`${styles.field} ${styles.full}`}>
                <span>{t('admin.settings.video_mimes')}</span>
                <input
                  name="video_allowed_mimes"
                  value={form.video_allowed_mimes}
                  onChange={onFieldChange}
                  disabled={isLoading || isSaving}
                />
                <small>{t('admin.settings.mimes_help')}</small>
              </label>
            </div>
          </section>

          <div className={styles.actions}>
            <button type="submit" disabled={isLoading || isSaving}>
              {isSaving ? t('admin.settings.saving') : t('admin.settings.save')}
            </button>
          </div>
        </form>
      </section>
    </MainLayout>
  )
}
