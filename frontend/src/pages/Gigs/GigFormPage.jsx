import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './GigWorkspace.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { listMyEvents } from '../../app/api/events.js'
import { listMyPoems } from '../../app/api/poems.js'
import {
  createEventGig,
  createPoemTranslationGig,
  deleteEventGig,
  deletePoemTranslationGig,
  getGig,
  updateEventGig,
  updatePoemTranslationGig,
} from '../../app/api/gigs.js'

const EVENT_GIG_TYPES = ['poet_artist', 'mc_host', 'technical_support', 'volunteer']
const EDITABLE_STATUSES = ['draft', 'open', 'closed', 'cancelled']
const CURRENCIES = ['EUR', 'USD', 'GBP', 'CHF']

const emptyValues = {
  parentId: '',
  type: 'poet_artist',
  quantity: '1',
  status: 'open',
  language: '',
  description: '',
  special_requirements: '',
  cachet_enabled: false,
  proposed_price: '',
  currency: 'EUR',
  travel_expenses_enabled: false,
  travel_expenses_max: '',
  accommodation_enabled: false,
  accommodation_details: '',
  deadline: '',
}

function toDateInput(value) {
  return value ? String(value).slice(0, 10) : ''
}

function valuesFromGig(gig) {
  return {
    ...emptyValues,
    parentId: String(gig.giggable_id ?? ''),
    type: gig.type ?? emptyValues.type,
    quantity: String(gig.quantity ?? 1),
    status: gig.status === 'filled' && gig.parent?.type === 'event' ? 'open' : (gig.status ?? 'open'),
    language: gig.language ?? '',
    description: gig.description ?? '',
    special_requirements: gig.special_requirements ?? '',
    cachet_enabled: Boolean(gig.cachet_enabled),
    proposed_price: gig.proposed_price ?? '',
    currency: gig.currency || 'EUR',
    travel_expenses_enabled: Boolean(gig.travel_expenses_enabled),
    travel_expenses_max: gig.travel_expenses_max ?? '',
    accommodation_enabled: Boolean(gig.accommodation_enabled),
    accommodation_details: gig.accommodation_details ?? '',
    deadline: toDateInput(gig.deadline),
  }
}

const nullable = (value) => {
  const text = String(value ?? '').trim()
  return text === '' ? null : text
}

function eventPayload(values) {
  return {
    type: values.type,
    quantity: Math.max(1, Number.parseInt(values.quantity, 10) || 1),
    status: values.status,
    language: nullable(values.language),
    description: nullable(values.description),
    special_requirements: nullable(values.special_requirements),
    cachet_enabled: values.cachet_enabled,
    proposed_price: values.cachet_enabled ? nullable(values.proposed_price) : null,
    currency: values.currency,
    travel_expenses_enabled: values.travel_expenses_enabled,
    travel_expenses_max: values.travel_expenses_enabled ? nullable(values.travel_expenses_max) : null,
    accommodation_enabled: values.accommodation_enabled,
    hospitality_required: values.accommodation_enabled,
    accommodation_details: values.accommodation_enabled ? nullable(values.accommodation_details) : null,
    deadline: nullable(values.deadline),
  }
}

function poemPayload(values) {
  return {
    target_language: values.language.trim(),
    proposed_price: values.proposed_price,
    currency: values.currency,
    deadline: nullable(values.deadline),
    special_requirements: nullable(values.special_requirements),
    status: values.status,
  }
}

export default function GigFormPage() {
  const { gigId } = useParams()
  const isEdit = Boolean(gigId)
  const navigate = useNavigate()
  const { t } = useI18n()
  const { token } = useAuth()
  const [source, setSource] = useState('event')
  const [values, setValues] = useState(emptyValues)
  const [editingGig, setEditingGig] = useState(null)
  const [parents, setParents] = useState({ event: null, poem: null })
  const [isLoading, setIsLoading] = useState(isEdit)
  const [loadError, setLoadError] = useState('')
  const [error, setError] = useState('')
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    if (!isEdit) {
      return undefined
    }
    const controller = new AbortController()
    getGig(gigId, { token, signal: controller.signal })
      .then((gig) => {
        if (!gig?.can_manage) {
          setLoadError(t('gigs.form.forbidden'))
          return
        }
        setEditingGig(gig)
        setSource(gig.parent?.type === 'poem' ? 'poem' : 'event')
        setValues(valuesFromGig(gig))
      })
      .catch((err) => {
        if (err?.name !== 'AbortError') {
          setLoadError(err.message)
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      })
    return () => controller.abort()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [gigId, isEdit, token])

  useEffect(() => {
    if (isEdit || parents[source] !== null) {
      return undefined
    }
    const controller = new AbortController()
    const request = source === 'event'
      ? listMyEvents({ token, signal: controller.signal })
      : listMyPoems({ perPage: 50 }, { token, signal: controller.signal }).then((result) => result.items)
    request
      .then((items) => setParents((prev) => ({ ...prev, [source]: Array.isArray(items) ? items : [] })))
      .catch((err) => {
        if (err?.name !== 'AbortError') {
          setParents((prev) => ({ ...prev, [source]: [] }))
          setError(err.message)
        }
      })
    return () => controller.abort()
  }, [isEdit, parents, source, token])

  const setField = (event) => {
    const { name, type, value, checked } = event.target
    setValues((prev) => ({ ...prev, [name]: type === 'checkbox' ? checked : value }))
  }

  function changeSource(next) {
    setSource(next)
    setError('')
    setValues((prev) => ({
      ...prev,
      parentId: '',
      type: next === 'poem' ? 'translator' : 'poet_artist',
      language: '',
    }))
  }

  async function handleSubmit(event) {
    event.preventDefault()
    const parentId = isEdit ? editingGig.giggable_id : values.parentId
    if (!parentId) {
      return
    }
    setIsSaving(true)
    setError('')
    try {
      let saved
      if (source === 'poem') {
        saved = isEdit
          ? await updatePoemTranslationGig(parentId, gigId, poemPayload(values), { token })
          : await createPoemTranslationGig(parentId, poemPayload(values), { token })
      } else {
        saved = isEdit
          ? await updateEventGig(parentId, gigId, eventPayload(values), { token })
          : await createEventGig(parentId, eventPayload(values), { token })
      }
      navigate(`/gigs/${saved?.id ?? gigId}`, { replace: isEdit })
    } catch (err) {
      setError(err.message)
      setIsSaving(false)
    }
  }

  async function handleDelete() {
    if (!editingGig || !window.confirm(t('gigs.common.confirm_delete'))) {
      return
    }
    setIsSaving(true)
    setError('')
    try {
      if (source === 'poem') {
        await deletePoemTranslationGig(editingGig.giggable_id, editingGig.id, { token })
      } else {
        await deleteEventGig(editingGig.giggable_id, editingGig.id, { token })
      }
      navigate('/gigs/mine?tab=managed', { replace: true })
    } catch (err) {
      setError(err.message)
      setIsSaving(false)
    }
  }

  if (isLoading || loadError) {
    return (
      <MainLayout themeTone="gigs">
        <div className={styles.page}>
          <Link to="/gigs" className={styles.backLink}>{t('gigs.common.back_to_list')}</Link>
          <p className={styles.state} role={loadError ? 'alert' : undefined}>
            {loadError || t('gigs.common.loading')}
          </p>
        </div>
      </MainLayout>
    )
  }

  const parentOptions = parents[source]
  const statusOptions = source === 'poem' && isEdit ? [...EDITABLE_STATUSES.slice(0, 2), 'filled', ...EDITABLE_STATUSES.slice(2)] : EDITABLE_STATUSES
  const isPoem = source === 'poem'

  return (
    <MainLayout themeTone="gigs">
      <div className={styles.page}>
        <Link to={isEdit ? `/gigs/${gigId}` : '/gigs'} className={styles.backLink}>
          {isEdit ? t('gigs.common.back_to_gig') : t('gigs.common.back_to_list')}
        </Link>
        <h1 className={styles.pageTitle}>{isEdit ? t('gigs.form.edit_title') : t('gigs.form.create_title')}</h1>
        {!isEdit ? <p className={styles.lead}>{t('gigs.form.lead')}</p> : null}

        <form className={styles.panel} onSubmit={handleSubmit}>
          <header className={styles.panelHead}>
            <h2 className={styles.panelTitle}>
              {isEdit ? (editingGig?.parent?.title ?? t('gigs.card.untitled')) : t('gigs.form.source')}
            </h2>
          </header>
          <div className={[styles.panelBody, styles.form].join(' ')}>
            {error ? <p className={styles.error} role="alert">{error}</p> : null}

            {!isEdit ? (
              <>
                <div className={styles.segmented} role="radiogroup" aria-label={t('gigs.form.source')}>
                  {['event', 'poem'].map((value) => (
                    <label key={value}>
                      <input
                        type="radio"
                        name="source"
                        value={value}
                        checked={source === value}
                        onChange={() => changeSource(value)}
                      />
                      {t(`gigs.form.source_${value}`)}
                    </label>
                  ))}
                </div>

                {parentOptions === null ? <p className={styles.muted}>{t('gigs.common.loading')}</p> : null}
                {parentOptions?.length === 0 ? (
                  <p className={styles.muted}>{isPoem ? t('gigs.form.no_poems') : t('gigs.form.no_events')}</p>
                ) : null}
                {parentOptions?.length > 0 ? (
                  <label className={styles.field}>
                    {isPoem ? t('gigs.form.poem') : t('gigs.form.event')}
                    <select className={styles.select} name="parentId" value={values.parentId} onChange={setField} required>
                      <option value="">{isPoem ? t('gigs.form.poem_placeholder') : t('gigs.form.event_placeholder')}</option>
                      {parentOptions.map((item) => (
                        <option key={item.id} value={item.id}>{item.title || `#${item.id}`}</option>
                      ))}
                    </select>
                  </label>
                ) : null}
              </>
            ) : null}

            <div className={styles.fieldGrid}>
              {!isPoem ? (
                <>
                  <label className={styles.field}>
                    {t('gigs.form.type')}
                    <select className={styles.select} name="type" value={values.type} onChange={setField}>
                      {EVENT_GIG_TYPES.map((value) => (
                        <option key={value} value={value}>{t(`gigs.types.${value}`)}</option>
                      ))}
                    </select>
                  </label>
                  <label className={styles.field}>
                    {t('gigs.form.quantity')}
                    <input className={styles.input} type="number" min="1" max="999" name="quantity" value={values.quantity} onChange={setField} required />
                  </label>
                </>
              ) : null}
              <label className={styles.field}>
                {isPoem ? t('gigs.form.target_language') : t('gigs.form.language')}
                <input
                  className={styles.input}
                  name="language"
                  value={values.language}
                  onChange={setField}
                  maxLength={255}
                  required={isPoem}
                />
              </label>
              <label className={styles.field}>
                {t('gigs.form.status')}
                <select className={styles.select} name="status" value={values.status} onChange={setField}>
                  {statusOptions.map((value) => (
                    <option key={value} value={value}>{t(`gigs.statuses.${value}`)}</option>
                  ))}
                </select>
              </label>
              <label className={styles.field}>
                {t('gigs.form.deadline')}
                <input className={styles.input} type="date" name="deadline" value={values.deadline} onChange={setField} />
              </label>
              {isPoem ? (
                <>
                  <label className={styles.field}>
                    {t('gigs.form.proposed_price')}
                    <input className={styles.input} type="number" min="0" step="0.01" name="proposed_price" value={values.proposed_price} onChange={setField} required />
                  </label>
                  <label className={styles.field}>
                    {t('gigs.form.currency')}
                    <select className={styles.select} name="currency" value={values.currency} onChange={setField}>
                      {CURRENCIES.map((value) => <option key={value} value={value}>{value}</option>)}
                    </select>
                  </label>
                </>
              ) : null}
            </div>

            {!isPoem ? (
              <>
                <label className={styles.field}>
                  {t('gigs.form.description')}
                  <textarea
                    className={styles.textarea}
                    name="description"
                    value={values.description}
                    onChange={setField}
                    placeholder={t('gigs.form.description_placeholder')}
                  />
                </label>

                <div className={styles.optionBox}>
                  <label className={styles.checkRow}>
                    <input type="checkbox" name="cachet_enabled" checked={values.cachet_enabled} onChange={setField} />
                    {t('gigs.form.cachet_enabled')}
                  </label>
                  {values.cachet_enabled ? (
                    <div className={styles.fieldGrid}>
                      <label className={styles.field}>
                        {t('gigs.form.amount')}
                        <input className={styles.input} type="number" min="0" step="0.01" name="proposed_price" value={values.proposed_price} onChange={setField} />
                      </label>
                      <label className={styles.field}>
                        {t('gigs.form.currency')}
                        <select className={styles.select} name="currency" value={values.currency} onChange={setField}>
                          {CURRENCIES.map((value) => <option key={value} value={value}>{value}</option>)}
                        </select>
                      </label>
                    </div>
                  ) : null}
                </div>

                <div className={styles.optionBox}>
                  <label className={styles.checkRow}>
                    <input type="checkbox" name="travel_expenses_enabled" checked={values.travel_expenses_enabled} onChange={setField} />
                    {t('gigs.form.travel_enabled')}
                  </label>
                  {values.travel_expenses_enabled ? (
                    <label className={styles.field}>
                      {t('gigs.form.travel_max')}
                      <input className={styles.input} type="number" min="0" step="0.01" name="travel_expenses_max" value={values.travel_expenses_max} onChange={setField} />
                    </label>
                  ) : null}
                </div>

                <div className={styles.optionBox}>
                  <label className={styles.checkRow}>
                    <input type="checkbox" name="accommodation_enabled" checked={values.accommodation_enabled} onChange={setField} />
                    {t('gigs.form.accommodation_enabled')}
                  </label>
                  {values.accommodation_enabled ? (
                    <label className={styles.field}>
                      {t('gigs.form.accommodation_details')}
                      <textarea className={styles.textarea} name="accommodation_details" value={values.accommodation_details} onChange={setField} />
                    </label>
                  ) : null}
                </div>
              </>
            ) : null}

            <label className={styles.field}>
              {t('gigs.form.requirements')}
              <textarea className={styles.textarea} name="special_requirements" value={values.special_requirements} onChange={setField} />
            </label>

            <div className={styles.actions}>
              <button
                type="submit"
                className={[styles.btn, styles.btnDark].join(' ')}
                disabled={isSaving || (!isEdit && !values.parentId)}
              >
                {isSaving ? t('gigs.common.saving') : (isEdit ? t('gigs.form.submit_edit') : t('gigs.form.submit_create'))}
              </button>
              {isEdit ? (
                <button type="button" className={[styles.btn, styles.btnDanger].join(' ')} disabled={isSaving} onClick={handleDelete}>
                  {t('gigs.form.delete')}
                </button>
              ) : null}
            </div>
          </div>
        </form>
      </div>
    </MainLayout>
  )
}
