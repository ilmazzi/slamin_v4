import { expect, test } from '@playwright/test'

const publicRoutes = [
  '/',
  '/login',
  '/register',
  '/forgot-password',
  '/forum',
  '/faq',
  '/help',
  '/support',
  '/contact',
  '/about',
  '/terms',
  '/privacy',
  '/cookies',
  '/newsletter',
]

const authRoutes = [
  '/dashboard',
  '/events',
  '/poems',
  '/articles',
  '/gigs',
  '/media',
  '/profile',
  '/profile/edit',
  '/media/upload',
  '/articles/create',
  '/moderation/dashboard',
  '/admin/settings',
]

async function loginAsSmokeUser(page) {
  await page.goto('/login', { waitUntil: 'domcontentloaded' })
  await page.fill('input[name="email"]', 'frontend.smoke@example.com')
  await page.fill('input[name="password"]', 'Passw0rd!234')
  await Promise.all([
    page.waitForURL('**/dashboard', { timeout: 25_000 }),
    page.click('button[type="submit"]'),
  ])
}

async function resolveContentPathValue(request, type) {
  const path = type === 'poem'
    ? '/api/v1/poems?per_page=50&page=1'
    : '/api/v1/articles?per_page=50&page=1'

  const response = await request.get(path)
  expect(response.ok()).toBeTruthy()
  const payload = await response.json()
  const rows = Array.isArray(payload?.data) ? payload.data : []
  const first = rows[0]
  const value = first?.slug || first?.id
  expect(value).toBeTruthy()
  return value
}

async function resolveMediaId(request) {
  const response = await request.get('/api/v1/media?per_page=10&page=1')
  expect(response.ok()).toBeTruthy()
  const payload = await response.json()
  const rows = Array.isArray(payload?.data) ? payload.data : []
  return rows[0]?.id ?? null
}

async function resolveEventPathValue(request) {
  const response = await request.get('/api/v1/events?per_page=10&page=1')
  expect(response.ok()).toBeTruthy()
  const payload = await response.json()
  const rows = Array.isArray(payload?.data) ? payload.data : []
  const first = rows[0]
  const value = first?.slug || first?.id
  expect(value).toBeTruthy()
  return value
}

test('public routes load without server errors', async ({ page }) => {
  for (const route of publicRoutes) {
    const response = await page.goto(route, { waitUntil: 'domcontentloaded' })
    expect(response, `missing response for ${route}`).not.toBeNull()
    expect(response.status(), `status for ${route}`).toBeLessThan(400)
  }
})

test('unauthenticated users are redirected from protected route', async ({ page }) => {
  await page.goto('/dashboard', { waitUntil: 'networkidle' })
  await expect(page).toHaveURL(/\/login$/)
})

test('event share composer switches branded image formats without mobile overflow', async ({ page, request }) => {
  const eventPathValue = await resolveEventPathValue(request)
  await page.addInitScript(() => {
    Object.defineProperty(navigator, 'canShare', {
      configurable: true,
      value: (payload) => Array.isArray(payload?.files) && payload.files.length > 0,
    })
    Object.defineProperty(navigator, 'share', {
      configurable: true,
      value: async (payload) => {
        window.__lastSharePayload = {
          hasFile: Array.isArray(payload?.files) && payload.files.length === 1,
          fileName: payload?.files?.[0]?.name ?? '',
          hasUrl: Boolean(payload?.url),
        }
      },
    })
  })
  await page.setViewportSize({ width: 390, height: 844 })
  await page.goto(`/events/${eventPathValue}`, { waitUntil: 'networkidle' })

  await page.getByRole('button', { name: /condividi evento|share event/i }).filter({ visible: true }).first().click()

  const dialog = page.getByRole('dialog')
  const preview = dialog.locator('img')
  await expect(dialog).toBeVisible()
  await expect(preview).toBeVisible()
  await expect.poll(() => preview.evaluate((image) => [image.naturalWidth, image.naturalHeight])).toEqual([1080, 1080])

  await dialog.getByRole('button', { name: /post verticale|portrait post/i }).click()
  await expect.poll(() => preview.evaluate((image) => [image.naturalWidth, image.naturalHeight])).toEqual([1080, 1350])
  await dialog.getByRole('button', { name: /condividi immagine|share image/i }).click()
  await expect.poll(() => page.evaluate(() => window.__lastSharePayload)).toEqual({
    hasFile: true,
    fileName: expect.stringContaining('-portrait.png'),
    hasUrl: false,
  })
  await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth)).toBe(390)
})

test('login flow and authenticated routes', async ({ page }) => {
  await loginAsSmokeUser(page)

  await expect(page).toHaveURL(/\/dashboard$/)

  for (const route of authRoutes) {
    const response = await page.goto(route, { waitUntil: 'domcontentloaded' })
    expect(response, `missing response for ${route}`).not.toBeNull()
    expect(response.status(), `status for ${route}`).toBeLessThan(400)
    await expect(page, `route should not bounce to login: ${route}`).not.toHaveURL(/\/login$/)
  }
})

test('poem detail supports comment and report flows', async ({ page, request }) => {
  await loginAsSmokeUser(page)
  const poemPathValue = await resolveContentPathValue(request, 'poem')

  await page.goto(`/poems/${poemPathValue}`, { waitUntil: 'domcontentloaded' })
  await expect(page.getByRole('heading', { name: /commenti poesia/i })).toBeVisible()

  const uniqueComment = `Smoke poem comment ${Date.now()}`
  await page.fill('textarea[placeholder="Scrivi un commento..."]', uniqueComment)
  await page.click('button:has-text("Pubblica commento")')
  await expect(page.getByText(uniqueComment)).toBeVisible()

  await page
    .locator('button[aria-label="Segnala"], button[aria-label="Salva"], button[aria-label="Report"], button[aria-label="Save article"], button[aria-label="poems.actions.report"], button[aria-label="poems.featured.save"], button[aria-label="articles.featured.save"]')
    .first()
    .click()
  const reportModal = page.getByRole('dialog')
  await reportModal.locator('select').selectOption('spam')
  const reportRequest = page.waitForResponse((response) =>
    response.request().method() === 'POST'
      && /\/api\/v1\/poems\/[^/]+\/reports$/.test(new URL(response.url()).pathname),
  )
  await reportModal.getByRole('button', { name: /report_confirm|Segnala|Send report|Save article/i }).first().click()
  const reportResponse = await reportRequest
  expect([200, 422]).toContain(reportResponse.status())
})

test('article detail supports comment and report flows', async ({ page, request }) => {
  await loginAsSmokeUser(page)
  const articlePathValue = await resolveContentPathValue(request, 'article')

  await page.goto(`/articles/${articlePathValue}`, { waitUntil: 'domcontentloaded' })
  await expect(page.getByRole('heading', { name: /commenti articolo/i })).toBeVisible()

  const uniqueComment = `Smoke article comment ${Date.now()}`
  await page.fill('textarea[placeholder="Scrivi un commento..."]', uniqueComment)
  await page.click('button:has-text("Pubblica commento")')
  await expect(page.getByText(uniqueComment)).toBeVisible()

  await page
    .locator('button[aria-label="Segnala"], button[aria-label="Salva"], button[aria-label="Report"], button[aria-label="Save article"], button[aria-label="poems.actions.report"], button[aria-label="poems.featured.save"], button[aria-label="articles.featured.save"]')
    .first()
    .click()
  const reportModal = page.getByRole('dialog')
  await reportModal.locator('select').selectOption('abuse')
  const reportRequest = page.waitForResponse((response) =>
    response.request().method() === 'POST'
      && /\/api\/v1\/articles\/[^/]+\/reports$/.test(new URL(response.url()).pathname),
  )
  await reportModal.getByRole('button', { name: /report_confirm|Segnala|Send report|Save article/i }).first().click()
  const reportResponse = await reportRequest
  expect([200, 422]).toContain(reportResponse.status())
})

test('media detail supports comment and report flows', async ({ page, request }) => {
  await loginAsSmokeUser(page)
  const mediaId = await resolveMediaId(request)
  test.skip(!mediaId, 'No public media available for smoke test')

  await page.goto(`/media/${mediaId}`, { waitUntil: 'domcontentloaded' })
  await expect(page.getByRole('heading', { name: /commenti media/i })).toBeVisible()

  const uniqueComment = `Smoke media comment ${Date.now()}`
  await page.fill('textarea[placeholder="Scrivi un commento..."]', uniqueComment)
  await page.click('button:has-text("Pubblica commento")')
  await expect(page.getByText(uniqueComment)).toBeVisible()

  await page
    .locator('button[aria-label="Report media"], button[aria-label="Segnala"], button[aria-label="Save article"]')
    .first()
    .click()
  const reportModal = page.getByRole('dialog')
  await reportModal.locator('select').selectOption('other')
  const reportRequest = page.waitForResponse((response) =>
    response.request().method() === 'POST'
      && /\/api\/v1\/media\/[^/]+\/reports$/.test(new URL(response.url()).pathname),
  )
  await reportModal.getByRole('button', { name: /Send report|report_confirm|Segnala/i }).first().click()
  const reportResponse = await reportRequest
  expect([200, 422]).toContain(reportResponse.status())
})
