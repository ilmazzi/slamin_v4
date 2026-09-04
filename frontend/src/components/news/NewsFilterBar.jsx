import styles from './NewsFilterBar.module.css'

const SORT_OPTIONS = ['newest', 'oldest', 'popular']

export default function NewsFilterBar({
  searchValue,
  onSearchChange,
  categoryValue,
  categoryOptions,
  onCategoryChange,
  tagValue,
  tagOptions,
  onTagChange,
  sortValue,
  onSortChange,
  onReset,
  isLoading,
  t,
  showTag = true,
  showReset = true,
  showSearch = true,
  showSort = true,
}) {
  return (
    <div className={styles.filterBar}>
      {showSearch ? (
        <label className={styles.searchWrap}>
          <input
            type="search"
            className={styles.searchInput}
            value={searchValue}
            onChange={(event) => onSearchChange(event.target.value)}
            placeholder={t('articles.filters.search_placeholder')}
            aria-label={t('articles.filters.search_label')}
          />
        </label>
      ) : null}

      <div className={styles.selectsRow}>
        {showSort ? (
          <label className={styles.selectWrap}>
            <select
              className={styles.select}
              value={sortValue}
              onChange={(event) => onSortChange(event.target.value)}
              aria-label={t('articles.filters.sort_label')}
            >
              {SORT_OPTIONS.map((value) => (
                <option key={value} value={value}>
                  {t(`articles.filters.sort_${value}`)}
                </option>
              ))}
            </select>
          </label>
        ) : null}

        <label className={styles.selectWrap}>
          <select
            className={styles.select}
            value={categoryValue}
            onChange={(event) => onCategoryChange(event.target.value)}
            aria-label={t('articles.filters.category_label')}
            disabled={isLoading}
          >
            <option value="">{t('articles.filters.category_placeholder')}</option>
            {categoryOptions.map((item) => (
              <option key={item.value} value={item.value}>
                {item.label}
              </option>
            ))}
          </select>
        </label>

        {showTag ? (
          <label className={styles.selectWrap}>
            <select
              className={styles.select}
              value={tagValue}
              onChange={(event) => onTagChange(event.target.value)}
              aria-label={t('articles.filters.tag_label')}
              disabled={isLoading}
            >
              <option value="">{t('articles.filters.tag_placeholder')}</option>
              {tagOptions.map((item) => (
                <option key={item.value} value={item.value}>
                  {item.label}
                </option>
              ))}
            </select>
          </label>
        ) : null}
      </div>

      {showReset ? (
        <button type="button" className={styles.resetButton} onClick={onReset}>
          {t('articles.filters.reset')}
        </button>
      ) : null}
    </div>
  )
}
