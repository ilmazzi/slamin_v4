import { useMemo } from 'react'
import { formatLocaleNumber } from '../../app/formatters/number.js'
import { useDashboardWidgets } from '../../app/hooks/useDashboardWidgets.js'
import { useI18n } from '../../app/hooks/useI18n.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../context/AuthContext.jsx'
import HomeDashboardPromo from './HomeDashboardPromo.jsx'

export default function HomeDashboardPromoSection({ compactTop = false }) {
  const { t, locale } = useI18n()
  const { token, user, isAuthenticated } = useAuth()
  const { widgets: dashboardWidgets } = useDashboardWidgets({
    token,
    locale,
    enabled: isAuthenticated && Boolean(user) && Boolean(token),
  })

  const dashboardMetrics = useMemo(
    () =>
      ['poems', 'events', 'views', 'likes'].map((id) => {
        const item = dashboardWidgets.find((widget) => widget.id === id)
        const value = item?.value ?? 0

        return {
          id,
          value: typeof value === 'number' ? formatLocaleNumber(value, locale) : String(value),
          label: t(`home.dashboard.${id}`),
        }
      }),
    [dashboardWidgets, locale, t],
  )

  if (!isAuthenticated || !user) {
    return null
  }

  return (
    <HomeDashboardPromo
      title={t('home.dashboard.title')}
      userName={user?.display_name || user?.name || t('home.dashboard.profile_fallback')}
      avatarSrc={resolvePublicUrl(user?.profile_photo_url ?? user?.profile_photo)}
      metrics={dashboardMetrics}
      compactTop={compactTop}
    />
  )
}
