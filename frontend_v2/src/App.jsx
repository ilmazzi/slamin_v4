import { useEffect } from 'react'
import { Navigate, Route, Routes, useParams } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute.jsx'
import OpenProjectFeedbackWidget from './components/feedback/OpenProjectFeedbackWidget.jsx'
import Header from './components/header/Header.jsx'
import { useAuth } from './context/AuthContext.jsx'
import DashboardPage from './pages/DashboardPage.jsx'
import { HomePage } from './pages/HomePage.jsx'
import { LoginPage } from './pages/LoginPage.jsx'
import EventPage from './pages/EventPage.jsx'
import EventDetailPage from './pages/EventDetailPage.jsx'
import PoemsPage from './pages/Poems.jsx'
import ArticlePage from './pages/ArticlePage.jsx'
import GigPage from './pages/GigPage.jsx'
import MediaPage from './pages/MediaPage.jsx'
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
              <PlaceholderPage title="Create Event" variant="auth" user={user} />
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
          path="/articles"
          element={
            <ProtectedRoute>
              <ArticlePage />
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
      <OpenProjectFeedbackWidget />
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
