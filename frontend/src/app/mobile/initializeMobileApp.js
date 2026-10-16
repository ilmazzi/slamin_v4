import { App } from '@capacitor/app'
import { Capacitor } from '@capacitor/core'
import { Keyboard } from '@capacitor/keyboard'
import { SplashScreen } from '@capacitor/splash-screen'
import { StatusBar, Style } from '@capacitor/status-bar'

function openInternalUrl(rawUrl) {
  try {
    const url = new URL(rawUrl)
    const nextPath = `${url.pathname}${url.search}${url.hash}`
    const currentPath = `${window.location.pathname}${window.location.search}${window.location.hash}`

    if (nextPath !== currentPath) {
      window.history.pushState(null, '', nextPath)
      window.dispatchEvent(new PopStateEvent('popstate'))
    }
  } catch {
    // Ignore malformed external URLs delivered by the native shell.
  }
}

export async function initializeMobileApp() {
  if (!Capacitor.isNativePlatform()) {
    return
  }

  const root = document.documentElement
  root.classList.add('native-app', `native-${Capacitor.getPlatform()}`)

  const setupResults = await Promise.allSettled([
    StatusBar.setOverlaysWebView({ overlay: false }),
    StatusBar.setStyle({ style: Style.Light }),
    StatusBar.setBackgroundColor({ color: '#000000' }),
    Keyboard.addListener('keyboardWillShow', () => {
      root.classList.add('native-keyboard-open')
    }),
    Keyboard.addListener('keyboardWillHide', () => {
      root.classList.remove('native-keyboard-open')
    }),
    App.addListener('backButton', ({ canGoBack }) => {
      if (canGoBack) {
        window.history.back()
        return
      }

      App.exitApp()
    }),
    App.addListener('appUrlOpen', ({ url }) => openInternalUrl(url)),
  ])

  setupResults.forEach((result) => {
    if (result.status === 'rejected') {
      console.warn('Unable to initialize a native mobile integration.', result.reason)
    }
  })

  await SplashScreen.hide()
}
