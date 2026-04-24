import { useEffect, useState } from 'react'
import { getDashboardWidgets } from '../api/dashboard.js'

const EMPTY_RECENT_ACTIVITIES = {
  total: 0,
  per_page: 3,
  current_page: 1,
  last_page: 1,
  items: [],
}

export function useDashboardWidgets({
  token,
  locale,
  enabled = true,
  activitiesPage,
}) {
  const [widgets, setWidgets] = useState([])
  const [communityCards, setCommunityCards] = useState([])
  const [recentActivitiesData, setRecentActivitiesData] = useState(EMPTY_RECENT_ACTIVITIES)
  const [isLoading, setIsLoading] = useState(enabled)

  useEffect(() => {
    const controller = new AbortController()

    if (!enabled || !token) {
      setWidgets([])
      setCommunityCards([])
      setRecentActivitiesData(EMPTY_RECENT_ACTIVITIES)
      setIsLoading(false)
      return () => controller.abort()
    }

    async function load() {
      setIsLoading(true)

      try {
        const result = await getDashboardWidgets(
          { locale, activitiesPage },
          {
            token,
            signal: controller.signal,
          },
        )

        if (!result.ok) {
          throw new Error('Failed to fetch dashboard widgets')
        }

        setWidgets(result.data?.widgets ?? [])
        setCommunityCards(result.data?.community_cards ?? [])
        setRecentActivitiesData(result.data?.recent_activities ?? EMPTY_RECENT_ACTIVITIES)
      } catch (error) {
        if (error?.name === 'AbortError') {
          return
        }

        console.error('Unable to load dashboard widgets:', error)
        setWidgets([])
        setCommunityCards([])
        setRecentActivitiesData(EMPTY_RECENT_ACTIVITIES)
      } finally {
        if (!controller.signal.aborted) {
          setIsLoading(false)
        }
      }
    }

    load()

    return () => controller.abort()
  }, [activitiesPage, enabled, locale, token])

  return {
    widgets,
    communityCards,
    recentActivitiesData,
    isLoading,
  }
}

