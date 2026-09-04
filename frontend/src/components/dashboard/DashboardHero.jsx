import styles from '../../pages/Dashboard/DashboardPage.module.css'
import { useState } from 'react'
import { useI18n } from '../../app/hooks/useI18n.js'
import fallbackBackground from '../../assets/dashboard/banner.png'

const FALLBACK_AVATAR =
  'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='

function ResilientImage({ src, fallbackSrc, onError, ...props }) {
  const [hasError, setHasError] = useState(false)

  return (
    <img
      {...props}
      src={hasError ? fallbackSrc : src}
      onError={(event) => {
        if (!hasError) {
          setHasError(true)
        }

        onError?.(event)
      }}
    />
  )
}

function coerceRoles(raw) {
  if (Array.isArray(raw)) {
    return raw
  }
  if (typeof raw === 'string' && raw.trim() !== '') {
    try {
      const parsed = JSON.parse(raw)
      if (Array.isArray(parsed)) {
        return parsed
      }
    } catch {
      // Plain string, e.g. legacy "poet,organizer"
    }
    return raw
      .split(',')
      .map((s) => s.trim())
      .filter(Boolean)
  }
  return []
}

function translateRoleLabel(t, role) {
  const slug = String(role ?? '')
    .toLowerCase()
    .trim()
  if (!slug) {
    return ''
  }

  const key = `home.dashboard_page.roles.${slug}`
  const label = t(key)
  if (label !== key) {
    return label
  }

  return slug.charAt(0).toUpperCase() + slug.slice(1)
}

function formatMemberTenure(createdAtIso, t) {
  if (!createdAtIso) {
    return null
  }

  const created = new Date(createdAtIso)
  if (Number.isNaN(created.getTime())) {
    return null
  }

  const now = Date.now()
  const diffMs = now - created.getTime()
  if (diffMs < 0) {
    return null
  }

  const days = Math.floor(diffMs / 86400000)
  if (days < 1) {
    return t('home.dashboard_page.tenure_today')
  }

  if (days < 30) {
    if (days === 1) {
      return t('home.dashboard_page.tenure_day_one')
    }
    return t('home.dashboard_page.tenure_days').replace(':count', String(days))
  }

  const months = Math.floor(days / 30)
  if (months < 12) {
    if (months === 1) {
      return t('home.dashboard_page.tenure_month_one')
    }
    return t('home.dashboard_page.tenure_months').replace(':count', String(months))
  }

  const years = Math.floor(days / 365)
  if (years === 1) {
    return t('home.dashboard_page.tenure_year_one')
  }
  return t('home.dashboard_page.tenure_years').replace(':count', String(years))
}

export default function DashboardHero({
  displayName,
  nickname,
  avatarSrc,
  backgroundSrc,
  roles = [],
  createdAt,
}) {
  const { t } = useI18n()
  const preferredBackgroundSrc = backgroundSrc || fallbackBackground
  const resolvedAvatarSrc = avatarSrc || FALLBACK_AVATAR

  const normalizedRoles = coerceRoles(roles)
    .map((r) => {
      if (typeof r === 'string') {
        return r.trim()
      }
      if (typeof r === 'number') {
        return String(r).trim()
      }
      return ''
    })
    .filter(Boolean)

  const tenureLabel = formatMemberTenure(createdAt, t)

  const roleBadges = normalizedRoles
    .map((role) => [role, translateRoleLabel(t, role)] )
    .filter(([, label]) => Boolean(label))

  const showBadges = roleBadges.length > 0 || Boolean(tenureLabel)

  return (
    <section className={styles.bannerWrap}>
      <ResilientImage
        key={preferredBackgroundSrc}
        src={preferredBackgroundSrc}
        fallbackSrc={fallbackBackground}
        alt=""
        className={styles.backgroundImage}
      />

      <div className={styles.bannerCard}>
        <div className={styles.avatarWrap}>
          <img src={resolvedAvatarSrc} alt={displayName} className={styles.avatar} />
        </div>

        <h1 className={`${styles.name} typeH3 textWhite`}>{displayName}</h1>
        <p className={`${styles.nickname} typeUi textWhite`}>{nickname}</p>

        {showBadges ? (
          <div className={styles.badges}>
            {roleBadges.map(([role, label], index) => (
              <span key={`${role}-${index}`} className={`${styles.badge} typeUi ${styles.badgeText} textWhite`}>
                {label}
              </span>
            ))}
            {tenureLabel ? (
              <span className={`${styles.badge} typeUi ${styles.badgeText} textWhite`}>{tenureLabel}</span>
            ) : null}
          </div>
        ) : null}
      </div>
    </section>
  )
}
