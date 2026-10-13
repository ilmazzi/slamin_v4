import styles from './MediaFilterBar.module.css'

const SORT_OPTIONS = ['newest', 'oldest', 'popular']
const CATEGORY_OPTIONS = ['', 'video', 'photo']

export default function MediaFilterBar({
  searchValue,
  onSearchChange,
  sortValue,
  onSortChange,
  categoryValue,
  onCategoryChange,
  t,
}) {
  return (
    <div className={styles.filterBar}>
      <label className={styles.searchWrap}>
        <input
          type="search"
          className={styles.searchInput}
          value={searchValue}
          onChange={(event) => onSearchChange(event.target.value)}
          placeholder={t('media.filters.search_placeholder')}
          aria-label={t('media.filters.search_label')}
        />
      </label>

      <div className={styles.selectsRow}>
        <label className={styles.selectWrap}>
          <select
            className={styles.select}
            value={sortValue}
            onChange={(event) => onSortChange(event.target.value)}
            aria-label={t('media.filters.sort_label')}
          >
            {SORT_OPTIONS.map((value) => (
              <option key={value} value={value}>
                {t(`media.filters.sort_${value}`)}
              </option>
            ))}
          </select>
        </label>

        <label className={styles.selectWrap}>
          <select
            className={styles.select}
            value={categoryValue}
            onChange={(event) => onCategoryChange(event.target.value)}
            aria-label={t('media.filters.category_label')}
          >
            {CATEGORY_OPTIONS.map((value) => (
              <option key={value || 'all'} value={value}>
                {value ? t(`media.filters.category_${value}`) : t('media.filters.category_all')}
              </option>
            ))}
          </select>
        </label>
      </div>
    </div>
  )
}
