import { useI18n } from '../../app/hooks/useI18n.js'
import { languageName } from '../../app/i18n/contentLanguages.js'
import styles from './ContentLanguageSwitcher.module.css'

export default function ContentLanguageSwitcher({ item, view, className = '' }) {
  const { t, locale } = useI18n()
  const translations = Array.isArray(item?.translations) ? item.translations : []

  if (translations.length === 0) {
    return null
  }

  const originalName = item?.language ? languageName(item.language, locale) : ''
  const selectedName = view.selectedLocale ? languageName(view.selectedLocale, locale) : ''

  return (
    <div className={[styles.switcher, className].filter(Boolean).join(' ')}>
      <p className={styles.status} aria-live="polite">
        {view.selectedLocale
          ? t('content_translations.reading_in', { language: selectedName })
          : originalName
            ? t('content_translations.reading_original', { language: originalName })
            : t('content_translations.reading_original_plain')}
        {view.selectedLocale ? (
          <button type="button" className={styles.originalLink} onClick={() => view.select('')}>
            {t('content_translations.show_original')}
          </button>
        ) : null}
      </p>

      <label className={styles.field}>
        <span className={styles.label}>{t('content_translations.language_label')}</span>
        <select
          className={styles.select}
          value={view.selectedLocale}
          disabled={view.isLoading}
          onChange={(event) => view.select(event.target.value)}
        >
          <option value="">
            {originalName
              ? t('content_translations.original_option', { language: originalName })
              : t('content_translations.original_option_plain')}
          </option>
          {translations.map((entry) => (
            <option key={entry.locale} value={entry.locale}>
              {languageName(entry.locale, locale)}
            </option>
          ))}
        </select>
      </label>

      {view.error ? (
        <p className={styles.error} role="alert">{t('content_translations.load_error')}</p>
      ) : null}
    </div>
  )
}
