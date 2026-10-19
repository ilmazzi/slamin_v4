import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { createGroup, getGroup, listGroupCategories, updateGroup } from '../../app/api/groups.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './GroupWorkspace.module.css'

const emptyForm = {
  name: '',
  description: '',
  group_category_id: '',
  visibility: 'public',
  is_listed: true,
  auto_accept_events: false,
  chat_audiences: [],
  image: null,
  website_url: '',
  facebook_url: '',
  instagram_url: '',
  youtube_url: '',
}

function revokeIfBlob(url) {
  if (url && url.startsWith('blob:')) {
    URL.revokeObjectURL(url)
  }
}

function formFromGroup(group) {
  return {
    name: group.name ?? '',
    description: group.description ?? '',
    group_category_id: group.group_category_id ? String(group.group_category_id) : '',
    visibility: group.visibility ?? 'public',
    is_listed: group.visibility === 'public' ? true : Boolean(group.is_listed),
    auto_accept_events: Boolean(group.auto_accept_events),
    chat_audiences: (group.chat_threads || []).filter((thread) => thread.is_enabled).map((thread) => thread.audience),
    image: null,
    website_url: group.website_url ?? '',
    facebook_url: group.facebook_url ?? '',
    instagram_url: group.instagram_url ?? '',
    youtube_url: group.youtube_url ?? '',
  }
}

export default function GroupCreatePage() {
  const { slug } = useParams()
  const isEdit = Boolean(slug)
  const { t } = useI18n()
  const { token } = useAuth()
  const navigate = useNavigate()
  const [categories, setCategories] = useState([])
  const [form, setForm] = useState(emptyForm)
  const [previewUrl, setPreviewUrl] = useState('')
  const [removeImage, setRemoveImage] = useState(false)
  const [fileInputKey, setFileInputKey] = useState(0)
  const [isLoading, setIsLoading] = useState(isEdit)
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError] = useState('')
  const [forbidden, setForbidden] = useState(false)
  const backHref = isEdit ? `/groups/${slug}` : '/groups'

  useEffect(() => {
    if (!token) return undefined
    const controller = new AbortController()
    listGroupCategories({ token, signal: controller.signal })
      .then(setCategories)
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('group.ui.load_error'))
        }
      })
    return () => controller.abort()
  }, [t, token])

  useEffect(() => {
    if (!token || !isEdit || !slug) return undefined
    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    setForbidden(false)
    getGroup(slug, { token, signal: controller.signal })
      .then((group) => {
        if (!group?.permissions?.update) {
          setForbidden(true)
          return
        }
        setForm(formFromGroup(group))
        setRemoveImage(false)
        setPreviewUrl(group.image_url ? resolvePublicUrl(group.image_url) : '')
      })
      .catch((loadError) => {
        if (loadError?.name !== 'AbortError') {
          setError(loadError?.message || t('group.ui.show_error'))
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) setIsLoading(false)
      })
    return () => controller.abort()
  }, [isEdit, slug, t, token])

  useEffect(() => () => revokeIfBlob(previewUrl), [previewUrl])

  const update = (field) => (event) => {
    const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value
    setForm((current) => ({ ...current, [field]: value }))
  }

  const updateImage = (event) => {
    const file = event.target.files?.[0] ?? null
    setForm((current) => ({ ...current, image: file }))
    setRemoveImage(false)
    setPreviewUrl((current) => {
      revokeIfBlob(current)
      return file ? URL.createObjectURL(file) : ''
    })
  }

  const clearImage = () => {
    setForm((current) => ({ ...current, image: null }))
    setRemoveImage(true)
    setFileInputKey((current) => current + 1)
    setPreviewUrl((current) => {
      revokeIfBlob(current)
      return ''
    })
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    if (!form.name.trim() || !form.group_category_id) {
      setError(t(isEdit ? 'group.ui.update_error' : 'group.ui.create_error'))
      return
    }

    setIsSaving(true)
    setError('')
    const payload = {
      name: form.name.trim(),
      description: form.description.trim() || null,
      group_category_id: Number(form.group_category_id),
      visibility: form.visibility,
      is_listed: form.visibility === 'public' ? true : Boolean(form.is_listed),
      auto_accept_events: Boolean(form.auto_accept_events),
      chat_audiences: isEdit ? undefined : form.chat_audiences,
      image: form.image,
      remove_image: isEdit && removeImage && !form.image ? true : undefined,
      website_url: form.website_url.trim() || null,
      facebook_url: form.facebook_url.trim() || null,
      instagram_url: form.instagram_url.trim() || null,
      youtube_url: form.youtube_url.trim() || null,
    }

    try {
      const group = isEdit
        ? await updateGroup(slug, payload, { token })
        : await createGroup(payload, { token })
      navigate(`/groups/${group.slug}`)
    } catch (saveError) {
      setError(saveError?.message || t(isEdit ? 'group.ui.update_error' : 'group.ui.create_error'))
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <MainLayout themeTone="groups">
      <div className={styles.page}>
        <Link to={backHref} className={styles.backLink}>
          {isEdit ? t('group.ui.back_to_group') : t('group.ui.back')}
        </Link>
        <h1 className={styles.pageTitle}>
          {t(isEdit ? 'group.ui.edit_lead_prefix' : 'group.ui.create_lead_prefix')}{' '}
          <em>{t(isEdit ? 'group.ui.edit_lead_emphasis' : 'group.ui.create_lead_emphasis')}</em>
        </h1>
        <p className={styles.lead}>{t(isEdit ? 'group.ui.edit_lead' : 'group.ui.create_lead')}</p>

        {isLoading ? (
          <p className={styles.state}>{t('group.ui.loading')}</p>
        ) : forbidden ? (
          <p className={styles.state} role="alert">{t('group.ui.update_forbidden')}</p>
        ) : (
          <form className={styles.panel} onSubmit={handleSubmit}>
            <header className={styles.panelHead}>
              <h2 className={styles.panelTitle}>
                {t(isEdit ? 'group.ui.edit_title' : 'group.ui.create_title')}
              </h2>
            </header>
            <div className={[styles.panelBody, styles.form].join(' ')}>
              {error ? <p className={styles.error} role="alert">{error}</p> : null}

              <div className={styles.fieldGrid}>
                <label className={`${styles.field} ${styles.full}`}>
                  {t('group.ui.name')}
                  <input className={styles.input} value={form.name} onChange={update('name')} required maxLength={255} />
                </label>

                <label className={styles.field}>
                  {t('group.ui.category')}
                  <select className={styles.select} value={form.group_category_id} onChange={update('group_category_id')} required>
                    <option value="">{t('group.ui.category_placeholder')}</option>
                    {categories.map((category) => (
                      <option key={category.id} value={category.id}>{category.name}</option>
                    ))}
                  </select>
                </label>

                <label className={`${styles.field} ${styles.full}`}>
                  {t('group.ui.description')}
                  <textarea className={styles.textarea} value={form.description} onChange={update('description')} rows={5} />
                </label>

                <label className={`${styles.field} ${styles.full}`}>
                  {t('group.ui.image')}
                  <input
                    key={fileInputKey}
                    className={styles.fileInput}
                    type="file"
                    accept="image/*"
                    onChange={updateImage}
                  />
                  <small className={styles.fieldHint}>{t('group.ui.image_help')}</small>
                </label>
                {previewUrl ? (
                  <div className={`${styles.previewWrap} ${styles.full}`}>
                    <img src={previewUrl} alt="" className={styles.cover} />
                    <button type="button" className={styles.clearImage} onClick={clearImage}>
                      {t('group.ui.image_remove')}
                    </button>
                  </div>
                ) : null}
              </div>

              <div className={styles.segmented} role="radiogroup" aria-label={t('group.ui.visibility_label')}>
                {['public', 'private'].map((value) => (
                  <label key={value}>
                    <input
                      type="radio"
                      name="visibility"
                      value={value}
                      checked={form.visibility === value}
                      onChange={update('visibility')}
                    />
                    {t(`group.visibility.${value}`)}
                  </label>
                ))}
              </div>

              {form.visibility === 'private' ? (
                <label className={styles.checkRow}>
                  <input type="checkbox" checked={form.is_listed} onChange={update('is_listed')} />
                  {t('group.ui.listed')}
                  <small>{t('group.ui.listed_help')}</small>
                </label>
              ) : null}

              <label className={styles.checkRow}>
                <input type="checkbox" checked={form.auto_accept_events} onChange={update('auto_accept_events')} />
                {t('group.ui.staff_auto_accept')}
              </label>

              {!isEdit ? (
                <fieldset className={styles.chatFieldset}>
                  <legend>{t('group.ui.chat_title')}</legend>
                  <p className={styles.fieldHint}>{t('group.ui.chat_lead')}</p>
                  {['admin', 'editor', 'affiliates', 'public'].map((audience) => (
                    <label key={audience} className={styles.checkRow}>
                      <input
                        type="checkbox"
                        checked={form.chat_audiences.includes(audience)}
                        onChange={() => {
                          setForm((current) => ({
                            ...current,
                            chat_audiences: current.chat_audiences.includes(audience)
                              ? current.chat_audiences.filter((item) => item !== audience)
                              : [...current.chat_audiences, audience],
                          }))
                        }}
                      />
                      <span>
                        {t(`group.ui.chat_audience_${audience}`)}
                        <small>{t(`group.ui.chat_audience_${audience}_help`)}</small>
                      </span>
                    </label>
                  ))}
                </fieldset>
              ) : null}

              <h3 className={styles.sectionTitle}>{t('group.ui.links')}</h3>
              <div className={styles.fieldGrid}>
                {['website', 'facebook', 'instagram', 'youtube'].map((field) => (
                  <label key={field} className={styles.field}>
                    {t(`group.ui.${field}`)}
                    <input
                      className={styles.input}
                      type="url"
                      value={form[`${field}_url`]}
                      onChange={update(`${field}_url`)}
                    />
                  </label>
                ))}
              </div>

              <div className={styles.actions}>
                <button type="submit" className={styles.btn} disabled={isSaving}>
                  {isSaving
                    ? t(isEdit ? 'group.ui.saving_changes' : 'group.ui.saving')
                    : t(isEdit ? 'group.ui.save_changes' : 'group.ui.save')}
                </button>
              </div>
            </div>
          </form>
        )}
      </div>
    </MainLayout>
  )
}
