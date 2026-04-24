import { authApiGet } from './client.js'

export async function getDashboardWidgets(
  { locale, activitiesPage } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()

  if (locale) {
    params.set('locale', locale)
  }

  if (activitiesPage) {
    params.set('dashboard_activities_page', String(activitiesPage))
  }

  const suffix = params.toString()
  const path = suffix ? `/api/v1/me/dashboard-widgets?${suffix}` : '/api/v1/me/dashboard-widgets'

  return authApiGet(path, {
    token,
    signal,
    fallbackMessage: 'Unable to load dashboard widgets',
  })
}

