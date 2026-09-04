import Footer from '../footer/Footer.jsx'
import Header from '../header/Header.jsx'
import { useAuth } from '../../app/hooks/useAuth.js'
import { useI18n } from '../../app/hooks/useI18n.js'

export default function MainLayout({
  themeTone,
  variant = 'auto',
  user,
  pageClassName,
  mainClassName,
  showFooter = true,
  syncHeaderWithTheme = true,
  children,
}) {
  const toneToCssVar = {
    event: 'var(--color-event)',
    poems: 'var(--color-poems)',
    articles: 'var(--color-articles)',
    gigs: 'var(--color-gigs)',
    red: 'var(--color-red)',
    black: 'var(--color-black)',
    media: 'var(--color-media)',
  }
  const accentColor = toneToCssVar[themeTone] || ''
  const auth = useAuth()
  const i18n = useI18n()

  const resolvedVariant =
    variant === 'auto' ? (auth.isAuthenticated ? 'auth' : 'guest') : variant
  const resolvedUser = user ?? auth.user ?? null

  const content =
    typeof children === 'function'
      ? children({
          ...auth,
          ...i18n,
          variant: resolvedVariant,
          user: resolvedUser,
        })
      : children

  const shouldThemeHeader = syncHeaderWithTheme && Boolean(accentColor)
  const resolvedLogoTone = shouldThemeHeader ? 'dark' : 'light'

  const styleVars = accentColor
    ? {
        '--page-hero-accent': accentColor,
      }
    : {}

  const headerVars = shouldThemeHeader
    ? {
        '--color-header-bg': accentColor,
        '--color-header-fg': 'var(--color-black)',
        '--color-header-border': 'var(--color-black)',
        '--header-logo-asset': 'var(--logo-black)',
      }
    : {
        '--color-header-bg': 'var(--color-black)',
        '--color-header-fg': 'var(--color-white)',
        '--color-header-border': 'var(--color-white)',
        '--header-logo-asset': 'var(--logo-white)',
      }

  return (
    <div className={pageClassName} style={{ ...styleVars, ...headerVars }}>
      <Header variant={resolvedVariant} user={resolvedUser} logoTone={resolvedLogoTone} />
      <main className={mainClassName}>{content}</main>
      {showFooter ? <Footer /> : null}
    </div>
  )
}
