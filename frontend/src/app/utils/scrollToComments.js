function currentHeaderOffset() {
  const header = document.querySelector('header')
  if (!header) {
    return 104
  }

  const rect = header.getBoundingClientRect()
  const height = Number.isFinite(rect.height) ? rect.height : 0
  return Math.max(72, Math.ceil(height) + 12)
}

function flashCommentsAnchor(anchor) {
  if (!anchor) {
    return
  }

  const timerKey = '__commentsHighlightTimer'
  if (anchor[timerKey]) {
    window.clearTimeout(anchor[timerKey])
  }

  anchor.removeAttribute('data-comments-highlight')
  // Force reflow so the animation can restart on repeated clicks.
  void anchor.offsetHeight
  anchor.setAttribute('data-comments-highlight', '1')

  anchor[timerKey] = window.setTimeout(() => {
    anchor.removeAttribute('data-comments-highlight')
  }, 1300)
}

function scrollNow({ behavior = 'auto' } = {}) {
  const anchor = document.getElementById('comments')
  if (!anchor) {
    return false
  }

  const top = window.scrollY + anchor.getBoundingClientRect().top - currentHeaderOffset()
  window.scrollTo({
    top: Math.max(0, top),
    behavior,
  })
  flashCommentsAnchor(anchor)

  return true
}

export function scheduleScrollToComments({
  behavior = 'auto',
  retriesMs = [0, 120, 280, 520],
} = {}) {
  const timers = []
  retriesMs.forEach((delay) => {
    const timer = window.setTimeout(() => {
      scrollNow({ behavior })
    }, delay)
    timers.push(timer)
  })

  return () => {
    timers.forEach((timer) => window.clearTimeout(timer))
  }
}

