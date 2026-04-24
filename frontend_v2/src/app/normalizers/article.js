/**
 * UI normalizer for Article API resource.
 * Keeps the "news" components compatible with API v4 naming.
 */
export function normalizeArticleForUi(article) {
  if (!article) {
    return null
  }

  return {
    ...article,
    featured_image: article.cover_image_url ?? article.featured_image,
    metrics: {
      views: article.views_count ?? 0,
      likes: article.likes_count ?? 0,
      comments: article.comments_count ?? 0,
    },
  }
}

export function normalizeArticlesForUi(list) {
  if (!Array.isArray(list)) {
    return []
  }

  return list.map((item) => normalizeArticleForUi(item)).filter(Boolean)
}

