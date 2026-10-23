import { useCallback, useEffect, useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import { listForumCategories, listForumPosts, voteForumPost } from '../../app/api/forum.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import ForumPostCard from '../../components/forum/ForumPostCard.jsx'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import styles from './ForumPage.module.css'

const PER_PAGE = 20

export default function ForumPage() {
  const { categorySlug } = useParams()
  const { t, locale } = useI18n()
  const { token, isAuthenticated } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const sort = searchParams.get('sort') === 'top' ? 'top' : 'new'
  const currentPage = Math.max(1, Number(searchParams.get('page') || 1))
  const [categories, setCategories] = useState([])
  const [refreshKey, setRefreshKey] = useState(0)

  useEffect(() => {
    const controller = new AbortController()
    listForumCategories({ signal: controller.signal })
      .then(setCategories)
      .catch(() => setCategories([]))
    return () => controller.abort()
  }, [])

  const fetcher = useCallback(
    (params, options) => listForumPosts(
      { ...params, category: categorySlug || '', sort },
      { ...options, token },
    ),
    [categorySlug, sort, token],
  )

  const { items: posts, meta, isLoading, error } = usePaginatedApiList({
    fetcher,
    params: { page: currentPage, perPage: PER_PAGE },
    deps: [currentPage, categorySlug, sort, refreshKey, token],
  })

  const activeCategory = categories.find((item) => item.slug === categorySlug)
  const lastPage = meta.last_page

  const setSort = (nextSort) => {
    const next = new URLSearchParams(searchParams)
    next.set('sort', nextSort)
    next.set('page', '1')
    setSearchParams(next)
  }

  const goToPage = (page) => {
    const next = new URLSearchParams(searchParams)
    next.set('page', String(page))
    setSearchParams(next)
  }

  const handleVote = async (post, value) => {
    if (!token || !post?.public_id) return
    try {
      await voteForumPost(post.public_id, value, { token })
      setRefreshKey((current) => current + 1)
    } catch {
      // Vote errors stay on the current list; a refresh still shows the last known score.
    }
  }

  return (
    <MainLayout>
      <div className={styles.forumPage}>
        <PageHero
          title={activeCategory?.name || t('forum.ui.title')}
          leadPrefix={activeCategory?.description || t('forum.ui.lead_prefix')}
          leadEmphasis={activeCategory ? '' : t('forum.ui.lead_emphasis')}
          leadSuffix={activeCategory ? '' : t('forum.ui.lead_suffix')}
          ctaLabel={t('forum.ui.new_post')}
          ctaHref={categorySlug ? `/forum/${encodeURIComponent(categorySlug)}/new` : '/forum'}
          hideCta={!categorySlug || !isAuthenticated}
        />

        <div className={styles.layout}>
          <nav className={styles.sidebar} aria-label={t('forum.ui.title')}>
            <Link to="/forum" className={`${styles.sideLink} ${!categorySlug ? styles.sideActive : ''}`}>
              {t('forum.ui.all_categories')}
            </Link>
            {categories.map((category) => (
              <Link
                key={category.id}
                to={`/forum/${encodeURIComponent(category.slug)}`}
                className={`${styles.sideLink} ${category.slug === categorySlug ? styles.sideActive : ''}`}
              >
                {category.name}
                <span className={styles.sideMeta}>{category.posts_count || 0}</span>
              </Link>
            ))}
          </nav>

          <section className={styles.feed}>
            <div className={styles.toolbar}>
              <div className={styles.sort}>
                <button type="button" className={sort === 'new' ? styles.active : ''} onClick={() => setSort('new')}>
                  {t('forum.ui.sort_new')}
                </button>
                <button type="button" className={sort === 'top' ? styles.active : ''} onClick={() => setSort('top')}>
                  {t('forum.ui.sort_top')}
                </button>
              </div>
              {categorySlug && isAuthenticated ? (
                <Link className={styles.cta} to={`/forum/${encodeURIComponent(categorySlug)}/new`}>
                  {t('forum.ui.new_post')}
                </Link>
              ) : null}
            </div>

            {error ? <p className={styles.state}>{error || t('forum.ui.load_error')}</p> : null}
            {isLoading ? <p className={styles.state}>{t('forum.ui.loading')}</p> : null}
            {!isLoading && !error && posts.length === 0 ? (
              <p className={styles.state}>
                {categorySlug ? t('forum.ui.empty_category') : t('forum.ui.empty')}
              </p>
            ) : null}

            <div className={styles.list}>
              {posts.map((post) => (
                <ForumPostCard
                  key={post.public_id}
                  post={post}
                  locale={locale}
                  t={t}
                  canVote={Boolean(token)}
                  onVote={handleVote}
                />
              ))}
            </div>

            {meta.total > 0 ? (
              <div className={styles.footer}>
                <Pagination
                  currentPage={Math.min(currentPage, lastPage)}
                  lastPage={lastPage}
                  onPageChange={goToPage}
                  showSinglePage
                />
              </div>
            ) : null}
          </section>
        </div>
      </div>
    </MainLayout>
  )
}
