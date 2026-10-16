import { lazy, Suspense, useEffect } from 'react'
import { Navigate, Route, Routes, useParams } from 'react-router-dom'
import ProtectedRoute from './components/ProtectedRoute.jsx'
import ScrollToTop from './components/ScrollToTop.jsx'
import { useAuth } from './app/hooks/useAuth.js'

const DashboardPage = lazy(() => import('./pages/Dashboard/DashboardPage.jsx'))
const OpenProjectFeedbackWidget = lazy(() =>
  import('./components/feedback/OpenProjectFeedbackWidget.jsx'),
)
const ChatWidget = lazy(() => import('./components/chat/ChatWidget.jsx'))
const HomePage = lazy(() =>
  import('./pages/Home/HomePage.jsx').then((module) => ({ default: module.HomePage })),
)
const SearchPage = lazy(() => import('./pages/Search/SearchPage.jsx'))
const LoginPage = lazy(() =>
  import('./pages/Login/LoginPage.jsx').then((module) => ({ default: module.LoginPage })),
)
const RegisterPage = lazy(() =>
  import('./pages/Auth/RegisterPage.jsx').then((module) => ({ default: module.RegisterPage })),
)
const ForgotPasswordPage = lazy(() =>
  import('./pages/Auth/ForgotPasswordPage.jsx').then((module) => ({ default: module.ForgotPasswordPage })),
)
const VerifyEmailPage = lazy(() => import('./pages/Auth/VerifyEmailPage.jsx'))
const EventPage = lazy(() => import('./pages/Events/EventPage.jsx'))
const EventCreatePage = lazy(() => import('./pages/Events/EventCreatePage.jsx'))
const EventDetailPage = lazy(() => import('./pages/Events/EventDetailPage.jsx'))
const PoemsPage = lazy(() => import('./pages/Poems/PoemsPage.jsx'))
const ArticlePage = lazy(() => import('./pages/Articles/ArticlePage.jsx'))
const ArticleDetailPage = lazy(() => import('./pages/Articles/ArticleDetailPage.jsx'))
const ArticleCreate = lazy(() => import('./pages/Articles/ArticleCreate.jsx'))
const GigPage = lazy(() => import('./pages/Gigs/GigPage.jsx'))
const GigDetailPage = lazy(() => import('./pages/Gigs/GigDetailPage.jsx'))
const GigFormPage = lazy(() => import('./pages/Gigs/GigFormPage.jsx'))
const MyGigsPage = lazy(() => import('./pages/Gigs/MyGigsPage.jsx'))
const GigNegotiationPage = lazy(() => import('./pages/Gigs/GigNegotiationPage.jsx'))
const TranslationJobPage = lazy(() => import('./pages/Gigs/TranslationJobPage.jsx'))
const MediaPage = lazy(() => import('./pages/Media/MediaPage.jsx'))
const MediaUploadPage = lazy(() => import('./pages/Media/MediaUploadPage.jsx'))
const MediaDetailPage = lazy(() => import('./pages/Media/MediaDetailPage.jsx'))
const PoemDetailPage = lazy(() => import('./pages/Poems/PoemDetailPage.jsx'))
const PoemCreate = lazy(() => import('./pages/Poems/PoemCreate.jsx'))
const ChatPage = lazy(() => import('./pages/Chat/ChatPage.jsx'))
const PlaceholderPage = lazy(() => import('./pages/shared/PlaceholderPage.jsx'))
const TranslationsAdminPage = lazy(() => import('./pages/Admin/TranslationsAdminPage.jsx'))
const AdminSettingsPage = lazy(() => import('./pages/Admin/AdminSettingsPage.jsx'))
const AdminHomePage = lazy(() => import('./pages/Admin/AdminHomePage.jsx'))
const HelpAdminPage = lazy(() => import('./pages/Admin/HelpAdminPage.jsx'))
const AdminUsersPage = lazy(() => import('./pages/Admin/AdminUsersPage.jsx'))
const EventCategoriesAdminPage = lazy(() => import('./pages/Admin/EventCategoriesAdminPage.jsx'))
const AdminEmailTestsPage = lazy(() => import('./pages/Admin/AdminEmailTestsPage.jsx'))
const HelpPage = lazy(() => import('./pages/Help/HelpPage.jsx'))
const TermsPage = lazy(() => import('./pages/Legal/TermsPage.jsx'))
const PrivacyPage = lazy(() => import('./pages/Legal/PrivacyPage.jsx'))
const ContactPage = lazy(() => import('./pages/Contact/ContactPage.jsx'))
const ModerationDashboardPage = lazy(() => import('./pages/Moderation/ModerationDashboardPage.jsx'))
const ModerationQueuePage = lazy(() => import('./pages/Moderation/ModerationQueuePage.jsx'))
const ModerationReviewPage = lazy(() => import('./pages/Moderation/ModerationReviewPage.jsx'))
const ModerationReportsPage = lazy(() => import('./pages/Moderation/ModerationReportsPage.jsx'))
const ModerationReportDetailPage = lazy(() => import('./pages/Moderation/ModerationReportDetailPage.jsx'))
const ProfilePage = lazy(() => import('./pages/Profile/ProfilePage.jsx'))
const ProfileEditPage = lazy(() => import('./pages/Profile/ProfileEditPage.jsx'))
const PublicProfilePage = lazy(() => import('./pages/Profile/PublicProfilePage.jsx'))


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
      <ScrollToTop />
      <Suspense fallback={null}>
        <Routes>
          <Route
            path="/"
            element={<HomePage variant={isAuthenticated ? 'auth' : 'guest'} user={user} />}
          />
          <Route path="/search" element={<SearchPage />} />

          <Route
            path="/login"
            element={
              isAuthenticated
                ? <Navigate to={user?.email_verified ? '/dashboard' : '/verify-email'} replace />
                : <LoginPage />
            }
          />
          <Route
            path="/register"
            element={
              isAuthenticated
                ? <Navigate to={user?.email_verified ? '/dashboard' : '/verify-email'} replace />
                : <RegisterPage />
            }
          />
          <Route path="/verify-email" element={<VerifyEmailPage />} />
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

          <Route path="/events" element={<EventPage />} />
          <Route path="/eventi" element={<Navigate to="/events" replace />} />

          <Route
            path="/events/create"
            element={
              <ProtectedRoute accountTypes={['poet_organizer']} redirectTo="/events">
                <EventCreatePage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/events/:eventId/edit"
            element={
              <ProtectedRoute accountTypes={['poet_organizer']} redirectTo="/events">
                <EventCreatePage />
              </ProtectedRoute>
            }
          />

          <Route path="/events/:eventId" element={<EventDetailPage />} />
          <Route path="/eventi/:eventId" element={<LegacyEventRedirect />} />
          <Route path="/share/events/:slug" element={<ShareContentRedirect toBase="/events" />} />

          <Route path="/poems" element={<PoemsPage />} />
          <Route
            path="/poems/create"
            element={
              <ProtectedRoute accountTypes={['poet_organizer']} redirectTo="/poems">
                <PoemCreate />
              </ProtectedRoute>
            }
          />
          <Route path="/poems/:slug" element={<PoemDetailPage />} />
          <Route path="/share/poems/:slug" element={<ShareContentRedirect toBase="/poems" />} />
          <Route path="/articles" element={<ArticlePage />} />
          <Route
            path="/articles/create"
            element={
              <ProtectedRoute accountTypes={['poet_organizer']} redirectTo="/articles">
                <ArticleCreate />
              </ProtectedRoute>
            }
          />
          <Route path="/articles/:slug" element={<ArticleDetailPage />} />
          <Route path="/share/articles/:slug" element={<ShareContentRedirect toBase="/articles" />} />
          <Route
            path="/gigs"
            element={
              <ProtectedRoute>
                <GigPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/gigs/create"
            element={
              <ProtectedRoute accountTypes={['poet_organizer']} redirectTo="/gigs">
                <GigFormPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/gigs/mine"
            element={
              <ProtectedRoute>
                <MyGigsPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/gigs/:gigId/edit"
            element={
              <ProtectedRoute accountTypes={['poet_organizer']} redirectTo="/gigs">
                <GigFormPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/gigs/:gigId"
            element={
              <ProtectedRoute>
                <GigDetailPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/gig-negotiations/:negotiationId"
            element={
              <ProtectedRoute>
                <GigNegotiationPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/translation-jobs/:jobId"
            element={
              <ProtectedRoute>
                <TranslationJobPage />
              </ProtectedRoute>
            }
          />
          <Route path="/media" element={<MediaPage />} />
          <Route
            path="/media/upload"
            element={
              <ProtectedRoute accountTypes={['poet_organizer']} redirectTo="/media">
                <MediaUploadPage />
              </ProtectedRoute>
            }
          />
          <Route path="/media/:mediaId" element={<MediaDetailPage />} />
          <Route path="/share/media/:slug" element={<ShareContentRedirect toBase="/media" />} />
          <Route
            path="/profile"
            element={
              <ProtectedRoute>
                <ProfilePage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/profile/edit"
            element={
              <ProtectedRoute>
                <ProfileEditPage />
              </ProtectedRoute>
            }
          />
          <Route path="/u/:nickname" element={<PublicProfilePage />} />
          <Route
            path="/chat"
            element={
              <ProtectedRoute>
                <ChatPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/chat/:threadId"
            element={
              <ProtectedRoute>
                <ChatPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/forum"
            element={
              <PlaceholderPage
                titleKey="common.coming_soon.forum_title"
                descriptionKey="common.coming_soon.forum_description"
              />
            }
          />
          <Route path="/faq" element={<HelpPage section="faq" />} />
          <Route path="/help" element={<HelpPage />} />
          <Route
            path="/support"
            element={
              <PlaceholderPage
                titleKey="common.coming_soon.support_title"
                descriptionKey="common.coming_soon.support_description"
              />
            }
          />
          <Route path="/contact" element={<ContactPage />} />
          <Route
            path="/contacts"
            element={<Navigate to="/contact" replace />}
          />
          <Route
            path="/tutorial"
            element={
              <PlaceholderPage
                titleKey="common.coming_soon.tutorial_title"
                descriptionKey="common.coming_soon.tutorial_description"
              />
            }
          />
          <Route
            path="/about"
            element={
              <PlaceholderPage
                titleKey="common.coming_soon.about_title"
                descriptionKey="common.coming_soon.about_description"
              />
            }
          />
          <Route path="/terms" element={<TermsPage />} />
          <Route path="/privacy" element={<PrivacyPage />} />
          <Route
            path="/newsletter"
            element={
              <PlaceholderPage
                titleKey="common.coming_soon.newsletter_title"
                descriptionKey="common.coming_soon.newsletter_description"
              />
            }
          />
          <Route
            path="/moderation/dashboard"
            element={
              <ProtectedRoute roles={['admin', 'moderator']}>
                <ModerationDashboardPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/moderation/reports"
            element={
              <ProtectedRoute roles={['admin', 'moderator']}>
                <ModerationReportsPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/moderation/reports/:id"
            element={
              <ProtectedRoute roles={['admin', 'moderator']}>
                <ModerationReportDetailPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/moderation/:type"
            element={
              <ProtectedRoute roles={['admin', 'moderator']}>
                <ModerationQueuePage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/moderation/:type/:id"
            element={
              <ProtectedRoute roles={['admin', 'moderator']}>
                <ModerationReviewPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin"
            element={
              <ProtectedRoute roles={['admin', 'moderator', 'translator']}>
                <AdminHomePage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/translations"
            element={
              <ProtectedRoute roles={['admin', 'translator']}>
                <TranslationsAdminPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/settings"
            element={
              <ProtectedRoute roles={['admin']}>
                <AdminSettingsPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/help"
            element={
              <ProtectedRoute roles={['admin']}>
                <HelpAdminPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/faq"
            element={
              <ProtectedRoute roles={['admin']}>
                <HelpAdminPage section="faq" />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/users"
            element={
              <ProtectedRoute roles={['admin']}>
                <AdminUsersPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/event-categories"
            element={
              <ProtectedRoute roles={['admin']}>
                <EventCategoriesAdminPage />
              </ProtectedRoute>
            }
          />
          <Route
            path="/admin/email-tests"
            element={
              <ProtectedRoute roles={['admin']}>
                <AdminEmailTestsPage />
              </ProtectedRoute>
            }
          />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
      <Suspense fallback={null}>
        <ChatWidget />
        <OpenProjectFeedbackWidget />
      </Suspense>
    </>
  )
}

function LegacyEventRedirect() {
  const { eventId } = useParams()
  return <Navigate to={`/events/${eventId ?? ''}`} replace />
}

function ShareContentRedirect({ toBase }) {
  const { slug } = useParams()
  if (!slug) {
    return <Navigate to={toBase} replace />
  }

  return <Navigate to={`${toBase}/${encodeURIComponent(slug)}`} replace />
}
