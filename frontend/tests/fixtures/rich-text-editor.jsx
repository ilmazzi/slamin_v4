import { useState } from 'react'
import { createRoot } from 'react-dom/client'
import { I18nProvider } from '../../src/app/hooks/useI18n.js'
import RichTextEditor from '../../src/components/shared/RichTextEditor.jsx'

export function EditorFixture() {
  const [value, setValue] = useState('')
  return (
    <I18nProvider>
      <RichTextEditor
        value={value}
        onChange={setValue}
        ariaLabel="Translation"
        compactParagraphs={new URLSearchParams(window.location.search).has('poem')}
      />
      <output data-testid="value">{value}</output>
      <button onClick={() => setValue('<p>Another translation</p>')}>Load translation</button>
      <button onClick={() => setValue('')}>Clear translation</button>
    </I18nProvider>
  )
}

createRoot(document.getElementById('root')).render(<EditorFixture />)
