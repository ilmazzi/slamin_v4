import { Link } from 'react-router-dom'
import filterStyles from '../poems/PoemsFilterBar.module.css'
import styles from './GigsFilterBar.module.css'
import { GIG_SORTS, GIG_TYPES } from '../../app/hooks/useGigPageSearchParams.js'

export default function GigsFilterBar({
  searchValue,
  onSearchChange,
  sortValue,
  onSortChange,
  typeValue,
  onTypeChange,
  t,
}) {
  return (
    <div className={filterStyles.filterBar}>
      <div className={filterStyles.searchRow}>
        <label className={filterStyles.searchWrap}>
          <input
            type="search"
            className={filterStyles.searchInput}
            value={searchValue}
            onChange={(event) => onSearchChange(event.target.value)}
            placeholder={t('gigs.filters.search_placeholder')}
            aria-label={t('gigs.filters.search_label')}
            maxLength={100}
          />
        </label>
        <Link to="/gigs/mine" className={styles.mineLink}>
          {t('gigs.filters.mine')}
        </Link>
      </div>

      <div className={[filterStyles.selectsRow, styles.selectsRow].join(' ')}>
        <label className={filterStyles.selectWrap}>
          <select
            className={filterStyles.select}
            value={sortValue}
            onChange={(event) => onSortChange(event.target.value)}
            aria-label={t('gigs.filters.sort_label')}
          >
            {GIG_SORTS.map((value) => (
              <option key={value} value={value}>
                {t(`gigs.filters.sort_${value}`)}
              </option>
            ))}
          </select>
        </label>

        <label className={filterStyles.selectWrap}>
          <select
            className={filterStyles.select}
            value={typeValue}
            onChange={(event) => onTypeChange(event.target.value)}
            aria-label={t('gigs.filters.type_label')}
          >
            <option value="">{t('gigs.filters.type_placeholder')}</option>
            {GIG_TYPES.map((value) => (
              <option key={value} value={value}>
                {t(`gigs.types.${value}`)}
              </option>
            ))}
          </select>
        </label>
      </div>
    </div>
  )
}
