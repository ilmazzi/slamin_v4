import { lazy, Suspense, useEffect } from 'react'
import { Navigate, Route, Routes, useParams } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute.jsx'
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
const RegisterPage = lazy(() =>
  import('./pages/Auth/RegisterPage.jsx').then((module) => ({ default: module.RegisterPage })),
)
const ForgotPasswordPage = lazy(() =>
  import('./pages/Auth/ForgotPasswordPage.jsx').then((module) => ({ default: module.ForgotPasswordPage })),
)
const EventPage = lazy(() => import('./pages/Events/EventPage.jsx'))
const EventCreatePage = lazy(() => import('./pages/Events/EventCreatePage.jsx'))
const EventDetailPage = lazy(() => import('./pages/Events/EventDetailPage.jsx'))
const PoemsPage = lazy(() => import('./pages/Poems/PoemsPage.jsx'))
const ArticlePage = lazy(() => import('./pages/Articles/ArticlePage.jsx'))
const ArticleDetailPage = lazy(() => import('./pages/Articles/ArticleDetailPage.jsx'))
const GigPage = lazy(() => import('./pages/Gigs/GigPage.jsx'))
const MediaPage = lazy(() => import('./pages/Media/MediaPage.jsx'))
const MediaDetailPage = lazy(() => import('./pages/Media/MediaDetailPage.jsx'))
const PoemDetailPage = lazy(() => import('./pages/Poems/PoemDetailPage.jsx'))
const PoemCreate = lazy(() => import('./pages/Poems/PoemCreate.jsx'))
const PlaceholderPage = lazy(() => import('./pages/shared/PlaceholderPage.jsx'))


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
            path="/register"
            element={
              isAuthenticated ? <Navigate to="/dashboard" replace /> : <RegisterPage />
            }
          />
          <Route
            path="/forgot-password"
            element={
              isAuthenticated ? <Navigate to="/dashboard" replace /> : <ForgotPasswordPage />
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
            path="/poems/create"
            element={
              <ProtectedRoute>
                <PoemCreate />
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
          <Route
            path="/media/:mediaId"
            element={
              <ProtectedRoute>
                <MediaDetailPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/media/upload"
            element={
              <ProtectedRoute>
                <PlaceholderPage
                  title="Upload media in arrivo"
                  description="La pagina di upload dedicata non e ancora pronta: per ora usa la sezione media esistente."
                />
              </ProtectedRoute>
            }
          />
          <Route
            path="/articles/create"
            element={
              <ProtectedRoute>
                <PlaceholderPage
                  title="Creazione articolo in arrivo"
                  description="Il flusso di creazione articolo frontend non e ancora disponibile in questa versione."
                />
              </ProtectedRoute>
            }
          />
          <Route
            path="/profile"
            element={
              <ProtectedRoute>
                <PlaceholderPage
                  title="Profilo in arrivo"
                  description="La pagina profilo frontend e' in lavorazione."
                />
              </ProtectedRoute>
            }
          />
          <Route
            path="/profile/edit"
            element={
              <ProtectedRoute>
                <PlaceholderPage
                  title="Modifica profilo in arrivo"
                  description="La modifica profilo dal nuovo frontend non e ancora disponibile."
                />
              </ProtectedRoute>
            }
          />
          <Route
            path="/forum"
            element={
              <PlaceholderPage
                title="Forum in arrivo"
                description="L'area forum non e ancora disponibile nel frontend corrente."
              />
            }
          />
          <Route
            path="/faq"
            element={
              <PlaceholderPage
                title="FAQ in arrivo"
                description="La pagina FAQ e' in preparazione."
              />
            }
          />
          <Route
            path="/help"
            element={
              <PlaceholderPage
                title="Help in arrivo"
                description="La sezione help non e ancora disponibile."
              />
            }
          />
          <Route
            path="/support"
            element={
              <PlaceholderPage
                title="Supporto in arrivo"
                description="La pagina supporto e' in preparazione."
              />
            }
          />
          <Route
            path="/contact"
            element={
              <PlaceholderPage
                title="Contatti in arrivo"
                description="La pagina contatti non e ancora pronta."
              />
            }
          />
          <Route
            path="/contacts"
            element={<Navigate to="/contact" replace />}
          />
          <Route
            path="/tutorial"
            element={
              <PlaceholderPage
                title="Tutorial in arrivo"
                description="I tutorial del nuovo frontend sono in preparazione."
              />
            }
          />
          <Route
            path="/about"
            element={
              <PlaceholderPage
                title="Chi siamo in arrivo"
                description="La pagina informativa non e ancora disponibile."
              />
            }
          />
          <Route
            path="/terms"
            element={
              <PlaceholderPage
                title="Termini in arrivo"
                description="La pagina termini e condizioni e' in preparazione."
              />
            }
          />
          <Route
            path="/privacy"
            element={
              <PlaceholderPage
                title="Privacy in arrivo"
                description="La pagina privacy policy e' in preparazione."
              />
            }
          />
          <Route
            path="/newsletter"
            element={
              <PlaceholderPage
                title="Newsletter in arrivo"
                description="L'iscrizione newsletter dal nuovo frontend non e ancora disponibile."
              />
            }
          />
          <Route
            path="/moderation/dashboard"
            element={
              <ProtectedRoute>
                <PlaceholderPage
                  title="Dashboard moderazione in arrivo"
                  description="La dashboard moderazione frontend dedicata e' in lavorazione."
                />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/settings"
            element={
              <ProtectedRoute>
                <PlaceholderPage
                  title="Impostazioni admin in arrivo"
                  description="Le impostazioni admin frontend dedicate non sono ancora disponibili."
                />
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

function LegacyEventRedirect() {
  const { eventId } = useParams()
  return <Navigate to={`/events/${eventId ?? ''}`} replace />
}
