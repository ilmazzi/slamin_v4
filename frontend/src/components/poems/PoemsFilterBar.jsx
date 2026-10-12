import { useState } from 'react'
import styles from './PoemsFilterBar.module.css'

const SORT_OPTIONS = ['newest', 'oldest', 'popular']

export default function PoemsFilterBar({
  searchValue,
  onSearchChange,
  sortValue,
  onSortChange,
  categoryValue,
  categoryOptions,
  onCategoryChange,
  languageValue,
  languageOptions,
  onLanguageChange,
  isLoading,
  t,
}) {
  const [advancedOpen, setAdvancedOpen] = useState(true)

  return (
    <div className={styles.filterBar}>
      <div className={styles.searchRow}>
        <label className={styles.searchWrap}>
          <input
            type="search"
            className={styles.searchInput}
            value={searchValue}
            onChange={(event) => onSearchChange(event.target.value)}
            placeholder={t('poems.filters.search_placeholder')}
            aria-label={t('poems.filters.search_label')}
          />
        </label>

        <button
          type="button"
          className={styles.advancedToggle}
          aria-expanded={advancedOpen}
          onClick={() => setAdvancedOpen((open) => !open)}
        >
          <span className={[styles.chevron, advancedOpen ? styles.chevronOpen : ''].join(' ')} aria-hidden="true">
            ∨
          </span>
          {t('poems.filters.advanced')}
        </button>
      </div>

      {advancedOpen ? (
        <div className={styles.selectsRow}>
          <label className={styles.selectWrap}>
            <select
              className={styles.select}
              value={sortValue}
              onChange={(event) => onSortChange(event.target.value)}
              aria-label={t('poems.filters.sort_label')}
            >
              {SORT_OPTIONS.map((value) => (
                <option key={value} value={value}>
                  {t(`poems.filters.sort_${value}`)}
                </option>
              ))}
            </select>
          </label>

          <label className={styles.selectWrap}>
            <select
              className={styles.select}
              value={categoryValue}
              onChange={(event) => onCategoryChange(event.target.value)}
              aria-label={t('poems.filters.category_label')}
              disabled={isLoading}
            >
              <option value="">{t('poems.filters.category_placeholder')}</option>
              {categoryOptions.map((item) => (
                <option key={item.value} value={item.value}>
                  {item.label}
                </option>
              ))}
            </select>
          </label>

          <label className={styles.selectWrap}>
            <select
              className={styles.select}
              value={languageValue}
              onChange={(event) => onLanguageChange(event.target.value)}
              aria-label={t('poems.filters.language_label')}
              disabled={isLoading}
            >
              <option value="">{t('poems.filters.language_placeholder')}</option>
              {languageOptions.map((item) => (
                <option key={item.value} value={item.value}>
                  {item.label}
                </option>
              ))}
            </select>
          </label>
        </div>
      ) : null}
    </div>
  )
}
