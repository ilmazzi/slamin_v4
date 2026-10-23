import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { createForumPost, getForumCategory, listForumCategories } from '../../app/api/forum.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import MainLayout from '../../components/layout/MainLayout.jsx'
import styles from './ForumPostForm.module.css'

export default function ForumPostCreatePage() {
  const { categorySlug } = useParams()
  const navigate = useNavigate()
  const { t } = useI18n()
  const { token } = useAuth()
  const [categories, setCategories] = useState([])
  const [categoryId, setCategoryId] = useState('')
  const [title, setTitle] = useState('')
  const [body, setBody] = useState('')
  const [error, setError] = useState('')
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    const controller = new AbortController()
    Promise.all([
      listForumCategories({ signal: controller.signal }),
      categorySlug ? getForumCategory(categorySlug, { signal: controller.signal }).catch(() => null) : Promise.resolve(null),
    ]).then(([nextCategories, current]) => {
      setCategories(nextCategories)
      const selected = current || nextCategories.find((item) => item.slug === categorySlug) || nextCategories[0]
      if (selected) setCategoryId(String(selected.id))
    }).catch((loadError) => {
      setError(loadError?.message || t('forum.ui.load_error'))
    })
    return () => controller.abort()
  }, [categorySlug, t])

  const handleSubmit = async (event) => {
    event.preventDefault()
    if (!token) return
    setIsSaving(true)
    setError('')
    try {
      const post = await createForumPost({
        forum_category_id: Number(categoryId),
        title: title.trim(),
        body: body.trim(),
      }, { token })
      const slug = post?.category?.slug || categorySlug
      navigate(`/forum/${encodeURIComponent(slug)}/${encodeURIComponent(post.slug)}`)
    } catch (saveError) {
      setError(saveError?.message || t('forum.ui.post_error'))
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <MainLayout>
      <section className={styles.page}>
        <p className={styles.back}>
          <Link to={categorySlug ? `/forum/${encodeURIComponent(categorySlug)}` : '/forum'}>
            {t('forum.ui.back_to_forum')}
          </Link>
        </p>
        <h1>{t('forum.ui.create_post')}</h1>
        {error ? <p className={styles.error}>{error}</p> : null}
        <form className={styles.form} onSubmit={handleSubmit}>
          <label>
            <span>{t('forum.ui.category_label')}</span>
            <select value={categoryId} onChange={(event) => setCategoryId(event.target.value)} required>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>{category.name}</option>
              ))}
            </select>
          </label>
          <label>
            <span>{t('forum.ui.title_label')}</span>
            <input value={title} onChange={(event) => setTitle(event.target.value)} maxLength={200} required />
          </label>
          <label>
            <span>{t('forum.ui.body_label')}</span>
            <textarea value={body} onChange={(event) => setBody(event.target.value)} maxLength={20000} required />
          </label>
          <button type="submit" disabled={isSaving}>
            {isSaving ? t('forum.ui.publishing') : t('forum.ui.publish')}
          </button>
        </form>
      </section>
    </MainLayout>
  )
}
