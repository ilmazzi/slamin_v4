import { lazy, Suspense, useEffect } from 'react'
import { Navigate, Route, Routes, useParams } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute.jsx'
import Header from './components/header/Header.jsx'
import { useAuth } from './app/hooks/useAuth.js'

const DashboardPage = lazy(() => import('./pages/Dashboard/DashboardPage.jsx'))
const OpenProjectFeedbackWidget = lazy(() =>
  import('./components/feedback/OpenProjectFeedbackWidget.jsx'),
)
const HomePage = lazy(() =>
  import('./pages/Home/HomePage.jsx').then((module) => ({ default: module.HomePage })),
)
const LoginPage = lazy(() =>
  import('./pages/Login/LoginPage.jsx').then((module) => ({ default: module.LoginPage })),
)
const EventPage = lazy(() => import('./pages/Events/EventPage.jsx'))
const EventCreatePage = lazy(() => import('./pages/Events/EventCreatePage.jsx'))
const EventDetailPage = lazy(() => import('./pages/Events/EventDetailPage.jsx'))
const PoemsPage = lazy(() => import('./pages/Poems/PoemsPage.jsx'))
const ArticlePage = lazy(() => import('./pages/Articles/ArticlePage.jsx'))
const ArticleDetailPage = lazy(() => import('./pages/Articles/ArticleDetailPage.jsx'))
const GigPage = lazy(() => import('./pages/Gigs/GigPage.jsx'))
const MediaPage = lazy(() => import('./pages/Media/MediaPage.jsx'))
const PoemDetailPage = lazy(() => import('./pages/Poems/PoemDetailPage.jsx'))
const PoemCreate = lazy(() => import('./pages/Poems/PoemCreate.jsx'))


export default function App() {
  const { isAuthenticated, isLoading, user } = useAuth()

  useEffect(() => {
    if (!user?.id || !isAuthenticated) {
      document.body.classList.remove('authenticated')
      document.body.removeAttribute('data-user-id')
      return
    }

    document.body.classList.add('authenticated')
    document.body.setAttribute('data-user-id', String(user.id))
  }, [isAuthenticated, user?.id])

  if (isLoading) {
    return null
  }

  return (
    <>
      <Suspense fallback={null}>
        <Routes>
          <Route
            path="/"
            element={<HomePage variant={isAuthenticated ? 'auth' : 'guest'} user={user} />}
          />

          <Route
            path="/login"
            element={
              isAuthenticated ? <Navigate to="/dashboard" replace /> : <LoginPage />
            }
          />

          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <DashboardPage user={user} />
              </ProtectedRoute>
            }
          />

          <Route
            path="/events"
            element={
              <ProtectedRoute>
                <EventPage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/eventi"
            element={
              <ProtectedRoute>
                <Navigate to="/events" replace />
              </ProtectedRoute>
            }
          />

          <Route
            path="/events/create"
            element={
              <ProtectedRoute>
                <EventCreatePage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/events/:eventId"
            element={
              <ProtectedRoute>
                <EventDetailPage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/eventi/:eventId"
            element={
              <ProtectedRoute>
                <LegacyEventRedirect />
              </ProtectedRoute>
            }
          />

          <Route
            path="/poems"
            element={
              <ProtectedRoute>
                <PoemsPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/poems/:slug"
            element={
              <ProtectedRoute>
                <PoemDetailPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/poems/create"
            element={
              <ProtectedRoute>
                <PoemCreate />
              </ProtectedRoute>
            }
          />
          <Route
            path="/articles"
            element={
              <ProtectedRoute>
                <ArticlePage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/articles/:slug"
            element={
              <ProtectedRoute>
                <ArticleDetailPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/gigs"
            element={
              <ProtectedRoute>
                <GigPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/media"
            element={
              <ProtectedRoute>
                <MediaPage />
              </ProtectedRoute>
            }
          />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
      <Suspense fallback={null}>
        <OpenProjectFeedbackWidget />
      </Suspense>
    </>
  )
}

function PlaceholderPage({ title, variant, user }) {
  return (
    <>
      <Header variant={variant} user={user} />
      <main
        style={{
          minHeight: 'calc(100svh - var(--header-desktop-height))',
          padding: '32px 24px',
        }}
      >
        <h1>{title}</h1>
      </main>
    </>
  )
}

function LegacyEventRedirect() {
  const { eventId } = useParams()
  return <Navigate to={`/events/${eventId ?? ''}`} replace />
}
