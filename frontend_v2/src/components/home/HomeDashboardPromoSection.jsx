import { useEffect, useMemo, useState } from 'react'
import { useI18n } from '../../app/hooks/useI18n.js'
import { authFetch } from '../../app/api/authFetch.js'
import { resolvePublicUrl } from '../../app/api/url.js'
import { useAuth } from '../../context/AuthContext.jsx'
import HomeDashboardPromo from './HomeDashboardPromo.jsx'

export default function HomeDashboardPromoSection({ compactTop = false }) {
  const { t, locale } = useI18n()
  const { token, user, isAuthenticated } = useAuth()
  const [dashboardWidgets, setDashboardWidgets] = useState([])

  useEffect(() => {
    const controller = new AbortController()

    if (!isAuthenticated || !user || !token) {
      setDashboardWidgets([])
      return () => controller.abort()
    }

    async function loadDashboardWidgets() {
      try {
        const response = await authFetch(`/api/v1/me/dashboard-widgets?locale=${encodeURIComponent(locale)}`, {
          token,
          signal: controller.signal,
        })

        if (!response.ok) {
          return
        }

        const payload = await response.json()
        const nextWidgets = payload?.data?.widgets
        if (Array.isArray(nextWidgets)) {
          setDashboardWidgets(nextWidgets)
        }
      } catch (error) {
        if (error?.name !== 'AbortError') {
          console.error('Unable to load dashboard widgets:', error)
        }
      }
    }

    loadDashboardWidgets()

    return () => controller.abort()
  }, [locale, token, user, isAuthenticated])

  const dashboardMetrics = useMemo(
    () =>
      ['poems', 'events', 'views', 'likes'].map((id) => {
        const item = dashboardWidgets.find((widget) => widget.id === id)
        const value = item?.value ?? 0

        return {
          id,
          value: typeof value === 'number' ? new Intl.NumberFormat(locale).format(value) : String(value),
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
