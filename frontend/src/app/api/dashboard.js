import { authApiGet } from './client.js'

export async function getDashboardWidgets(
  { locale, activitiesPage, activitiesFeed } = {},
  { token, signal } = {},
) {
  const params = new URLSearchParams()

  if (locale) {
    params.set('locale', locale)
  }

  if (activitiesPage) {
    params.set('dashboard_activities_page', String(activitiesPage))
  }

  if (activitiesFeed) {
    params.set('activities_feed', activitiesFeed)
  }

  const suffix = params.toString()
  const path = suffix ? `/api/v1/me/dashboard-widgets?${suffix}` : '/api/v1/me/dashboard-widgets'

  return authApiGet(path, {
    token,
    signal,
    fallbackMessage: 'Unable to load dashboard widgets',
  })
}

