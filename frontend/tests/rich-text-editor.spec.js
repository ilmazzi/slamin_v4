import { expect, test } from '@playwright/test'

async function placeCursorAtStart(editor) {
  await editor.evaluate((element) => {
    element.focus()
    const selection = window.getSelection()
    selection.collapse(element.querySelector('p'), 0)
  })
}

for (const type of ['article', 'poem']) {
  test(`${type} translation keeps the cursor and spaces above pasted text`, async ({ page }) => {
    await page.goto(`/tests/fixtures/rich-text-editor.html${type === 'poem' ? '?poem' : ''}`)
    const editor = page.locator('[contenteditable="true"]')
    await editor.click()
    await editor.evaluate((element) => {
      const clipboardData = new DataTransfer()
      clipboardData.setData('text/html', '<p style="text-align: justify;">Testo italiano copiato.</p><p>Secondo paragrafo.</p>')
      element.dispatchEvent(new ClipboardEvent('paste', { clipboardData, bubbles: true, cancelable: true }))
    })
    await expect(editor.locator('p')).toHaveText(['Testo italiano copiato.', 'Secondo paragrafo.'])
    await placeCursorAtStart(editor)
    await editor.press('Enter')
    await editor.press('Enter')
    await placeCursorAtStart(editor)
    await editor.pressSequentially('This is the translation.  More words.', { delay: 25 })
    await expect(editor).toContainText('This is the translation.  More words.')
    await expect(page.getByTestId('value')).toContainText('This is the translation.')
    expect(await editor.locator('p').first().textContent()).toBe('This is the translation.  More words.')

    // External loads and resets must still replace the document.
    await page.getByRole('button', { name: 'Load translation' }).click()
    await expect(editor.locator('p')).toHaveText(['Another translation'])
    await page.getByRole('button', { name: 'Clear translation' }).click()
    await expect(editor).toHaveText('')
    await editor.click()
    await editor.pressSequentially('New words with spaces', { delay: 25 })
    await expect(editor).toHaveText('New words with spaces')
  })
}
