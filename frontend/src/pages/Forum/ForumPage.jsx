import { useCallback, useEffect, useMemo, useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import { listForumCategories, listForumPosts, voteForumPost } from '../../app/api/forum.js'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { usePaginatedApiList } from '../../app/hooks/usePaginatedApiList.js'
import ForumCategoryCard from '../../components/forum/ForumCategoryCard.jsx'
import ForumPostCard from '../../components/forum/ForumPostCard.jsx'
import HomeDashboardPromoSection from '../../components/home/HomeDashboardPromoSection.jsx'
import MainLayout from '../../components/layout/MainLayout.jsx'
import PageHero from '../../components/PageHero.jsx'
import Pagination from '../../components/shared/Pagination.jsx'
import pageStyles from '../shared/ContentPageShell.module.css'
import styles from './ForumPage.module.css'

const PER_PAGE = 20

export default function ForumPage() {
  const { categorySlug } = useParams()
  const { t, locale } = useI18n()
  const { token, isAuthenticated } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const sort = searchParams.get('sort') === 'top' ? 'top' : 'new'
  const categorySort = searchParams.get('sort') === 'az' ? 'az' : 'popular'
  const query = searchParams.get('q') || ''
  const currentPage = Math.max(1, Number(searchParams.get('page') || 1))
  const [categories, setCategories] = useState([])
  const [searchInput, setSearchInput] = useState(query)
  const [refreshKey, setRefreshKey] = useState(0)

  useEffect(() => {
    setSearchInput(query)
  }, [query])

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (searchInput === query) return
      const next = new URLSearchParams(searchParams)
      if (searchInput.trim()) next.set('q', searchInput.trim())
      else next.delete('q')
      next.delete('page')
      setSearchParams(next)
    }, 250)
    return () => window.clearTimeout(timer)
  }, [query, searchInput, searchParams, setSearchParams])

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
    enabled: Boolean(categorySlug),
    deps: [currentPage, categorySlug, sort, refreshKey, token],
  })

  const activeCategory = categories.find((item) => item.slug === categorySlug)
  const lastPage = meta.last_page
  const createHref = categorySlug
    ? `/forum/${encodeURIComponent(categorySlug)}/new`
    : '/forum/new'

  const visibleCategories = useMemo(() => {
    const needle = query.trim().toLowerCase()
    const filtered = categories.filter((category) => {
      if (!needle) return true
      return [category.name, category.slug, category.description]
        .filter(Boolean)
        .some((value) => String(value).toLowerCase().includes(needle))
    })

    return [...filtered].sort((left, right) => {
      if (categorySort === 'az') {
        return String(left.name || left.slug).localeCompare(String(right.name || right.slug), locale)
      }
      return Number(right.posts_count || 0) - Number(left.posts_count || 0)
    })
  }, [categories, categorySort, locale, query])

  const setParam = (key, value) => {
    const next = new URLSearchParams(searchParams)
    next.set(key, value)
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

  const heroLead = (
    <>
      {t('forum.ui.lead_join')}
      {' '}
      <span>
        {t('forum.ui.lead_community')}
        {t('forum.ui.lead_join_end')}
      </span>
      <br />
      <span>{t('forum.ui.lead_share')}</span>
      {t('forum.ui.lead_discuss')}
    </>
  )

  return (
    <MainLayout themeTone="forum">
      <div className={pageStyles.forumPage}>
        <PageHero
          title={t('forum.ui.title')}
          lead={heroLead}
          ctaLabel={t('forum.ui.create_post')}
          ctaHref={createHref}
          hideCta={!isAuthenticated}
        />

        <section className={styles.toolbar}>
          {!categorySlug ? (
            <label className={styles.searchWrap}>
              <input
                type="search"
                className={styles.search}
                value={searchInput}
                onChange={(event) => setSearchInput(event.target.value)}
                placeholder={t('forum.ui.search_placeholder')}
                aria-label={t('forum.ui.search_placeholder')}
              />
            </label>
          ) : (
            <Link to="/forum" className={styles.back}>
              {t('forum.ui.back_to_forum')}
            </Link>
          )}

          <label className={styles.sortWrap}>
            <select
              className={styles.sort}
              value={categorySlug ? sort : categorySort}
              onChange={(event) => setParam('sort', event.target.value)}
              aria-label={t('forum.ui.sort_prefix')}
            >
              {categorySlug ? (
                <>
                  <option value="new">{t('forum.ui.sort_prefix')} {t('forum.ui.sort_new')}</option>
                  <option value="top">{t('forum.ui.sort_prefix')} {t('forum.ui.sort_top')}</option>
                </>
              ) : (
                <>
                  <option value="popular">{t('forum.ui.sort_prefix')} {t('forum.ui.sort_popular')}</option>
                  <option value="az">{t('forum.ui.sort_prefix')} {t('forum.ui.sort_az')}</option>
                </>
              )}
            </select>
          </label>
        </section>

        {!categorySlug ? (
          <section className={styles.categories}>
            {visibleCategories.length === 0 ? (
              <p className={styles.state}>{t('forum.ui.empty_categories')}</p>
            ) : null}
            {visibleCategories.map((category) => (
              <ForumCategoryCard key={category.id} category={category} t={t} />
            ))}
          </section>
        ) : (
          <section className={styles.feed}>
            {activeCategory ? (
              <ForumCategoryCard category={activeCategory} t={t} interactive={false} />
            ) : null}

            {error ? <p className={styles.state}>{error || t('forum.ui.load_error')}</p> : null}
            {isLoading ? <p className={styles.state}>{t('forum.ui.loading')}</p> : null}
            {!isLoading && !error && posts.length === 0 ? (
              <p className={styles.state}>{t('forum.ui.empty_category')}</p>
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
        )}
      </div>

      <HomeDashboardPromoSection />
    </MainLayout>
  )
}
