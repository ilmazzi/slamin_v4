export function splitFeaturedArticle(items, currentPage) {
  const safeItems = Array.isArray(items) ? items : []

  if (safeItems.length === 0) {
    return {
      featuredArticle: null,
      articlesList: [],
    }
  }

  if (Number(currentPage) === 1) {
    return {
      featuredArticle: safeItems[0] ?? null,
      articlesList: safeItems.slice(1),
    }
  }

  return {
    featuredArticle: null,
    articlesList: safeItems,
  }
}
