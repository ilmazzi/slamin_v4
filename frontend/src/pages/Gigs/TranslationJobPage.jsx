import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import MainLayout from '../../components/layout/MainLayout.jsx'
import AppToast from '../../components/shared/AppToast.jsx'
import { formatGigDeadline, formatGigDateTime, formatGigMoney } from '../../components/gigs/gigFormat.js'
import styles from './GigWorkspace.module.css'
import { useI18n } from '../../app/hooks/useI18n.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import {
  addTranslationComment,
  approveTranslationJob,
  getTranslationJob,
  requestTranslationChanges,
  resolveTranslationComment,
  submitTranslationJob,
  updateTranslationJob,
} from '../../app/api/gigs.js'

const COMMENT_TARGETS = ['general', 'source_text', 'translated_text']
const EDITABLE_STATUSES = ['in_progress', 'changes_requested']

const personName = (person, fallbackId) => person?.display_name || person?.nickname || `#${fallbackId}`

export default function TranslationJobPage() {
  const { jobId } = useParams()
  const { t, locale } = useI18n()
  const { token, user } = useAuth()
  const [job, setJob] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [actionError, setActionError] = useState('')
  const [busy, setBusy] = useState('')
  const [toast, setToast] = useState('')
  const [content, setContent] = useState('')
  const [deadline, setDeadline] = useState('')
  const [comment, setComment] = useState({ comment_target: 'general', target_locator: '', content: '' })
  const clearToast = useCallback(() => setToast(''), [])

  useEffect(() => {
    const controller = new AbortController()
    setIsLoading(true)
    setError('')
    getTranslationJob(jobId, { token, signal: controller.signal })
      .then((data) => {
        setJob(data)
        setContent(data?.translated_content ?? '')
        setDeadline(data?.deadline ? String(data.deadline).slice(0, 10) : '')
      })
      .catch((err) => {
        if (err?.name !== 'AbortError') {
          setError(err.message)
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      })
    return () => controller.abort()
  }, [jobId, token])

  if (isLoading || !job) {
    return (
      <MainLayout themeTone="gigs">
        <div className={styles.page}>
          <Link to="/gigs/mine" className={styles.backLink}>{t('gigs.filters.mine')}</Link>
          <p className={styles.state} role={error ? 'alert' : undefined}>
            {isLoading ? t('gigs.common.loading') : (error || t('gigs.detail.not_found'))}
          </p>
        </div>
      </MainLayout>
    )
  }

  const viewerId = Number(user?.id)
  const isTranslator = viewerId === Number(job.translator_user_id)
  const isOwner = viewerId === Number(job.owner_user_id)
  const canEditContent = isTranslator && EDITABLE_STATUSES.includes(job.status)
  const canEditDeadline = isOwner && !['approved', 'cancelled'].includes(job.status)
  const canReview = isOwner && job.status === 'submitted'
  const canApprove = isOwner && ['submitted', 'changes_requested'].includes(job.status) && Boolean(job.translated_content)
  const comments = job.comments ?? []
  const isDirty = content !== (job.translated_content ?? '')

  const mergeJob = (updated) => {
    if (updated) {
      setJob((prev) => ({ ...prev, ...updated }))
    }
  }

  async function run(key, task, successMessage) {
    setBusy(key)
    setActionError('')
    try {
      await task()
      setToast(successMessage)
    } catch (err) {
      setActionError(err.message)
    } finally {
      setBusy('')
    }
  }

  const saveContent = () => run('save', async () => {
    mergeJob(await updateTranslationJob(job.id, { translated_content: content }, { token }))
  }, t('gigs.job.saved'))

  const submit = () => run('submit', async () => {
    if (isDirty) {
      mergeJob(await updateTranslationJob(job.id, { translated_content: content }, { token }))
    }
    mergeJob(await submitTranslationJob(job.id, { token }))
  }, t('gigs.job.submitted'))

  const saveDeadline = () => run('deadline', async () => {
    mergeJob(await updateTranslationJob(job.id, { deadline: deadline || null }, { token }))
  }, t('gigs.job.saved'))

  const requestChanges = () => run('changes', async () => {
    mergeJob(await requestTranslationChanges(job.id, { token }))
  }, t('gigs.job.changes_requested'))

  const approve = () => run('approve', async () => {
    mergeJob(await approveTranslationJob(job.id, { token }))
  }, t('gigs.job.approved'))

  async function handleComment(event) {
    event.preventDefault()
    const text = comment.content.trim()
    if (!text) {
      return
    }
    await run('comment', async () => {
      const created = await addTranslationComment(job.id, {
        comment_target: comment.comment_target,
        target_locator: comment.target_locator.trim() || null,
        content: text,
      }, { token })
      if (created) {
        setJob((prev) => ({ ...prev, comments: [created, ...(prev.comments ?? [])] }))
      }
      setComment((prev) => ({ ...prev, target_locator: '', content: '' }))
    }, t('gigs.job.comment_added'))
  }

  const resolveComment = (item) => run(`resolve-${item.id}`, async () => {
    const updated = await resolveTranslationComment(job.id, item.id, { token })
    if (updated) {
      setJob((prev) => ({
        ...prev,
        comments: (prev.comments ?? []).map((entry) => (entry.id === updated.id ? { ...entry, ...updated } : entry)),
      }))
    }
  }, t('gigs.job.resolved'))

  return (
    <MainLayout themeTone="gigs">
      <div className={styles.page}>
        {job.gig_negotiation_id ? (
          <Link to={`/gig-negotiations/${job.gig_negotiation_id}`} className={styles.backLink}>{t('gigs.job.back')}</Link>
        ) : null}

        <section className={styles.panel}>
          <header className={styles.panelHead}>
            <h1 className={styles.panelTitle}>
              {t('gigs.job.title')}: {job.poem?.title ?? `#${job.poem_id}`}
            </h1>
            <span className={[styles.badge, styles.badgeAccent].join(' ')}>{t(`gigs.job_statuses.${job.status}`)}</span>
          </header>
          <div className={styles.panelBody}>
            {actionError ? <p className={styles.error} role="alert">{actionError}</p> : null}
            <dl className={styles.facts}>
              <div className={styles.fact}>
                <dt>{t('gigs.detail.source_language')}</dt>
                <dd>{job.source_language || t('gigs.common.not_set')}</dd>
              </div>
              <div className={styles.fact}>
                <dt>{t('gigs.detail.target_language')}</dt>
                <dd>{job.target_language}</dd>
              </div>
              <div className={styles.fact}>
                <dt>{t('gigs.job.agreed_price')}</dt>
                <dd>{formatGigMoney(job.agreed_price, job.currency, locale) || t('gigs.common.not_set')}</dd>
              </div>
              <div className={styles.fact}>
                <dt>{t('gigs.job.deadline')}</dt>
                <dd>{formatGigDeadline(job.deadline, locale) || t('gigs.common.not_set')}</dd>
              </div>
              <div className={styles.fact}>
                <dt>{t('gigs.negotiation.title')}</dt>
                <dd>
                  {t('gigs.negotiation.between', {
                    owner: personName(job.owner, job.owner_user_id),
                    translator: personName(job.translator, job.translator_user_id),
                  })}
                </dd>
              </div>
            </dl>

            {canEditDeadline ? (
              <div className={styles.actions}>
                <input
                  className={[styles.input, styles.inlineSelect].join(' ')}
                  type="date"
                  value={deadline}
                  onChange={(event) => setDeadline(event.target.value)}
                  aria-label={t('gigs.job.deadline')}
                />
                <button type="button" className={styles.btn} disabled={busy !== ''} onClick={saveDeadline}>
                  {t('gigs.job.save_deadline')}
                </button>
              </div>
            ) : null}
          </div>
        </section>

        <div className={styles.cardGrid}>
          <section className={styles.panel}>
            <header className={styles.panelHead}>
              <h2 className={styles.panelTitle}>{t('gigs.job.source')}</h2>
            </header>
            <div className={styles.panelBody}>
              <pre className={styles.sourceText}>{job.poem?.content ?? ''}</pre>
            </div>
          </section>

          <section className={styles.panel}>
            <header className={styles.panelHead}>
              <h2 className={styles.panelTitle}>{t('gigs.job.translation')}</h2>
            </header>
            <div className={styles.panelBody}>
              {canEditContent ? (
                <>
                  <textarea
                    className={[styles.textarea, styles.textareaTall].join(' ')}
                    value={content}
                    onChange={(event) => setContent(event.target.value)}
                    placeholder={t('gigs.job.translation_placeholder')}
                    aria-label={t('gigs.job.translation')}
                  />
                  <div className={styles.actions}>
                    <button type="button" className={styles.btn} disabled={busy !== '' || !isDirty} onClick={saveContent}>
                      {t('gigs.job.save')}
                    </button>
                    <button type="button" className={[styles.btn, styles.btnDark].join(' ')} disabled={busy !== '' || !content.trim()} onClick={submit}>
                      {t('gigs.job.submit')}
                    </button>
                  </div>
                </>
              ) : job.translated_content ? (
                <pre className={styles.sourceText}>{job.translated_content}</pre>
              ) : (
                <p className={styles.muted}>{t('gigs.job.empty_translation')}</p>
              )}

              {canReview || canApprove ? (
                <div className={styles.actions}>
                  {canReview ? (
                    <button type="button" className={styles.btn} disabled={busy !== ''} onClick={requestChanges}>
                      {t('gigs.job.request_changes')}
                    </button>
                  ) : null}
                  {canApprove ? (
                    <button type="button" className={[styles.btn, styles.btnDark].join(' ')} disabled={busy !== ''} onClick={approve}>
                      {t('gigs.job.approve')}
                    </button>
                  ) : null}
                </div>
              ) : null}
            </div>
          </section>
        </div>

        <section className={styles.panel}>
          <header className={styles.panelHead}>
            <h2 className={styles.panelTitle}>{t('gigs.job.comments')}</h2>
            <span className={styles.panelMeta}>{comments.length}</span>
          </header>
          <div className={styles.panelBody}>
            {job.status !== 'cancelled' ? (
              <form className={styles.form} onSubmit={handleComment}>
                <div className={styles.fieldGrid}>
                  <label className={styles.field}>
                    {t('gigs.job.comment_target')}
                    <select
                      className={styles.select}
                      value={comment.comment_target}
                      onChange={(event) => setComment((prev) => ({ ...prev, comment_target: event.target.value }))}
                    >
                      {COMMENT_TARGETS.map((value) => (
                        <option key={value} value={value}>{t(`gigs.comment_targets.${value}`)}</option>
                      ))}
                    </select>
                  </label>
                  <label className={styles.field}>
                    {t('gigs.job.comment_locator')}
                    <input
                      className={styles.input}
                      value={comment.target_locator}
                      onChange={(event) => setComment((prev) => ({ ...prev, target_locator: event.target.value }))}
                      maxLength={255}
                    />
                  </label>
                </div>
                <textarea
                  className={styles.textarea}
                  value={comment.content}
                  onChange={(event) => setComment((prev) => ({ ...prev, content: event.target.value }))}
                  placeholder={t('gigs.job.comment_placeholder')}
                  aria-label={t('gigs.job.comment_placeholder')}
                  required
                />
                <div className={styles.actions}>
                  <button type="submit" className={[styles.btn, styles.btnDark].join(' ')} disabled={busy !== '' || !comment.content.trim()}>
                    {t('gigs.job.add_comment')}
                  </button>
                </div>
              </form>
            ) : null}

            {comments.length === 0 ? <p className={styles.muted}>{t('gigs.job.comments_empty')}</p> : (
              <ul className={styles.list}>
                {comments.map((item) => (
                  <li key={item.id} className={styles.listItem}>
                    <div className={styles.listItemHead}>
                      <strong>{personName(item.author, item.author_user_id)}</strong>
                      <span className={styles.muted}>
                        {t(`gigs.comment_targets.${item.comment_target}`)}
                        {item.target_locator ? ` · ${item.target_locator}` : ''}
                        {' · '}
                        {formatGigDateTime(item.created_at, locale)}
                      </span>
                    </div>
                    <p className={styles.text}>{item.content}</p>
                    <div className={styles.actions}>
                      {item.status === 'resolved' ? (
                        <span className={styles.badge}>{t('gigs.job.resolved')}</span>
                      ) : (
                        <button
                          type="button"
                          className={styles.btn}
                          disabled={busy !== ''}
                          onClick={() => resolveComment(item)}
                        >
                          {t('gigs.job.resolve')}
                        </button>
                      )}
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </section>
      </div>

      <AppToast message={toast} isVisible={Boolean(toast)} onClose={clearToast} />
    </MainLayout>
  )
}
